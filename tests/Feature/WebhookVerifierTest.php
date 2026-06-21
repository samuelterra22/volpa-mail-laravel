<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use SamuelTerra\VolpaMail\Data\WebhookEvent;
use SamuelTerra\VolpaMail\Webhooks\WebhookVerifier;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a valid signature header for the given payload, secret and timestamp,
 * using the same HMAC formula the backend uses.
 */
function makeSignatureHeader(string $payload, string $secret, int $ts): string
{
    $hmac = hash_hmac('sha256', $ts.'.'.$payload, $secret);

    return "t={$ts},v1={$hmac}";
}

/**
 * A verifier whose clock is frozen at a fixed timestamp.
 * Overrides the protected now() hook instead of using global time().
 */
function frozenVerifier(int $now): WebhookVerifier
{
    return new class($now) extends WebhookVerifier
    {
        public function __construct(private readonly int $frozenNow) {}

        protected function now(): int
        {
            return $this->frozenNow;
        }
    };
}

// ---------------------------------------------------------------------------
// verify() — happy path
// ---------------------------------------------------------------------------

it('accepts a valid signature at the exact timestamp', function () {
    $payload = '{"type":"email.delivered","created":"2026-06-20T10:00:00Z","data":{}}';
    $secret = 'whsec_test_abc123';
    $now = 1_750_000_000;
    $header = makeSignatureHeader($payload, $secret, $now);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeTrue();
});

it('accepts a valid signature within tolerance', function () {
    $payload = '{"type":"email.bounced","created":"2026-06-20T10:00:00Z","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $ts = $now - 299; // 1 second inside the 300-second window
    $header = makeSignatureHeader($payload, $secret, $ts);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeTrue();
});

it('accepts a valid signature at exactly the tolerance boundary', function () {
    $payload = '{"type":"email.sent","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $ts = $now - 300; // exactly 300s — abs(now - ts) === tolerance → NOT > tolerance → allowed
    $header = makeSignatureHeader($payload, $secret, $ts);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeTrue();
});

// ---------------------------------------------------------------------------
// verify() — rejection cases
// ---------------------------------------------------------------------------

it('rejects an expired timestamp (outside tolerance)', function () {
    $payload = '{"type":"email.delivered","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $ts = $now - 301; // 1 second past the window
    $header = makeSignatureHeader($payload, $secret, $ts);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeFalse();
});

it('rejects a future timestamp outside tolerance', function () {
    $payload = '{"type":"email.delivered","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $ts = $now + 301;
    $header = makeSignatureHeader($payload, $secret, $ts);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeFalse();
});

it('rejects a tampered payload', function () {
    $payload = '{"type":"email.delivered","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $header = makeSignatureHeader($payload, $secret, $now);
    $tampered = '{"type":"email.delivered","data":{"injected":true}}';

    $verifier = frozenVerifier($now);

    expect($verifier->verify($tampered, $secret, $header))->toBeFalse();
});

it('rejects a wrong secret', function () {
    $payload = '{"type":"email.sent","data":{}}';
    $now = 1_750_000_000;
    $header = makeSignatureHeader($payload, 'correct-secret', $now);

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, 'wrong-secret', $header))->toBeFalse();
});

it('rejects a malformed header with no equals sign', function () {
    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', 'malformed-header'))->toBeFalse();
});

it('rejects an empty signature header', function () {
    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', ''))->toBeFalse();
});

it('rejects a header missing the v1 component', function () {
    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', 't=1750000000'))->toBeFalse();
});

it('rejects a header missing the t component', function () {
    $hmac = hash_hmac('sha256', '1750000000.{}', 'secret');

    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', "v1={$hmac}"))->toBeFalse();
});

it('rejects a header where t is not a digit string', function () {
    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', 't=abc,v1=deadbeef'))->toBeFalse();
});

it('skips malformed segments and still rejects (missing valid t)', function () {
    // Header has garbage segments plus a valid v1 but no valid t
    $hmac = hash_hmac('sha256', '1750000000.{}', 'secret');
    $header = "bad,  ,no_equals_here,v1={$hmac}";

    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verify('{}', 'secret', $header))->toBeFalse();
});

it('trims whitespace in header segments', function () {
    $payload = '{"type":"email.delivered","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $hmac = hash_hmac('sha256', "{$now}.{$payload}", $secret);
    // Add extra spaces around segments — the parser must trim them
    $header = "  t={$now}  ,  v1={$hmac}  ";

    $verifier = frozenVerifier($now);

    expect($verifier->verify($payload, $secret, $header))->toBeTrue();
});

// ---------------------------------------------------------------------------
// verifyRequest()
// ---------------------------------------------------------------------------

it('verifies a valid Illuminate Request', function () {
    $payload = '{"type":"email.clicked","data":{}}';
    $secret = 'whsec_req_test';
    $now = 1_750_000_000;
    $header = makeSignatureHeader($payload, $secret, $now);

    $request = Request::create(
        uri: '/webhooks',
        method: 'POST',
        content: $payload,
    );
    $request->headers->set('X-VolpaMail-Signature', $header);

    $verifier = frozenVerifier($now);

    expect($verifier->verifyRequest($request, $secret))->toBeTrue();
});

it('rejects a Request with a tampered body', function () {
    $original = '{"type":"email.clicked","data":{}}';
    $secret = 'secret';
    $now = 1_750_000_000;
    $header = makeSignatureHeader($original, $secret, $now);

    $request = Request::create(
        uri: '/webhooks',
        method: 'POST',
        content: '{"type":"email.clicked","data":{"extra":1}}',
    );
    $request->headers->set('X-VolpaMail-Signature', $header);

    $verifier = frozenVerifier($now);

    expect($verifier->verifyRequest($request, $secret))->toBeFalse();
});

it('rejects a Request with no signature header', function () {
    $request = Request::create(uri: '/webhooks', method: 'POST', content: '{}');

    $verifier = frozenVerifier(1_750_000_000);

    expect($verifier->verifyRequest($request, 'secret'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// parseEvent()
// ---------------------------------------------------------------------------

it('parses a full webhook event JSON', function () {
    $json = '{"type":"email.delivered","created":"2026-06-20T10:00:00Z","data":{"email_id":"eml_123"}}';
    $verifier = new WebhookVerifier;
    $event = $verifier->parseEvent($json);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->type)->toBe('email.delivered')
        ->and($event->created)->toBe('2026-06-20T10:00:00Z')
        ->and($event->data)->toBe(['email_id' => 'eml_123']);
});

it('throws JsonException for invalid JSON in parseEvent', function () {
    $verifier = new WebhookVerifier;
    $verifier->parseEvent('not-json');
})->throws(JsonException::class);

// ---------------------------------------------------------------------------
// WebhookEvent DTO
// ---------------------------------------------------------------------------

it('builds WebhookEvent from array with null created', function () {
    $event = WebhookEvent::fromArray(['type' => 'email.sent', 'data' => []]);

    expect($event->type)->toBe('email.sent')
        ->and($event->created)->toBeNull()
        ->and($event->data)->toBe([]);
});

it('builds WebhookEvent from JSON', function () {
    $event = WebhookEvent::fromJson('{"type":"domain.verified","created":"2026-01-01T00:00:00Z","data":{"domain":"volpa.com.br"}}');

    expect($event->type)->toBe('domain.verified')
        ->and($event->data['domain'])->toBe('volpa.com.br');
});
