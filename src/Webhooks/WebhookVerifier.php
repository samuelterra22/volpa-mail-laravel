<?php

declare(strict_types=1);

namespace SamuelTerra\VolpaMail\Webhooks;

use Illuminate\Http\Request;
use SamuelTerra\VolpaMail\Data\WebhookEvent;

/**
 * Verifies Volpa Mail webhook signatures (custom scheme — NOT Svix).
 *
 * Signature header format: `t=<unix_ts>,v1=<hex_hmac>`.
 * Signed string: `<unix_ts>.<raw JSON body>`.
 * Algorithm: HMAC-SHA256, hex output, compared with hash_equals.
 * Default tolerance: 300 seconds.
 *
 * The "current time" is resolved by {@see now()}, which can be overridden in a
 * subclass for deterministic testing. This avoids constructor clock injection
 * while keeping the class cleanly testable without mocking global functions.
 */
class WebhookVerifier
{
    /**
     * Verify a webhook delivery.
     *
     * Parses the header `t=<unix_ts>,v1=<hex>`, validates that the timestamp is
     * recent (within `$tolerance` seconds), recomputes HMAC-SHA256 over
     * `"<ts>.<payload>"`, and compares with `hash_equals`.
     *
     * Mirrors the backend implementation byte-for-byte:
     * ```php
     * foreach (explode(',', $signatureHeader) as $segment) {
     *     $segment = trim($segment);
     *     if ($segment === '' || !str_contains($segment, '=')) { continue; }
     *     [$key, $value] = explode('=', $segment, 2);
     *     $parts[$key] = $value;
     * }
     * if (!isset($parts['t'], $parts['v1']) || !ctype_digit($parts['t'])) { return false; }
     * $ts = (int) $parts['t'];
     * if (abs(time() - $ts) > $tolerance) { return false; }
     * $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
     * return hash_equals($expected, $parts['v1']);
     * ```
     *
     * @param  int  $tolerance  Max age in seconds (default 300).
     */
    public function verify(
        string $payload,
        string $secret,
        string $signatureHeader,
        int $tolerance = 300,
    ): bool {
        $parts = [];

        foreach (explode(',', $signatureHeader) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || ! str_contains($segment, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $segment, 2);
            $parts[$key] = $value;
        }

        if (! isset($parts['t'], $parts['v1']) || ! ctype_digit($parts['t'])) {
            return false;
        }

        $ts = (int) $parts['t'];

        if (abs($this->now() - $ts) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $ts.'.'.$payload, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    /**
     * Convenience wrapper that reads from an Illuminate Request.
     *
     * Reads the `X-VolpaMail-Signature` header and the raw request body,
     * then delegates to {@see verify()}.
     *
     * @param  int  $tolerance  Max age in seconds (default 300).
     */
    public function verifyRequest(Request $request, string $secret, int $tolerance = 300): bool
    {
        $header = $request->header('X-VolpaMail-Signature', '');

        return $this->verify(
            payload: $request->getContent(),
            secret: $secret,
            signatureHeader: (string) $header,
            tolerance: $tolerance,
        );
    }

    /**
     * Parse and decode a raw JSON payload into a typed {@see WebhookEvent}.
     *
     * @throws \JsonException If the payload is not valid JSON.
     */
    public function parseEvent(string $payload): WebhookEvent
    {
        return WebhookEvent::fromJson($payload);
    }

    /**
     * Returns the current Unix timestamp.
     *
     * Override in a subclass to inject a fixed clock for deterministic testing:
     * ```php
     * class FrozenVerifier extends WebhookVerifier {
     *     protected function now(): int { return 1_700_000_000; }
     * }
     * ```
     */
    protected function now(): int
    {
        return time();
    }
}
