# Volpa Mail for Laravel

[![tests](https://github.com/samuelterra22/volpa-mail-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/samuelterra22/volpa-mail-laravel/actions)
[![Packagist](https://img.shields.io/packagist/v/samuelterra22/volpa-mail-laravel.svg)](https://packagist.org/packages/samuelterra22/volpa-mail-laravel)
[![PHP](https://img.shields.io/packagist/php-v/samuelterra22/volpa-mail-laravel.svg)](https://packagist.org/packages/samuelterra22/volpa-mail-laravel)
[![License](https://img.shields.io/packagist/l/samuelterra22/volpa-mail-laravel.svg)](LICENSE.md)

Official **Volpa Mail** SDK and Mail Transport for Laravel. Send transactional
emails through the [Volpa Mail](https://volpa.com.br) API using Laravel's native
`Mail` facade or the SDK directly — with typed DTOs, retries, and rich error
handling.

> **What this package is:** the *client* SDK that your Laravel apps install to
> send mail through Volpa Mail. It does **not** contain the Volpa Mail backend
> (the multi-tenant sending platform).

---

## Table of contents

- [Requirements](#requirements)
- [Feature scope](#feature-scope)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick start](#quick-start)
- [Using as a Laravel Mailer](#using-as-a-laravel-mailer)
- [Using as a direct SDK](#using-as-a-direct-sdk)
- [Idempotency-Key on send](#idempotency-key-on-send)
- [Suppressions](#suppressions)
- [Contacts & contact lists](#contacts--contact-lists)
- [Broadcasts](#broadcasts)
- [Webhook verification](#webhook-verification)
- [Email payload reference](#email-payload-reference)
- [Attachments](#attachments)
- [Checking delivery status](#checking-delivery-status)
- [Error handling](#error-handling)
- [Troubleshooting](#troubleshooting)
- [Testing & quality](#testing--quality)
- [Contributing — Conventional Commits & releases](#contributing--conventional-commits--releases)
- [License](#license)

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | `^8.3` |
| Laravel (`illuminate/*`) | `^11.0` or `^12.0` |
| `symfony/mailer` | `^7.0` |
| A Volpa Mail account | API key generated in the panel (Settings → API Keys) |

---

## Feature scope

| Capability | Status |
|---|:---:|
| Send transactional email (`POST /emails`) | ✅ |
| Get email status (`GET /emails/{id}`) | ✅ |
| Laravel `Mail::mailer('volpa-mail')` transport | ✅ |
| Typed DTOs (`SendEmailData`, `Address`, `Attachment`, `SentEmail`) | ✅ |
| Automatic retries + typed exceptions | ✅ |
| `Idempotency-Key` header on send | ✅ |
| 429 / `Retry-After` handling | ✅ |
| Suppressions | ✅ |
| Contacts / Contact lists | ✅ |
| Broadcasts / Campaigns | ✅ |
| Webhook signature verification (custom HMAC-SHA256) | ✅ |
| Batch send (`POST /emails/batch`) | 🔜 roadmap |
| Domain API | — not implemented (no backend endpoint) |

---

## Installation

```bash
composer require samuelterra22/volpa-mail-laravel
```

The service provider and the `VolpaMail` facade are auto-discovered — no manual
registration needed.

Publish the config (optional, only if you want to tweak defaults):

```bash
php artisan vendor:publish --tag=volpa-mail-config
```

This creates `config/volpa-mail.php`.

---

## Configuration

Add your credentials to `.env`:

```dotenv
VOLPA_MAIL_API_KEY=your-tenant-api-key
VOLPA_MAIL_BASE_URL=https://mail.volpa.com.br/v1
```

All available environment variables:

| Env variable | Config key | Default | Description |
|---|---|---|---|
| `VOLPA_MAIL_API_KEY` | `api_key` | *(none — required)* | Tenant API key. Sent in the `X-API-Key` header on every request. Generated in the Volpa Mail panel under **Settings → API Keys**. |
| `VOLPA_MAIL_BASE_URL` | `base_url` | `https://mail.volpa.com.br/v1` | Base endpoint of the REST API. Includes the `/v1` version prefix, no trailing slash. |
| `VOLPA_MAIL_TIMEOUT` | `timeout` | `10` | Per-request timeout, in seconds. |
| `VOLPA_MAIL_RETRY_TIMES` | `retry.times` | `2` | Retry attempts on network failure or `5xx`. |
| `VOLPA_MAIL_RETRY_SLEEP` | `retry.sleep` | `200` | Wait between retries, in milliseconds. |

> **The API key is mandatory.** If it is missing, the SDK throws
> `VolpaMailException::missingApiKey()` on the first call — fail fast instead of
> silently dropping mail.

---

## Quick start

```php
use SamuelTerra\VolpaMail\Facades\VolpaMail;

$sent = VolpaMail::emails()->send([
    'from'    => ['email' => 'contato@athelier.com.br', 'name' => 'Athelier'],
    'to'      => [['email' => 'cliente@gmail.com']],
    'subject' => 'Sua reserva foi confirmada!',
    'html'    => '<h1>Obrigado!</h1>',
]);

echo $sent->id;            // eml_123
echo $sent->status->value; // queued
```

---

## Using as a Laravel Mailer

Register the mailer in `config/mail.php`:

```php
'mailers' => [
    // ...
    'volpa-mail' => [
        'transport' => 'volpa-mail',
    ],
],
```

Make it the default mailer:

```dotenv
MAIL_MAILER=volpa-mail
```

…or use it on demand for a single message:

```php
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

Mail::mailer('volpa-mail')->to($user->email)->send(new WelcomeMail($user));
```

All standard `Mailable` features work as usual — Markdown mailables,
attachments, `cc`/`bcc`, `replyTo`, custom headers. The transport converts the
Symfony message into a Volpa Mail API call and records the returned email ID as
the message ID (`setMessageId($sent->id)`) so you can correlate it with webhook
events on the backend.

---

## Using as a direct SDK

For fine-grained control, templates, and variables, call the SDK directly.

### With a friendly array

```php
use SamuelTerra\VolpaMail\Facades\VolpaMail;

$sent = VolpaMail::emails()->send([
    'from'        => ['email' => 'no-reply@volpa.com.br', 'name' => 'Volpa'],
    'to'          => [['email' => 'cliente@gmail.com', 'name' => 'João']],
    'cc'          => [['email' => 'gestor@volpa.com.br']],
    'subject'     => 'Sua reserva foi confirmada!',
    'html'        => '<h1>Obrigado, João!</h1>',
    'text'        => 'Obrigado, João!',
    'template_id' => 'reservation-confirmation',
    'variables'   => ['name' => 'João', 'date' => '20/06/2026'],
    'tags'        => ['reserva'],
    'headers'     => ['X-Campaign' => 'reservas-junho'],
]);
```

### With a typed DTO

```php
use SamuelTerra\VolpaMail\Data\Address;
use SamuelTerra\VolpaMail\Data\SendEmailData;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

$sent = VolpaMail::emails()->send(new SendEmailData(
    from:    new Address('no-reply@volpa.com.br', 'Volpa'),
    to:      [new Address('cliente@gmail.com', 'João')],
    subject: 'Olá',
    html:    '<p>Conteúdo</p>',
));
```

---

## Idempotency-Key on send

Pass a unique key as the second argument to `send()` to enable idempotent
delivery. The backend replays the same response for a repeated key (24-hour
TTL) instead of sending a duplicate email. On a body mismatch for the same key,
the API returns `409 Conflict`.

The recommended key format is UUID v7 (time-ordered):

```php
use Illuminate\Support\Str;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

// Primeira tentativa
$sent = VolpaMail::emails()->send([
    'from'    => ['email' => 'no-reply@volpa.com.br'],
    'to'      => [['email' => 'cliente@gmail.com']],
    'subject' => 'Confirmação de pedido',
    'html'    => '<p>Seu pedido foi confirmado.</p>',
], Str::uuid7());

// Segunda chamada com a mesma chave — sem reenvio, retorna o mesmo $sent
```

> Store the key with the job so retries reuse it safely.

---

## Suppressions

Manage the suppression list (hard bounces, complaints, unsubscribes, etc.):

```php
use SamuelTerra\VolpaMail\Enums\SuppressionReason;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

// Listar supressões (com filtros opcionais)
$suppressions = VolpaMail::suppressions()->list(['reason' => 'hard_bounce']);

// Adicionar uma supressão manualmente
$suppression = VolpaMail::suppressions()->create(
    'usuario@exemplo.com.br',
    SuppressionReason::Manual,
);

// Consultar
$suppression = VolpaMail::suppressions()->get('usuario@exemplo.com.br');

// Remover (permite reenvio para este endereço)
VolpaMail::suppressions()->delete('usuario@exemplo.com.br');

// Importar em lote
VolpaMail::suppressions()->import(
    ['a@ex.com', 'b@ex.com'],
    SuppressionReason::HardBounce,
);
```

`SuppressionReason` cases: `HardBounce`, `SoftBounceRepeated`, `Complaint`,
`Unsubscribe`, `Manual`, `InvalidAddress`.

---

## Contacts & contact lists

```php
use SamuelTerra\VolpaMail\Facades\VolpaMail;

// Contatos
$contacts = VolpaMail::contacts()->list(['status' => 'active']);
$contact  = VolpaMail::contacts()->create([
    'email' => 'joao@exemplo.com.br',
    'name'  => 'João Silva',
]);
$contact  = VolpaMail::contacts()->get($contact->id);

// Listas de contatos
$lists = VolpaMail::contactLists()->list();
$list  = VolpaMail::contactLists()->create(['name' => 'Newsletter Junho']);
$list  = VolpaMail::contactLists()->get($list->id);

// Importar contatos para uma lista
VolpaMail::contactLists()->import($list->id, [
    ['email' => 'a@ex.com', 'name' => 'Ana'],
    ['email' => 'b@ex.com', 'name' => 'Bruno'],
]);
```

`ContactStatus` cases: `Active`, `Unsubscribed`, `Bounced`, `Complained`.

---

## Broadcasts

```php
use SamuelTerra\VolpaMail\Facades\VolpaMail;

// Criar um broadcast (campanha)
$broadcast = VolpaMail::broadcasts()->create([
    'name'        => 'Promoção Julho',
    'subject'     => 'Aproveite as ofertas de julho!',
    'template_id' => 'promo-julho',
    'list_id'     => $list->id,
]);

// Enviar
$result = VolpaMail::broadcasts()->send($broadcast->id);
// $result = ['id' => '...', 'status' => 'sending', 'total_queued' => 1234]

// Cancelar (enquanto ainda não completou)
$broadcast = VolpaMail::broadcasts()->cancel($broadcast->id);

// Listar e consultar
$all       = VolpaMail::broadcasts()->list();
$broadcast = VolpaMail::broadcasts()->get($broadcast->id);
```

`BroadcastStatus` cases: `Draft`, `Scheduled`, `Sending`, `Sent`, `Canceled`,
`Failed`. Use `$status->isFinal()` to check if the broadcast has reached a
terminal state.

---

## Webhook verification

The Volpa Mail backend sends events to your endpoint (e.g. `delivered`,
`bounced`) signed with a custom HMAC-SHA256 scheme.

**Signature format** — the delivery header is `X-VolpaMail-Signature`:
```
t=<unix_timestamp>,v1=<hex_hmac>
```
The signed string is `<unix_timestamp>.<raw_json_body>`. The tolerance window
defaults to 300 seconds.

### Verifying in a controller

```php
use Illuminate\Http\Request;
use SamuelTerra\VolpaMail\Webhooks\WebhookVerifier;

class VolpaWebhookController extends Controller
{
    public function handle(Request $request): \Illuminate\Http\Response
    {
        $secret   = config('services.volpa_mail.webhook_secret');
        $verifier = new WebhookVerifier();

        if (! $verifier->verifyRequest($request, $secret)) {
            abort(401, 'Assinatura inválida.');
        }

        $event = $verifier->parseEvent($request->getContent());
        // $event->type    — ex.: 'email.delivered'
        // $event->created — timestamp ISO 8601
        // $event->data    — array com os detalhes do evento

        // Processar o evento...
        return response('', 200);
    }
}
```

Or verify raw payload + header string manually:

```php
$ok = $verifier->verify(
    payload:         $rawBody,
    secret:          $secret,
    signatureHeader: $request->header('X-VolpaMail-Signature'),
    tolerance:       300, // segundos (padrão)
);
```

> The event type is also available in the `X-VolpaMail-Event` header if you
> need it before parsing the body.

---

## Email payload reference

Fields accepted by `send()` (array keys / DTO constructor args). Empty optional
fields are omitted from the request body.

| Field (array) | DTO arg | Type | Required | Notes |
|---|---|---|:---:|---|
| `from` | `from` | `array{email,name?}` / `Address` | ✅ | Sender. |
| `to` | `to` | list of `{email,name?}` / `Address[]` | ✅ | At least one recipient. |
| `cc` | `cc` | list / `Address[]` | — | Carbon copy. |
| `bcc` | `bcc` | list / `Address[]` | — | Blind carbon copy. |
| `reply_to` | `replyTo` | list / `Address[]` | — | Reply-To addresses. |
| `subject` | `subject` | `string` | ⚠️ | Required unless a `template_id` supplies it. |
| `html` | `html` | `string` | — | HTML body. |
| `text` | `text` | `string` | — | Plain-text body. |
| `template_id` | `templateId` | `string` | — | Template slug or ID on the backend. |
| `variables` | `variables` | `array<string,mixed>` | — | Template variables. |
| `tags` | `tags` | `string[]` | — | Tags for filtering/analytics. |
| `headers` | `headers` | `array<string,string>` | — | Custom `X-*` headers. |
| `attachments` | `attachments` | `Attachment[]` | — | See [Attachments](#attachments). |

---

## Attachments

Build an attachment from a file on disk (it is read and base64-encoded for you):

```php
use SamuelTerra\VolpaMail\Data\Attachment;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

VolpaMail::emails()->send([
    'from'        => ['email' => 'no-reply@volpa.com.br'],
    'to'          => [['email' => 'cliente@gmail.com']],
    'subject'     => 'Sua nota fiscal',
    'html'        => '<p>Segue em anexo.</p>',
    'attachments' => [
        Attachment::fromPath(storage_path('app/notas/nf-123.pdf')),
    ],
]);
```

Or construct it explicitly with already-encoded content:

```php
new Attachment(
    filename:    'nf-123.pdf',
    content:     base64_encode($pdfBytes),
    contentType: 'application/pdf',
);
```

---

## Checking delivery status

```php
$email = VolpaMail::emails()->get('eml_123');

$email->id;                   // 'eml_123'
$email->status;               // EmailStatus enum
$email->status->value;        // 'delivered'
$email->status->isTerminal(); // true for delivered/bounced/failed/complained/rejected/canceled
$email->from;                 // ?string — sender address
$email->to;                   // list<string> — recipient addresses
$email->subject;              // ?string
$email->messageStream;        // ?string — stream/pool identifier
```

`EmailStatus` cases:

| Case | Value | Terminal? |
|---|---|:---:|
| `Pending` | `pending` | — |
| `Queued` | `queued` | — |
| `Scheduled` | `scheduled` | — |
| `Processing` | `processing` | — |
| `Sent` | `sent` | — |
| `Delivered` | `delivered` | ✅ |
| `Opened` | `opened` | — |
| `Clicked` | `clicked` | — |
| `Deferred` | `deferred` | — |
| `Bounced` | `bounced` | ✅ |
| `SoftBounced` | `soft_bounced` | — |
| `Complained` | `complained` | ✅ |
| `Rejected` | `rejected` | ✅ |
| `Failed` | `failed` | ✅ |
| `Canceled` | `canceled` | ✅ |

---

## Error handling

Any non-2xx response (or a missing API key) raises a
`SamuelTerra\VolpaMail\Exceptions\VolpaMailException`:

```php
use SamuelTerra\VolpaMail\Exceptions\VolpaMailException;
use SamuelTerra\VolpaMail\Facades\VolpaMail;

try {
    VolpaMail::emails()->send([/* ... */]);
} catch (VolpaMailException $e) {
    $e->getMessage();   // human-readable message from the API
    $e->status;         // ?int — HTTP status code (e.g. 422)
    $e->errors;         // array<string, mixed> — field => [messages]
    $e->errorCode;      // ?string — machine code, e.g. 'sender_not_found'
    $e->retryAfter;     // ?int — seconds to wait (parsed from Retry-After on 429)
}
```

The exception parses both error envelopes returned by the backend:
`{"error":{"code","message"}}` and `{"message","errors"}`. On HTTP 429, check
`$e->retryAfter` before scheduling a retry.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| `VolpaMailException: Missing Volpa Mail API key` | `VOLPA_MAIL_API_KEY` not set (or config cached). | Set the env var, then `php artisan config:clear`. |
| `401 Unauthorized` | Invalid/revoked API key, or key from the wrong tenant. | Re-issue the key in the panel and update `.env`. |
| `403 Forbidden` | IP allowlist on the key, or sender not verified. | Allow your server IP / verify the sender domain in the panel. |
| `422 Unprocessable Entity` | Validation failed (e.g. missing `subject` and `template_id`). | Inspect `$e->errors` for the offending fields. |
| Mail silently not sent via `Mail::` | `volpa-mail` mailer not registered in `config/mail.php`. | Add the mailer block shown above. |
| Connection timeouts | Network/firewall or low `VOLPA_MAIL_TIMEOUT`. | Raise the timeout / check egress to `mail.volpa.com.br`. |

---

## Testing & quality

The test suite uses [Pest](https://pestphp.com/) and
[Orchestra Testbench](https://github.com/orchestral/testbench) with
`Http::fake()` — **no real network calls are made**.

```bash
composer test       # Pest test suite
composer analyse    # PHPStan / Larastan level 8
composer format     # Laravel Pint (code style)
```

Run all three before pushing:

```bash
composer test && composer analyse && composer format
```

---

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

---

## License

MIT. See [LICENSE.md](LICENSE.md).
