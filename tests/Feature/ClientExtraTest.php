<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use SamuelTerra\VolpaMail\Client\VolpaMailClient;
use SamuelTerra\VolpaMail\Enums\EmailStatus;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\Facades\VolpaMail;
use SamuelTerra\VolpaMail\Transport\VolpaMailTransport;

it('consulta o status de um e-mail por id', function () {
    Http::fake([
        '*/emails/eml_9' => Http::response(['id' => 'eml_9', 'status' => 'delivered'], 200),
    ]);

    $sent = VolpaMail::emails()->get('eml_9');

    expect($sent->id)->toBe('eml_9')
        ->and($sent->status)->toBe(EmailStatus::Delivered);

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/emails/eml_9') && $r->method() === 'GET');
});

it('lança exceção quando a API key não está configurada', function () {
    config()->set('volpa-mail.api_key', '');
    app()->forgetInstance(VolpaMailClient::class); // recria o singleton com a config nova

    VolpaMail::emails()->get('eml_1');
})->throws(VolpaMailException::class, 'VOLPA_MAIL_API_KEY');

it('expõe status e errors em falha sem campo message', function () {
    Http::fake([
        '*/emails' => Http::response(['errors' => ['to' => ['obrigatório']]], 500),
    ]);

    try {
        VolpaMail::emails()->send(['from' => 'a@b.test', 'to' => 'c@d.test', 'text' => 'x']);
        test()->fail('deveria ter lançado VolpaMailException');
    } catch (VolpaMailException $e) {
        expect($e->status)->toBe(500)
            ->and($e->errors)->toBe(['to' => ['obrigatório']])
            ->and($e->getMessage())->toContain('status 500');
    }
});

it('o transport se identifica como volpa-mail', function () {
    $transport = new VolpaMailTransport(app(VolpaMailClient::class));

    expect((string) $transport)->toBe('volpa-mail');
});
