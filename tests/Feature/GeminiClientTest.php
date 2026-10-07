<?php

namespace Tests\Feature;

use App\Services\Ai\GeminiClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the ordered model chain behind GeminiClient::generateJson(): per-model
 * "unavailable until" memory, failure classification, the depth cap, profiles,
 * per-model thinking levels, and the RuntimeException contract callers rely on.
 */
class GeminiClientTest extends TestCase
{
    private const MODEL_A = 'generativelanguage.googleapis.com/v1beta/models/model-a:generateContent';

    private const MODEL_B = 'generativelanguage.googleapis.com/v1beta/models/model-b:generateContent';

    private const MODEL_C = 'generativelanguage.googleapis.com/v1beta/models/model-c:generateContent';

    private const MODEL_D = 'generativelanguage.googleapis.com/v1beta/models/model-d:generateContent';

    private const SCHEMA = ['type' => 'OBJECT', 'properties' => ['answer' => ['type' => 'STRING']], 'required' => ['answer']];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);
        Http::preventStrayRequests();
    }

    /** @return array<string, mixed> */
    private function jsonResponse(string $answer): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => $answer])]]]]]];
    }

    /**
     * The shape of a real Google free-tier 429 (see docs/DECISIONS.md, AI model chain).
     *
     * @return array<string, mixed>
     */
    private function quotaBody(string $quotaId, string $retryDelay): array
    {
        return ['error' => [
            'code' => 429,
            'status' => 'RESOURCE_EXHAUSTED',
            'message' => 'You exceeded your current quota.',
            'details' => [
                ['@type' => 'type.googleapis.com/google.rpc.QuotaFailure', 'violations' => [['quotaId' => $quotaId]]],
                ['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => $retryDelay],
            ],
        ]];
    }

    /**
     * @param  list<string>  $models
     */
    private function judgeChain(array $models): GeminiClient
    {
        config(['services.gemini.judge_models' => $models]);

        return new GeminiClient;
    }

    private function ask(GeminiClient $client, string $profile = GeminiClient::PROFILE_JUDGE): string
    {
        return $client->generateJson('Say hi.', self::SCHEMA, $profile)['answer'];
    }

    private function sentTo(string $model): int
    {
        return Http::recorded(fn ($request) => str_contains((string) $request->url(), "/models/{$model}:"))->count();
    }

    public function test_first_model_answers_and_the_structured_output_payload_is_unchanged(): void
    {
        Http::fake([self::MODEL_A => Http::response($this->jsonResponse('Hello from A.'))]);

        $this->assertSame('Hello from A.', $this->ask($this->judgeChain(['model-a', 'model-b'])));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['generationConfig'] === ['responseMimeType' => 'application/json', 'responseSchema' => self::SCHEMA]
            && $request['contents'][0]['parts'][0]['text'] === 'Say hi.'
            && $request->hasHeader('x-goog-api-key', 'test-key'));
    }

    public function test_daily_quota_429_skips_the_model_on_later_requests_until_its_retry_delay_passes(): void
    {
        Http::fake([
            self::MODEL_A => Http::sequence()
                ->push($this->quotaBody('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '40085s'), 429)
                ->push($this->jsonResponse('A is back.')),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);
        $client = $this->judgeChain(['model-a', 'model-b']);

        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame(1, $this->sentTo('model-a'), 'Later requests must not pay a failed request on the exhausted model.');
        $this->assertSame(2, $this->sentTo('model-b'));

        $this->travel(40086)->seconds();

        $this->assertSame('A is back.', $this->ask($client));
        $this->assertSame(2, $this->sentTo('model-a'));
    }

    public function test_per_minute_429_is_skipped_only_for_about_its_retry_delay(): void
    {
        Http::fake([
            self::MODEL_A => Http::sequence()
                ->push($this->quotaBody('GenerateRequestsPerMinutePerProjectPerModel-FreeTier', '30s'), 429)
                ->push($this->jsonResponse('A is back.')),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);
        $client = $this->judgeChain(['model-a', 'model-b']);

        $this->assertSame('Hello from B.', $this->ask($client));

        $this->travel(20)->seconds();
        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame(1, $this->sentTo('model-a'));

        $this->travel(15)->seconds();
        $this->assertSame('A is back.', $this->ask($client));
    }

    public function test_retired_model_404_is_skipped_for_a_long_time(): void
    {
        Http::fake([
            self::MODEL_A => Http::response(['error' => ['code' => 404, 'message' => 'no longer available to new users']], 404),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);
        $client = $this->judgeChain(['model-a', 'model-b']);

        $this->assertSame('Hello from B.', $this->ask($client));

        $this->travel(2)->hours();
        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame(1, $this->sentTo('model-a'));
    }

    public function test_server_error_marks_the_model_down_for_a_few_minutes_only(): void
    {
        Http::fake([
            self::MODEL_A => Http::sequence()
                ->push(['error' => 'overloaded'], 503)
                ->push($this->jsonResponse('A is back.')),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);
        $client = $this->judgeChain(['model-a', 'model-b']);

        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame('Hello from B.', $this->ask($client));
        $this->assertSame(1, $this->sentTo('model-a'));

        $this->travel(4)->minutes();

        $this->assertSame('A is back.', $this->ask($client));
    }

    public function test_connection_failure_moves_on_to_the_next_model(): void
    {
        Http::fake([
            self::MODEL_A => Http::failedConnection(),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);

        $this->assertSame('Hello from B.', $this->ask($this->judgeChain(['model-a', 'model-b'])));
    }

    public function test_bad_request_400_fails_without_walking_the_chain(): void
    {
        Http::fake([
            self::MODEL_A => Http::response(['error' => ['code' => 400, 'message' => 'bad request']], 400),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);

        try {
            $this->ask($this->judgeChain(['model-a', 'model-b']));
            $this->fail('Expected a 400 to fail the request, not be retried on another model.');
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RequestException::class, $e->getPrevious());
            $this->assertSame(400, $e->getPrevious()->response->status());
        }

        $this->assertSame(0, $this->sentTo('model-b'));
    }

    public function test_a_total_outage_throws_a_runtime_exception_without_the_key_or_the_provider_body(): void
    {
        Log::spy();
        Http::fake([
            self::MODEL_A => Http::response('provider overloaded', 503),
            self::MODEL_B => Http::response(['error' => 'down'], 503),
        ]);

        try {
            $this->ask($this->judgeChain(['model-a', 'model-b']));
            $this->fail('Expected a RuntimeException once every model failed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Gemini request failed on every model.', $e->getMessage());
            $this->assertStringNotContainsString('provider overloaded', $e->getMessage());
        }

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'failed on every model')
                && $context['profile'] === 'judge'
                && $context['attempts'][0]['kind'] === 'transient'
                && ! str_contains(json_encode($context), 'test-key'))
            ->once();
    }

    public function test_a_request_tries_at_most_three_models(): void
    {
        Http::fake([
            self::MODEL_A => Http::response(['error' => 'down'], 503),
            self::MODEL_B => Http::response(['error' => 'down'], 503),
            self::MODEL_C => Http::response(['error' => 'down'], 503),
            self::MODEL_D => Http::response($this->jsonResponse('Hello from D.')),
        ]);

        $this->expectException(RuntimeException::class);

        try {
            $this->ask($this->judgeChain(['model-a', 'model-b', 'model-c', 'model-d']));
        } finally {
            $this->assertSame(0, $this->sentTo('model-d'));
        }
    }

    public function test_when_every_model_is_marked_down_the_one_healing_soonest_is_probed(): void
    {
        Http::fake([
            self::MODEL_A => Http::sequence()
                ->push(['error' => 'down'], 503)
                ->push($this->jsonResponse('A recovered.')),
            self::MODEL_B => Http::response(['error' => 'down'], 503),
        ]);
        $client = $this->judgeChain(['model-a', 'model-b']);

        try {
            $this->ask($client);
        } catch (RuntimeException) {
            // both marked down
        }

        $this->travel(1)->seconds();

        $this->assertSame('A recovered.', $this->ask($client), 'A request must still probe a model rather than fail with no attempt.');
        $this->assertSame(1, $this->sentTo('model-b'));
    }

    public function test_each_profile_walks_its_own_list(): void
    {
        config([
            'services.gemini.judge_models' => ['model-a'],
            'services.gemini.generate_models' => ['model-b'],
        ]);
        Http::fake([
            self::MODEL_A => Http::response($this->jsonResponse('judge')),
            self::MODEL_B => Http::response($this->jsonResponse('generate')),
        ]);
        $client = new GeminiClient;

        $this->assertSame('judge', $this->ask($client));
        $this->assertSame('generate', $this->ask($client, GeminiClient::PROFILE_GENERATE));
    }

    public function test_without_chain_lists_the_legacy_model_and_fallback_are_used(): void
    {
        config([
            'services.gemini.judge_models' => [],
            'services.gemini.model' => 'model-a',
            'services.gemini.fallback_model' => 'model-b',
        ]);
        Http::fake([
            self::MODEL_A => Http::response(['error' => 'down'], 503),
            self::MODEL_B => Http::response($this->jsonResponse('Hello from B.')),
        ]);

        $this->assertSame('Hello from B.', $this->ask(new GeminiClient));
    }

    public function test_a_fallback_equal_to_the_primary_is_not_tried_twice(): void
    {
        config([
            'services.gemini.judge_models' => [],
            'services.gemini.model' => 'model-a',
            'services.gemini.fallback_model' => 'model-a',
        ]);
        Http::fake([self::MODEL_A => Http::response(['error' => 'down'], 503)]);

        try {
            $this->ask(new GeminiClient);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(1, $this->sentTo('model-a'));
    }

    public function test_thinking_level_is_sent_only_for_entries_that_carry_one(): void
    {
        Http::fake([
            self::MODEL_A => Http::response(['error' => 'down'], 503),
            self::MODEL_B => Http::response($this->jsonResponse('plain model')),
        ]);

        $this->ask($this->judgeChain(['model-a:minimal', 'model-b']));

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'model-a')
            && $request['generationConfig']['thinkingConfig'] === ['thinkingLevel' => 'minimal']
            && $request['generationConfig']['responseSchema'] === self::SCHEMA);
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'model-b')
            && ! isset($request['generationConfig']['thinkingConfig']));
    }

    public function test_missing_api_key_throws_without_any_http_call(): void
    {
        config(['services.gemini.api_key' => '']);
        Http::fake();

        try {
            $this->ask($this->judgeChain(['model-a']));
            $this->fail('Expected a RuntimeException when no API key is configured.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('GEMINI_API_KEY is not set', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_unusable_model_output_is_a_runtime_exception_that_does_not_echo_the_output(): void
    {
        Http::fake([self::MODEL_A => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'not json at all, very secret']]]]]])]);

        try {
            $this->ask($this->judgeChain(['model-a']));
            $this->fail('Expected invalid JSON to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertSame('Gemini returned invalid JSON.', $e->getMessage());
        }
    }
}
