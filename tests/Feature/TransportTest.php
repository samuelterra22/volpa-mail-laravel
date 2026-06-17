<?php

declare(strict_types=1);

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class WelcomeMail extends Mailable
{
    public function build(): self
    {
        return $this->subject('Bem-vindo ao Volpa')
            ->html('<h1>Olá!</h1>');
    }
}

it('envia através do mailer volpa-mail', function () {
    Http::fake([
        '*/emails' => Http::response(['id' => 'eml_transport', 'status' => 'queued'], 202),
    ]);

    Mail::to('cliente@gmail.com')->send(new WelcomeMail());

    Http::assertSent(function ($request) {
        return str_ends_with($request->url(), '/emails')
            && $request->hasHeader('X-API-Key', 'test-key')
            && $request['subject'] === 'Bem-vindo ao Volpa'
            && $request['to'][0]['email'] === 'cliente@gmail.com'
            && str_contains($request['html'], 'Olá!');
    });
});
