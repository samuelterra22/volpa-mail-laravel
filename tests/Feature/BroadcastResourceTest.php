<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\Resources\BroadcastResource;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Data\Broadcast;
use SamuelTerra\VolpaMail\Enums\BroadcastStatus;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Full broadcast shape as returned by GET /broadcasts/{id} */
function fullBroadcast(string $id = 'brc_1'): array
{
    return [
        'id' => $id,
        'name' => 'June Newsletter',
        'subject' => "What's new in June",
        'status' => 'draft',
        'scheduled_at' => null,
        'started_at' => null,
        'completed_at' => null,
        'total_recipients' => 500,
        'total_sent' => 0,
        'total_delivered' => 0,
        'total_failed' => 0,
    ];
}

/** Partial broadcast shape returned by GET /broadcasts (list endpoint) */
function partialBroadcast(string $id = 'brc_2'): array
{
    return [
        'id' => $id,
        'name' => 'May Campaign',
        'subject' => 'May updates',
        'status' => 'sent',
        'total_recipients' => 1000,
        'total_sent' => 990,
        'started_at' => '2026-05-01T10:00:00Z',
        'completed_at' => '2026-05-01T10:15:00Z',
    ];
}

/** Build a real VolpaMailClient using the app singleton (config set by TestCase). */
function makeBroadcastResource(): BroadcastResource
{
    return new BroadcastResource(app(VolpaMailClient::class));
}

// ---------------------------------------------------------------------------
// list()
// ---------------------------------------------------------------------------

it('lists broadcasts and returns list of Broadcast DTOs', function () {
    Http::fake([
        '*/broadcasts' => Http::response([
            'data' => [partialBroadcast('brc_2'), partialBroadcast('brc_3')],
        ], 200),
    ]);

    $results = makeBroadcastResource()->list();

    expect($results)->toBeArray()->toHaveCount(2);

    $first = $results[0];
    expect($first)->toBeInstanceOf(Broadcast::class)
        ->and($first->id)->toBe('brc_2')
        ->and($first->name)->toBe('May Campaign')
        ->and($first->status)->toBe(BroadcastStatus::Sent)
        ->and($first->totalSent)->toBe(990)
        ->and($first->scheduledAt)->toBeNull()       // omitted in list shape
        ->and($first->totalDelivered)->toBeNull();    // omitted in list shape

    Http::assertSent(fn ($r) => str_ends_with(rtrim($r->url(), '/'), '/broadcasts') && $r->method() === 'GET');
});

