<?php

declare(strict_types=1);

use SamuelTerra\VolpaMail\Data\Address;
use SamuelTerra\VolpaMail\Data\Attachment;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Data\SentEmail;
use SamuelTerra\VolpaMail\Enums\EmailStatus;
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

it('monta Address a partir de Symfony com e sem nome', function () {
    $withName = Address::fromSymfony(new SymfonyAddress('a@b.test', 'Fulano'));
    expect($withName->email)->toBe('a@b.test')
        ->and($withName->name)->toBe('Fulano')
        ->and($withName->toArray())->toBe(['email' => 'a@b.test', 'name' => 'Fulano']);

    $noName = Address::fromSymfony(new SymfonyAddress('c@d.test'));
    expect($noName->name)->toBeNull()
        ->and($noName->toArray())->toBe(['email' => 'c@d.test']);
});

it('monta Address a partir de array', function () {
    $a = Address::fromArray(['email' => 'x@y.test', 'name' => 'X']);
    expect($a->email)->toBe('x@y.test')->and($a->name)->toBe('X');
});

it('cria Attachment a partir de caminho e serializa', function () {
    $path = tempnam(sys_get_temp_dir(), 'vml');
    file_put_contents($path, 'conteudo-do-arquivo');

    $att = Attachment::fromPath($path, 'doc.txt', 'text/plain');

    expect($att->filename)->toBe('doc.txt')
        ->and($att->contentType)->toBe('text/plain')
        ->and(base64_decode($att->content))->toBe('conteudo-do-arquivo')
        ->and($att->toArray())->toBe([
            'filename' => 'doc.txt',
            'content' => base64_encode('conteudo-do-arquivo'),
            'content_type' => 'text/plain',
        ]);

    unlink($path);
});

it('infere filename e content-type no Attachment::fromPath', function () {
    $path = tempnam(sys_get_temp_dir(), 'vml');
    file_put_contents($path, 'x');

    $att = Attachment::fromPath($path);

    expect($att->filename)->toBe(basename($path))
        ->and($att->contentType)->toBeString();

    unlink($path);
});

it('SendEmailData aceita destinatários como string, assoc e lista', function () {
    $data = SendEmailData::fromArray([
        'from' => ['email' => 'from@v.test', 'name' => 'From'],
        'to' => 'one@v.test',
        'cc' => ['email' => 'cc@v.test'],
        'bcc' => [['email' => 'b1@v.test'], 'b2@v.test'],
        'subject' => 'Assunto',
        'attachments' => [
            ['filename' => 'a.pdf', 'content' => base64_encode('z'), 'content_type' => 'application/pdf'],
            ['filename' => 'b.bin', 'content' => base64_encode('w')], // sem content_type → default
        ],
    ]);

    $arr = $data->toArray();

    expect($arr['to'][0]['email'])->toBe('one@v.test')
        ->and($arr['cc'][0]['email'])->toBe('cc@v.test')
        ->and($arr['bcc'][0]['email'])->toBe('b1@v.test')
        ->and($arr['bcc'][1]['email'])->toBe('b2@v.test')
        ->and($arr['attachments'][0]['content_type'])->toBe('application/pdf')
        ->and($arr['attachments'][1]['content_type'])->toBe('application/octet-stream')
        ->and($arr)->not->toHaveKey('html'); // array_filter remove campos vazios
});

it('SendEmailData exige from e to', function () {
    expect(fn () => SendEmailData::fromArray(['to' => 'x@y.test']))
        ->toThrow(VolpaMailException::class);

    expect(fn () => SendEmailData::fromArray(['from' => 'x@y.test']))
        ->toThrow(VolpaMailException::class);
});

it('SendEmailData::fromSymfonyEmail converte anexos, cc, bcc e reply-to', function () {
    $email = (new SymfonyEmail)
        ->from(new SymfonyAddress('from@v.test', 'From'))
        ->to('to@v.test')
        ->cc('cc@v.test')
        ->bcc('bcc@v.test')
        ->replyTo('reply@v.test')
        ->subject('Assunto')
        ->text('corpo txt')
        ->html('<b>oi</b>');
    $email->attach('conteudo-anexo', 'arquivo.txt', 'text/plain');

    $arr = SendEmailData::fromSymfonyEmail($email)->toArray();

    expect($arr['from']['email'])->toBe('from@v.test')
        ->and($arr['cc'][0]['email'])->toBe('cc@v.test')
        ->and($arr['bcc'][0]['email'])->toBe('bcc@v.test')
        ->and($arr['reply_to'][0]['email'])->toBe('reply@v.test')
        ->and($arr['text'])->toBe('corpo txt')
        ->and($arr['html'])->toBe('<b>oi</b>')
        ->and($arr['attachments'][0]['filename'])->toBe('arquivo.txt')
        ->and(base64_decode($arr['attachments'][0]['content']))->toBe('conteudo-anexo');
});

it('SendEmailData::fromSymfonyEmail exige remetente', function () {
    $email = (new SymfonyEmail)->to('to@v.test')->subject('x')->text('y');

    expect(fn () => SendEmailData::fromSymfonyEmail($email))
        ->toThrow(VolpaMailException::class);
});

it('SentEmail faz fallback de status desconhecido para Pending', function () {
    $sent = SentEmail::fromArray(['id' => 'eml_1', 'status' => 'marciano']);

    expect($sent->status)->toBe(EmailStatus::Pending)
        ->and($sent->id)->toBe('eml_1');
});

it('EmailStatus identifica estados terminais', function () {
    expect(EmailStatus::Delivered->isTerminal())->toBeTrue()
        ->and(EmailStatus::Bounced->isTerminal())->toBeTrue()
        ->and(EmailStatus::Failed->isTerminal())->toBeTrue()
        ->and(EmailStatus::Complained->isTerminal())->toBeTrue()
        ->and(EmailStatus::Pending->isTerminal())->toBeFalse()
        ->and(EmailStatus::Queued->isTerminal())->toBeFalse()
        ->and(EmailStatus::Sent->isTerminal())->toBeFalse()
        ->and(EmailStatus::Deferred->isTerminal())->toBeFalse();
});
