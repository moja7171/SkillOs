<?php

namespace App\Services\Ai\Concerns;

use Illuminate\Http\Client\PendingRequest;

/**
 * Routes an outbound Http call through services.ai_proxy.url when it's set (empty
 * by default — every call connects directly, unchanged from before this existed).
 * Gemini's API is unreachable directly from this project's production host
 * (Iran-hosted; Google blocks the region at its own edge, confirmed 2026-09-18):
 * the relay at that URL forwards whatever it's told to (via the X-Relay-Url
 * header) verbatim to the real destination and hands back the real response
 * untouched, so GeminiClient's existing ->json()/->throw() handling keeps
 * working unchanged — only the request's URL and one extra header change.
 * Same protocol as tools/ai-relay.py; ported from EnglishOS's identical setup
 * (App\Services\Concerns\UsesOutboundProxy there).
 */
trait UsesOutboundProxy
{
    protected function withOutboundProxy(PendingRequest $request, string $realUrl): PendingRequest
    {
        $relayUrl = (string) config('services.ai_proxy.url');

        if ($relayUrl === '') {
            return $request;
        }

        return $request->withHeaders([
            'X-Relay-Url' => $realUrl,
            'X-Relay-Auth' => (string) config('services.ai_proxy.secret'),
        ]);
    }

    /**
     * The URL a caller should actually send the request to — $realUrl itself when
     * no relay is configured, or the relay's own URL (which reads the real
     * destination back out of the X-Relay-Url header set by withOutboundProxy()
     * above) when one is.
     */
    protected function outboundUrl(string $realUrl): string
    {
        $relayUrl = (string) config('services.ai_proxy.url');

        return $relayUrl === '' ? $realUrl : $relayUrl;
    }
}
