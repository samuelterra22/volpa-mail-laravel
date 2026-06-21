<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\SuppressionResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Suppression;
use SamuelTerra\VolpaMail\Enums\SuppressionReason;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeSuppressionClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

// ---------------------------------------------------------------------------
// list()
// ---------------------------------------------------------------------------

it('lists suppressions and returns array of Suppression DTOs', function () {
    Http::fake([
        '*/suppressions*' => Http::response([
            'data' => [
                [
                    'id' => 'sup_1',
                    'email' => 'bounce@example.com',
                    'reason' => 'hard_bounce',
                    'source' => 'ses',
                    'created_at' => '2026-06-01T00:00:00Z',
                ],
                [
                    'id' => 'sup_2',
                    'email' => 'spam@example.com',
                    'reason' => 'complaint',
                    'source' => null,
                    'created_at' => '2026-06-02T00:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $items = $resource->list();

    expect($items)->toHaveCount(2)
        ->and($items[0])->toBeInstanceOf(Suppression::class)
        ->and($items[0]->id)->toBe('sup_1')
        ->and($items[0]->email)->toBe('bounce@example.com')
        ->and($items[0]->reason)->toBe(SuppressionReason::HardBounce)
        ->and($items[0]->source)->toBe('ses')
        ->and($items[1]->reason)->toBe(SuppressionReason::Complaint)
        ->and($items[1]->source)->toBeNull();
});

it('sends reason and limit filters as query parameters', function () {
    Http::fake([
        '*/suppressions*' => Http::response(['data' => []], 200),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->list(['reason' => 'hard_bounce', 'source' => 'ses', 'limit' => 25]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'reason=hard_bounce')
            && str_contains($request->url(), 'source=ses')
            && str_contains($request->url(), 'limit=25');
    });
});

it('list() omits null filters from query string', function () {
    Http::fake([
        '*/suppressions*' => Http::response(['data' => []], 200),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->list(['reason' => 'manual']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'reason=manual')
            && ! str_contains($request->url(), 'source=')
            && ! str_contains($request->url(), 'limit=');
    });
});

// ---------------------------------------------------------------------------
// create()
// ---------------------------------------------------------------------------

it('creates a suppression and returns Suppression DTO', function () {
    Http::fake([
        '*/suppressions' => Http::response([
            'id' => 'sup_new',
            'email' => 'new@example.com',
            'reason' => 'manual',
            'created_at' => '2026-06-20T10:00:00Z',
        ], 201),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $suppression = $resource->create('new@example.com', SuppressionReason::Manual);

    expect($suppression)->toBeInstanceOf(Suppression::class)
        ->and($suppression->id)->toBe('sup_new')
        ->and($suppression->email)->toBe('new@example.com')
        ->and($suppression->reason)->toBe(SuppressionReason::Manual)
        ->and($suppression->source)->toBeNull()
        ->and($suppression->createdAt)->toBe('2026-06-20T10:00:00Z');
});

it('sends correct payload when creating a suppression', function () {
    Http::fake([
        '*/suppressions' => Http::response([
            'id' => 's1', 'email' => 'x@x.test', 'reason' => 'hard_bounce',
        ], 201),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->create('x@x.test', SuppressionReason::HardBounce);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-API-Key', 'test-key')
            && $request['email'] === 'x@x.test'
            && $request['reason'] === 'hard_bounce';
    });
});

// ---------------------------------------------------------------------------
// get()
// ---------------------------------------------------------------------------

it('gets a single suppression by email address', function () {
    Http::fake([
        '*/suppressions/alice%40example.com' => Http::response([
            'id' => 'sup_abc',
            'email' => 'alice@example.com',
            'reason' => 'unsubscribe',
            'source' => 'user',
            'created_at' => '2026-05-01T00:00:00Z',
        ], 200),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $suppression = $resource->get('alice@example.com');

    expect($suppression)->toBeInstanceOf(Suppression::class)
        ->and($suppression->id)->toBe('sup_abc')
        ->and($suppression->email)->toBe('alice@example.com')
        ->and($suppression->reason)->toBe(SuppressionReason::Unsubscribe)
        ->and($suppression->source)->toBe('user');
});

it('throws VolpaMailException on 404 for suppression get', function () {
    Http::fake([
        '*/suppressions/*' => Http::response(['message' => 'not_found'], 404),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->get('missing@example.com');
})->throws(VolpaMailException::class, 'not_found');

// ---------------------------------------------------------------------------
// delete()
// ---------------------------------------------------------------------------

it('deletes a suppression by email and returns void', function () {
    Http::fake([
        '*/suppressions/*' => Http::response(null, 204),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $result = $resource->delete('gone@example.com');

    expect($result)->toBeNull();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), rawurlencode('gone@example.com'));
    });
});

it('throws VolpaMailException on 404 for suppression delete', function () {
    Http::fake([
        '*/suppressions/*' => Http::response(['message' => 'not_found'], 404),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->delete('absent@example.com');
})->throws(VolpaMailException::class, 'not_found');

// ---------------------------------------------------------------------------
// import()
// ---------------------------------------------------------------------------

it('imports a list of emails and returns imported count and reason', function () {
    Http::fake([
        '*/suppressions/import' => Http::response([
            'imported' => 3,
            'reason' => 'manual',
        ], 202),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $result = $resource->import(['a@x.test', 'b@x.test', 'c@x.test']);

    expect($result['imported'])->toBe(3)
        ->and($result['reason'])->toBe('manual');
});

it('import() sends the correct payload with custom reason', function () {
    Http::fake([
        '*/suppressions/import' => Http::response(['imported' => 2, 'reason' => 'hard_bounce'], 202),
    ]);

    $resource = new SuppressionResource(makeSuppressionClient());
    $resource->import(['a@x.test', 'b@x.test'], SuppressionReason::HardBounce);

    Http::assertSent(function ($request) {
        return $request['emails'] === ['a@x.test', 'b@x.test']
            && $request['reason'] === 'hard_bounce'
            && $request->hasHeader('X-API-Key', 'test-key');
    });
});

// ---------------------------------------------------------------------------
// Suppression::fromArray edge cases
// ---------------------------------------------------------------------------

it('Suppression::fromArray falls back to Manual for unknown reason', function () {
    $suppression = Suppression::fromArray([
        'id' => 'sup_x',
        'email' => 'x@x.test',
        'reason' => 'totally_unknown',
    ]);

    expect($suppression->reason)->toBe(SuppressionReason::Manual)
        ->and($suppression->source)->toBeNull()
        ->and($suppression->createdAt)->toBeNull();
});

it('Suppression::fromArray handles all reason enum values', function (string $value, SuppressionReason $expected) {
    $suppression = Suppression::fromArray(['id' => 'x', 'email' => 'x@x.test', 'reason' => $value]);

    expect($suppression->reason)->toBe($expected);
})->with([
    ['hard_bounce',          SuppressionReason::HardBounce],
    ['soft_bounce_repeated', SuppressionReason::SoftBounceRepeated],
    ['complaint',            SuppressionReason::Complaint],
    ['unsubscribe',          SuppressionReason::Unsubscribe],
    ['manual',               SuppressionReason::Manual],
    ['invalid_address',      SuppressionReason::InvalidAddress],
]);
