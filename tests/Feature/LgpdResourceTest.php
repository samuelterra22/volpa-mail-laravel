<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\LgpdResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\LgpdErasure;
use SamuelTerra\VolpaMail\Data\LgpdExport;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

function makeLgpdClient(): VolpaMailClient
{
    return new VolpaMailClient(
        http: app(HttpFactory::class),
        apiKey: 'test-key',
        baseUrl: 'https://api.mail.volpa.test/v1',
        timeout: 5,
        retry: ['times' => 1, 'sleep' => 0],
    );
}

it('exports personal data and returns LgpdExport DTO', function () {
    Http::fake([
        '*/lgpd/export' => Http::response([
            'tenant' => 'acme',
            'email' => 'alice@example.com',
            'exported_at' => '2026-06-22T00:00:00Z',
            'contact' => [
                'id' => 'cnt_1',
                'email' => 'alice@example.com',
                'first_name' => 'Alice',
                'status' => 'active',
            ],
            'emails' => [
                ['id' => 'em_1', 'subject' => 'Welcome', 'sent_at' => '2026-01-01T00:00:00Z'],
            ],
            'events' => [
                ['type' => 'open', 'occurred_at' => '2026-01-02T00:00:00Z'],
            ],
            'suppressions' => [],
            'unsubscribe_links' => [
                ['list_id' => 'lst_1', 'created_at' => '2026-01-01T00:00:00Z'],
            ],
        ], 200),
    ]);

    $resource = new LgpdResource(makeLgpdClient());
    $export = $resource->export('alice@example.com');

    expect($export)->toBeInstanceOf(LgpdExport::class)
        ->and($export->tenant)->toBe('acme')
        ->and($export->email)->toBe('alice@example.com')
        ->and($export->exportedAt)->toBe('2026-06-22T00:00:00Z')
        ->and($export->contact)->toBeArray()
        ->and($export->contact['id'])->toBe('cnt_1')
        ->and($export->emails)->toBeArray()
        ->and($export->events)->toBeArray()
        ->and($export->suppressions)->toBe([])
        ->and($export->unsubscribeLinks)->toBeArray()
        ->and($export->unsubscribeLinks[0]['list_id'])->toBe('lst_1');
});

it('sends correct method, path, and body for export', function () {
    Http::fake([
        '*/lgpd/export' => Http::response([
            'tenant' => 't',
            'email' => 'alice@example.com',
            'exported_at' => null,
            'contact' => null,
            'emails' => [],
            'events' => [],
            'suppressions' => [],
            'unsubscribe_links' => [],
        ], 200),
    ]);

    $resource = new LgpdResource(makeLgpdClient());
    $resource->export('alice@example.com');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), 'lgpd/export')
            && $request->data()['email'] === 'alice@example.com';
    });
});

it('erases personal data and returns LgpdErasure DTO', function () {
    Http::fake([
        '*/lgpd/erasure' => Http::response([
            'erased' => true,
            'tenant' => 'acme',
            'email' => 'bob@example.com',
            'stats' => [
                'contact' => 1,
                'memberships' => 3,
                'unsubscribe_links' => 2,
                'events' => 10,
            ],
        ], 200),
    ]);

    $resource = new LgpdResource(makeLgpdClient());
    $erasure = $resource->erase('bob@example.com');

    expect($erasure)->toBeInstanceOf(LgpdErasure::class)
        ->and($erasure->erased)->toBeTrue()
        ->and($erasure->tenant)->toBe('acme')
        ->and($erasure->email)->toBe('bob@example.com')
        ->and($erasure->stats)->toBe([
            'contact' => 1,
            'memberships' => 3,
            'unsubscribe_links' => 2,
            'events' => 10,
        ]);
});

it('sends correct method, path, and body for erasure', function () {
    Http::fake([
        '*/lgpd/erasure' => Http::response([
            'erased' => true,
            'tenant' => 't',
            'email' => 'bob@example.com',
            'stats' => [],
        ], 200),
    ]);

    $resource = new LgpdResource(makeLgpdClient());
    $resource->erase('bob@example.com');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), 'lgpd/erasure')
            && $request->data()['email'] === 'bob@example.com';
    });
});

it('throws VolpaMailException on 422 for export', function () {
    Http::fake([
        '*/lgpd/export' => Http::response(['message' => 'Unprocessable Entity'], 422),
    ]);

    $resource = new LgpdResource(makeLgpdClient());
    $resource->export('bad@example.com');
})->throws(VolpaMailException::class);

it('LgpdExport::fromArray maps null contact and defaults empty arrays', function () {
    $export = LgpdExport::fromArray([
        'tenant' => 'x',
        'email' => 'x@x.test',
    ]);

    expect($export->exportedAt)->toBeNull()
        ->and($export->contact)->toBeNull()
        ->and($export->emails)->toBe([])
        ->and($export->events)->toBe([])
        ->and($export->suppressions)->toBe([])
        ->and($export->unsubscribeLinks)->toBe([]);
});

it('LgpdErasure::fromArray defaults erased to false and stats to empty', function () {
    $erasure = LgpdErasure::fromArray([
        'tenant' => 'x',
        'email' => 'x@x.test',
    ]);

    expect($erasure->erased)->toBeFalse()
        ->and($erasure->stats)->toBe([]);
});