it('returns an empty list when data is empty', function () {
    Http::fake(['*/broadcasts' => Http::response(['data' => []], 200)]);

    $results = makeBroadcastResource()->list();

    expect($results)->toBeArray()->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// create()
// ---------------------------------------------------------------------------

it('creates a broadcast and returns a Broadcast DTO', function () {
    Http::fake([
        '*/broadcasts' => Http::response(fullBroadcast('brc_new'), 201),
    ]);

    $broadcast = makeBroadcastResource()->create([
        'name' => 'June Newsletter',
        'sender_id' => 'snd_abc',
        'subject' => "What's new in June",
        'html_body' => '<h1>Hello</h1>',
    ]);

    expect($broadcast)->toBeInstanceOf(Broadcast::class)
        ->and($broadcast->id)->toBe('brc_new')
        ->and($broadcast->status)->toBe(BroadcastStatus::Draft)
        ->and($broadcast->totalRecipients)->toBe(500);

    Http::assertSent(function ($r) {
        return str_ends_with(rtrim($r->url(), '/'), '/broadcasts')
            && $r->method() === 'POST'
            && $r['name'] === 'June Newsletter'
            && $r['sender_id'] === 'snd_abc';
    });
});

it('creates a scheduled broadcast when scheduled_at is provided', function () {
    $shape = array_merge(fullBroadcast('brc_sched'), [
        'status' => 'scheduled',
        'scheduled_at' => '2026-07-01T09:00:00Z',
    ]);

    Http::fake(['*/broadcasts' => Http::response($shape, 201)]);

    $broadcast = makeBroadcastResource()->create([
        'name' => 'Summer Campaign',
        'sender_id' => 'snd_abc',
        'subject' => 'Summer deals',
        'scheduled_at' => '2026-07-01T09:00:00Z',
    ]);

    expect($broadcast->status)->toBe(BroadcastStatus::Scheduled)
        ->and($broadcast->scheduledAt)->toBe('2026-07-01T09:00:00Z');
});

// ---------------------------------------------------------------------------
// get()
// ---------------------------------------------------------------------------

it('fetches a single broadcast by id', function () {
    Http::fake([
        '*/broadcasts/brc_1' => Http::response(fullBroadcast('brc_1'), 200),
    ]);

    $broadcast = makeBroadcastResource()->get('brc_1');

    expect($broadcast)->toBeInstanceOf(Broadcast::class)
        ->and($broadcast->id)->toBe('brc_1')
        ->and($broadcast->totalDelivered)->toBe(0)
        ->and($broadcast->totalFailed)->toBe(0);

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/broadcasts/brc_1') && $r->method() === 'GET');
});

// ---------------------------------------------------------------------------
// send()
// ---------------------------------------------------------------------------

it('triggers sending and returns the 202 payload as array', function () {
    Http::fake([
        '*/broadcasts/brc_1/send' => Http::response([
            'id' => 'brc_1',
            'status' => 'sending',
            'total_queued' => 500,
        ], 202),
    ]);

    $result = makeBroadcastResource()->send('brc_1');

    expect($result)->toBeArray()
        ->and($result['id'])->toBe('brc_1')
        ->and($result['status'])->toBe('sending')
        ->and($result['total_queued'])->toBe(500);

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/broadcasts/brc_1/send') && $r->method() === 'POST');
});

// ---------------------------------------------------------------------------
// cancel()
// ---------------------------------------------------------------------------

it('cancels a broadcast and returns a Broadcast DTO', function () {
    $shape = array_merge(fullBroadcast('brc_1'), ['status' => 'canceled']);

    Http::fake([
        '*/broadcasts/brc_1/cancel' => Http::response($shape, 200),
    ]);

    $broadcast = makeBroadcastResource()->cancel('brc_1');

    expect($broadcast)->toBeInstanceOf(Broadcast::class)
        ->and($broadcast->id)->toBe('brc_1')
        ->and($broadcast->status)->toBe(BroadcastStatus::Canceled)
        ->and($broadcast->status->isFinal())->toBeTrue();

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/broadcasts/brc_1/cancel') && $r->method() === 'POST');
});

// ---------------------------------------------------------------------------
// Error: broadcast_finalized → VolpaMailException
// ---------------------------------------------------------------------------

it('throws VolpaMailException with broadcast_finalized on send of finalized broadcast', function () {
    Http::fake([
        '*/broadcasts/brc_done/send' => Http::response([
            'message' => 'The broadcast has already been finalized.',
            'errors' => ['broadcast_finalized' => ['This broadcast has already been sent or canceled.']],
        ], 422),
    ]);

    makeBroadcastResource()->send('brc_done');
})->throws(VolpaMailException::class);

// ---------------------------------------------------------------------------
// BroadcastStatus helpers
// ---------------------------------------------------------------------------

it('BroadcastStatus::isFinal returns true for terminal states', function (BroadcastStatus $status, bool $expected) {
    expect($status->isFinal())->toBe($expected);
})->with([
    'draft is not final' => [BroadcastStatus::Draft, false],
    'scheduled is not final' => [BroadcastStatus::Scheduled, false],
    'sending is not final' => [BroadcastStatus::Sending, false],
    'sent is final' => [BroadcastStatus::Sent, true],
    'canceled is final' => [BroadcastStatus::Canceled, true],
    'failed is final' => [BroadcastStatus::Failed, true],
]);

// ---------------------------------------------------------------------------
// Broadcast::fromArray — tolerates partial shapes and unknown status
// ---------------------------------------------------------------------------

it('Broadcast::fromArray falls back to Draft for unknown status', function () {
    $broadcast = Broadcast::fromArray([
        'id' => 'brc_x',
        'name' => 'Test',
        'subject' => 'Hi',
        'status' => 'unknown_future_value',
    ]);

    expect($broadcast->status)->toBe(BroadcastStatus::Draft);
});

it('Broadcast::fromArray tolerates fully missing optional fields', function () {
    $broadcast = Broadcast::fromArray([
        'id' => 'brc_y',
        'name' => 'Minimal',
        'subject' => 'Sub',
        'status' => 'draft',
    ]);

    expect($broadcast->scheduledAt)->toBeNull()
        ->and($broadcast->startedAt)->toBeNull()
        ->and($broadcast->completedAt)->toBeNull()
        ->and($broadcast->totalRecipients)->toBeNull()
        ->and($broadcast->totalSent)->toBeNull()
        ->and($broadcast->totalDelivered)->toBeNull()
        ->and($broadcast->totalFailed)->toBeNull();
});
