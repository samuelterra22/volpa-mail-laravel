<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Enums\EmailStatus;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

it('envia e-mail via SDK com array', function () {
    Http::fake([
        '*/emails' => Http::response([
            'id' => 'eml_123',
            'status' => 'queued',
            'created_at' => '2026-06-17T08:00:00Z',
        ], 202),
    ]);

    $sent = VolpaMail::emails()->send([
        'from' => ['email' => 'contato@athelier.com.br', 'name' => 'Athelier'],
        'to' => [['email' => 'cliente@gmail.com']],
        'subject' => 'Sua reserva foi confirmada!',
        'html' => '<h1>Obrigado!</h1>',
        'template_id' => 'reservation-confirmation',
        'variables' => ['name' => 'João'],
    ]);

    expect($sent->id)->toBe('eml_123')
        ->and($sent->status)->toBe(EmailStatus::Queued);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-API-Key', 'test-key')
            && $request['subject'] === 'Sua reserva foi confirmada!'
            && $request['from']['email'] === 'contato@athelier.com.br'
            && $request['variables']['name'] === 'João';
    });
});

it('aceita SendEmailData tipado', function () {
    Http::fake(['*/emails' => Http::response(['id' => 'eml_x', 'status' => 'sent'], 200)]);

    $sent = VolpaMail::emails()->send(SendEmailData::fromArray([
        'from' => 'no-reply@volpa.test',
        'to' => 'dest@volpa.test',
        'subject' => 'Olá',
        'text' => 'corpo',
    ]));

    expect($sent->id)->toBe('eml_x');
});

it('lança exceção em resposta de erro', function () {
    Http::fake(['*/emails' => Http::response(['message' => 'invalid recipient'], 422)]);

    VolpaMail::emails()->send([
        'from' => 'a@b.test',
        'to' => 'c@d.test',
        'subject' => 'x',
        'text' => 'y',
    ]);
})->throws(VolpaMailException::class, 'invalid recipient');

it('exige campos obrigatórios', function () {
    SendEmailData::fromArray(['to' => 'x@y.test']);
})->throws(VolpaMailException::class);
