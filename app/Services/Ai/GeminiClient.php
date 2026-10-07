<?php

namespace App\Services\Ai;

use App\Services\Ai\Concerns\UsesOutboundProxy;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GeminiClient
{
    use UsesOutboundProxy;

    /** Grading a learner's answer: quality first, the learner is waiting. */
    public const PROFILE_JUDGE = 'judge';

    /** Generating content (review practices): not time-critical, kept off the judge's quota. */
    public const PROFILE_GENERATE = 'generate';

    private const PROVIDER = 'gemini';

    protected string $apiKey;

    /**
     * Set only when the caller pinned models in the constructor (ad-hoc
     * probes such as the ops diagnostic). A pinned chain ignores and never
     * touches the shared "unavailable" memory.
     *
     * @var list<array{model: string, thinking_level: ?string}>|null
     */
    protected ?array $pinnedChain;

    protected AiModelChain $chain;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $fallbackModel = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.gemini.api_key');
        $this->chain = new AiModelChain;

        $this->pinnedChain = ($model !== null || $fallbackModel !== null)
            ? AiModelChain::parseChain([
                $model ?? (string) config('services.gemini.model'),
                $fallbackModel ?? (string) config('services.gemini.fallback_model'),
            ])
            : null;
    }

    /**
     * Ask Gemini for a JSON object matching the given response schema
     * (Gemini's structured output subset of the OpenAPI schema format).
     *
     * Walks the profile's ordered model chain: a model known to be exhausted
     * or down is skipped without a request; otherwise one attempt is made,
     * and a failure that is the model's own (quota, retired, 5xx, timeout)
     * marks it unavailable for a while and moves on to the next. The walk is
     * bounded (services.gemini.max_attempts / total_budget) so a learner
     * never waits long on a hard outage.
     *
     * Every failure surfaces as a RuntimeException with no key and no
     * provider body in its message (callers such as SessionController catch
     * RuntimeException only); the original is kept as the previous exception.
     *
     * @param  array<string, mixed>  $schema
     * @param  string  $profile  PROFILE_JUDGE (default) or PROFILE_GENERATE — which model chain to walk.
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt, array $schema, string $profile = self::PROFILE_JUDGE): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not set. Add it to .env before generating AI content.');
        }

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];

        try {
            $text = $this->chain->walk(
                provider: self::PROVIDER,
                logLabel: 'GeminiClient',
                chain: $this->configuredChain($profile),
                attempt: fn (array $entry, int $timeout) => $this->attempt($entry, $payload, $timeout),
                limits: [
                    'max_attempts' => (int) config('services.gemini.max_attempts', 3),
                    'budget' => (int) config('services.gemini.total_budget', 20),
                    'attempt_timeout' => (int) config('services.gemini.attempt_timeout', 10),
                ],
                tracked: $this->pinnedChain === null,
                logContext: ['profile' => $profile],
                chainName: "Gemini {$profile}",
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Gemini request failed on every model.', previous: $e);
        }

        if (! is_string($text)) {
            throw new RuntimeException('Gemini returned no content.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $data;
    }

    /**
     * The profile's full configured chain, best first, with the legacy
     * model + fallback pair standing in when no list is configured.
     *
     * @return list<array{model: string, thinking_level: ?string}>
     */
    public function configuredChain(string $profile): array
    {
        if ($this->pinnedChain !== null) {
            return $this->pinnedChain;
        }

        $configured = AiModelChain::parseChain((array) config("services.gemini.{$profile}_models", []));

        if ($configured !== []) {
            return $configured;
        }

        return AiModelChain::parseChain([
            (string) config('services.gemini.model'),
            (string) config('services.gemini.fallback_model'),
        ]);
    }

    /**
     * Whether Google still serves this model: true/false for a definite
     * answer (200 / 404 — retired models answer 404), null when the check
     * itself failed. Costs no generation quota.
     */
    public function modelExists(string $model): ?bool
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}";

        try {
            $response = $this->withOutboundProxy(
                Http::withHeader('x-goog-api-key', $this->apiKey)->timeout(10),
                $url,
            )->get($this->outboundUrl($url));
        } catch (Throwable) {
            return null;
        }

        return match (true) {
            $response->successful() => true,
            $response->status() === 404 => false,
            default => null,
        };
    }

    /**
     * One timeout-bounded attempt against a single model. Left to throw on
     * failure — the chain decides whether to try the next model.
     *
     * @param  array{model: string, thinking_level: ?string}  $entry
     * @param  array<string, mixed>  $payload
     */
    protected function attempt(array $entry, array $payload, int $timeoutSeconds): ?string
    {
        if ($entry['thinking_level'] !== null) {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingLevel' => $entry['thinking_level']];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$entry['model']}:generateContent";

        // 1 attempt per model: the next model in the chain IS the retry.
        $response = $this->withOutboundProxy(
            Http::timeout($timeoutSeconds)->withHeader('x-goog-api-key', $this->apiKey),
            $url,
        )
            ->post($this->outboundUrl($url), $payload)
            ->throw();

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? $text : null;
    }
}
