<?php

namespace App\Services\Ai;

use App\Services\Ai\Concerns\UsesOutboundProxy;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    use UsesOutboundProxy;

    protected string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->model = (string) config('services.gemini.model');
    }

    /**
     * Ask Gemini for a JSON object matching the given response schema
     * (Gemini's structured output subset of the OpenAPI schema format).
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt, array $schema): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not set. Add it to .env before generating AI content.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $response = $this->withOutboundProxy(
            Http::timeout(60)->withHeader('x-goog-api-key', $this->apiKey),
            $url,
        )->post($this->outboundUrl($url), [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new RuntimeException('Gemini returned no content: '.$response->body());
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Gemini returned invalid JSON: '.$text);
        }

        return $data;
    }
}
