<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

// ---------------------------------------------------------------------------
// list()
// ---------------------------------------------------------------------------

it('lista webhooks registrados', function () {
    Http::fake([
        '*/webhooks' => Http::response([
            'data' => [
                [
                    'id' => 'wh_1',
                    'url' => 'https://app.example.com/webhooks/volpa',
                    'description' => 'Prod handler',
                    'events' => ['email.delivered', 'email.bounced'],
                    'is_active' => true,
                    'consecutive_failures' => 0,
                    'last_success_at' => '2026-06-20T09:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $list = VolpaMail::webhooks()->list();

    expect($list)->toBeArray()
        ->toHaveCount(1)
        ->and($list[0]['id'])->toBe('wh_1')
        ->and($list[0]['events'])->toBe(['email.delivered', 'email.bounced']);

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/webhooks') && $r->method() === 'GET');
});

it('retorna lista vazia quando não há webhooks', function () {
    Http::fake(['*/webhooks' => Http::response(['data' => []], 200)]);

    expect(VolpaMail::webhooks()->list())->toBe([]);
});

// ---------------------------------------------------------------------------
// create()
// ---------------------------------------------------------------------------

it('cria um novo webhook endpoint', function () {
    Http::fake([
        '*/webhooks' => Http::response([
            'id' => 'wh_new',
            'url' => 'https://app.example.com/webhooks/volpa',
            'events' => ['email.delivered'],
            'secret' => 'whsec_abc123',
            'created_at' => '2026-06-20T10:00:00Z',
        ], 201),
    ]);

    $result = VolpaMail::webhooks()->create([
        'url' => 'https://app.example.com/webhooks/volpa',
        'events' => ['email.delivered'],
        'description' => 'Main handler',
    ]);

    expect($result['id'])->toBe('wh_new')
        ->and($result['secret'])->toBe('whsec_abc123');

    Http::assertSent(function ($r) {
        return str_ends_with($r->url(), '/webhooks')
            && $r->method() === 'POST'
            && $r['url'] === 'https://app.example.com/webhooks/volpa'
            && $r['events'] === ['email.delivered'];
    });
});

it('lança exceção ao criar webhook com resposta de erro', function () {
    Http::fake([
        '*/webhooks' => Http::response(['message' => 'The url field is required.', 'errors' => ['url' => ['The url field is required.']]], 422),
    ]);

    VolpaMail::webhooks()->create(['events' => ['email.delivered']]);
})->throws(VolpaMailException::class);

// ---------------------------------------------------------------------------
// get()
// ---------------------------------------------------------------------------

it('retorna detalhes de um webhook pelo id', function () {
    Http::fake([
        '*/webhooks/wh_1' => Http::response([
            'id' => 'wh_1',
            'url' => 'https://app.example.com/webhooks/volpa',
            'events' => ['*'],
            'is_active' => true,
            'total_deliveries' => 42,
            'last_failure_at' => null,
        ], 200),
    ]);

    $result = VolpaMail::webhooks()->get('wh_1');

    expect($result['id'])->toBe('wh_1')
        ->and($result['total_deliveries'])->toBe(42);

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/webhooks/wh_1') && $r->method() === 'GET');
});

it('lança exceção ao buscar webhook inexistente', function () {
    Http::fake([
        '*/webhooks/wh_999' => Http::response(['message' => 'Not found.'], 404),
    ]);

    VolpaMail::webhooks()->get('wh_999');
})->throws(VolpaMailException::class);

// ---------------------------------------------------------------------------
// delete()
// ---------------------------------------------------------------------------

it('deleta um webhook endpoint', function () {
    Http::fake([
        '*/webhooks/wh_1' => Http::response(null, 204),
    ]);

    // delete() returns void — just assert it does not throw
    VolpaMail::webhooks()->delete('wh_1');

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/webhooks/wh_1') && $r->method() === 'DELETE');
});

it('lança exceção ao deletar webhook inexistente', function () {
    Http::fake([
        '*/webhooks/wh_999' => Http::response(['message' => 'Not found.'], 404),
    ]);

    VolpaMail::webhooks()->delete('wh_999');
})->throws(VolpaMailException::class);

// ---------------------------------------------------------------------------
// test()
// ---------------------------------------------------------------------------

it('dispara um evento de teste para o webhook', function () {
    Http::fake([
        '*/webhooks/wh_1/test' => Http::response(['queued' => true], 202),
    ]);

    $result = VolpaMail::webhooks()->test('wh_1');

    expect($result['queued'])->toBeTrue();

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/webhooks/wh_1/test') && $r->method() === 'POST');
});

it('lança exceção ao testar webhook inexistente', function () {
    Http::fake([
        '*/webhooks/wh_999/test' => Http::response(['message' => 'Not found.'], 404),
    ]);

    VolpaMail::webhooks()->test('wh_999');
})->throws(VolpaMailException::class);
