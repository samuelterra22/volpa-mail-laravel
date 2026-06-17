# Volpa Mail for Laravel

[![tests](https://github.com/samuelterra22/volpa-mail-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/samuelterra22/volpa-mail-laravel/actions)
[![Packagist](https://img.shields.io/packagist/v/samuelterra22/volpa-mail-laravel.svg)](https://packagist.org/packages/samuelterra22/volpa-mail-laravel)

Official **Volpa Mail** SDK and Mail Transport for Laravel. Send transactional
emails through the Volpa Mail API using Laravel's native `Mail` or the SDK
directly.

## Installation

```bash
composer require samuelterra22/volpa-mail-laravel
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=volpa-mail-config
```

Configure your `.env`:

```dotenv
VOLPA_MAIL_API_KEY=vmk_xxxxxxxxxxxxxxxx
VOLPA_MAIL_BASE_URL=https://api.mail.volpa.com.br/v1
```

## Using as a Laravel Mailer

Add the mailer in `config/mail.php`:

```php
'mailers' => [
    'volpa-mail' => [
        'transport' => 'volpa-mail',
    ],
],
```

Set it as the default (`MAIL_MAILER=volpa-mail`) or use it on demand:

```php
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

Mail::mailer('volpa-mail')->to($user->email)->send(new WelcomeMail($user));
```

All `Mailable` features (markdown, attachments, cc/bcc, reply-to) work as
usual.

## Using as a direct SDK

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

Or with a typed DTO:

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

Check status:

```php
$email = VolpaMail::emails()->get('eml_123');
```

## Tests

```bash
composer test
composer analyse   # PHPStan level 8
composer format    # Pint
```

## Contributing — Conventional Commits & releases

This package is versioned **automatically**. When you push to `main`, the `CI`
workflow runs the tests; if they pass, the `Release` workflow reads the commit
messages, computes the next version (SemVer), and publishes the tag + GitHub
Release — which syncs Packagist. **You never create a tag by hand.**

For this to work, commits **must** follow the
[Conventional Commits](https://www.conventionalcommits.org/) standard:

```
<type>[optional scope]: <description>

[optional body]

[optional footer]
```

### Types and version impact

| Commit type | Example | Version effect |
|---|---|---|
| `feat:` | `feat: add ContactResource` | **minor** (`1.2.0` → `1.3.0`) |
| `fix:` | `fix: fix retry on 429` | **patch** (`1.2.0` → `1.2.1`) |
| `perf:` | `perf: reduce allocation in toArray` | **patch** |
| `BREAKING CHANGE` | see below | **major** (`1.2.0` → `2.0.0`) |
| `chore:` `docs:` `test:` `ci:` `style:` `refactor:` `build:` | — | **none** (no release) |

> Since the workflow uses `default_bump: false`, a push that contains **only**
> no-effect commits (e.g. just `docs:`) does **not** generate a release —
> correct SemVer behavior.

### Breaking change (major)

Use `!` after the type **or** a `BREAKING CHANGE:` footer:

```
feat!: rename VolpaMail::emails()->find() to ->get()

BREAKING CHANGE: the find() method was removed; use get().
```

### Examples

```bash
git commit -m "feat: support Idempotency-Key on send()"
git commit -m "fix(transport): propagate reply_to when converting Symfony Email"
git commit -m "docs: document status lookup"   # no release
```

Before pushing, make sure the gate is green locally:

```bash
composer test && composer analyse && composer format
```

## License

MIT. See [LICENSE.md](LICENSE.md).
