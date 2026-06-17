# Volpa Mail para Laravel

[![tests](https://github.com/samuelterra/volpa-mail-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/samuelterra/volpa-mail-laravel/actions)
[![Packagist](https://img.shields.io/packagist/v/samuelterra/volpa-mail-laravel.svg)](https://packagist.org/packages/samuelterra/volpa-mail-laravel)

SDK e Mail Transport oficiais do **Volpa Mail** para Laravel. Envie e-mails
transacionais pela API do Volpa Mail usando o `Mail` nativo do Laravel ou o
SDK direto.

## Instalação

```bash
composer require samuelterra/volpa-mail-laravel
```

Publique a config (opcional):

```bash
php artisan vendor:publish --tag=volpa-mail-config
```

Configure o `.env`:

```dotenv
VOLPA_MAIL_API_KEY=vmk_xxxxxxxxxxxxxxxx
VOLPA_MAIL_BASE_URL=https://api.mail.volpa.com.br/v1
```

## Uso como Mailer do Laravel

Adicione o mailer em `config/mail.php`:

```php
'mailers' => [
    'volpa-mail' => [
        'transport' => 'volpa-mail',
    ],
],
```

Defina como padrão (`MAIL_MAILER=volpa-mail`) ou use sob demanda:

```php
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

Mail::mailer('volpa-mail')->to($user->email)->send(new WelcomeMail($user));
```

Todos os recursos de `Mailable` (markdown, anexos, cc/bcc, reply-to)
funcionam normalmente.

## Uso como SDK direto

```php
use SamuelTerra\VolpaMail\Facades\VolpaMail;

$sent = VolpaMail::emails()->send([
    'from' => ['email' => 'contato@athelier.com.br', 'name' => 'Athelier'],
    'to' => [['email' => 'cliente@gmail.com']],
    'subject' => 'Sua reserva foi confirmada!',
    'html' => '<h1>Obrigado!</h1>',
    'template_id' => 'reservation-confirmation',
    'variables' => ['name' => 'João'],
]);

echo $sent->id;      // eml_123
echo $sent->status->value; // queued
```

Ou com DTO tipado:

```php
use SamuelTerra\VolpaMail\Data\Address;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

VolpaMail::emails()->send(new SendEmailData(
    from: new Address('no-reply@volpa.com.br', 'Volpa'),
    to: [new Address('cliente@gmail.com')],
    subject: 'Olá',
    html: '<p>Conteúdo</p>',
));
```

Consultar status:

```php
$email = VolpaMail::emails()->get('eml_123');
```

## Testes

```bash
composer test
composer analyse   # PHPStan nível 8
composer format    # Pint
```

## Licença

MIT. Veja [LICENSE.md](LICENSE.md).
