# CLAUDE.md — volpa-mail-laravel

Contexto para o Claude Code dar continuidade a este pacote. Leia antes de
qualquer alteração.

## O que é este pacote

`samuelterra22/volpa-mail-laravel` é o **SDK + Mail Transport** do Volpa Mail
para aplicações Laravel dos clientes. Ele permite que qualquer app Laravel
envie e-mails transacionais pela API do Volpa Mail de duas formas:

1. **Como mailer do Laravel** (`Mail::mailer('volpa-mail')->send(...)`) — via
   um `Transport` do Symfony Mailer registrado com `Mail::extend`.
2. **Como SDK direto** (`VolpaMail::emails()->send([...])`) — para controle
   fino, templates e variáveis.

> Este pacote é **o cliente**. Ele NÃO contém o backend. O backend (a
> plataforma Volpa Mail) é um projeto Laravel + Filament separado.

## Contexto de marca

- **Volpa** é a marca guarda-chuva (família de SaaS): Volpa Chat, **Volpa
  Mail**, Volpa Track, Volpa Sign. Símbolo: raposa (it. *volpe*). Tagline:
  "Esperto por dentro".
- **Volpa Mail** = sub-produto de e-mail transacional. Cor de destaque:
  Cobalt `#2E5BFF`. Estados de entrega têm cores próprias (Delivered,
  Deferred, Bounced, Pending).
- Nome anterior do projeto: **MailForge**. Migrou para **Volpa Mail**.
  Qualquer referência a `mailforge` em código antigo deve ser renomeada.

## A plataforma Volpa Mail (backend — contexto, não está aqui)

Alternativa self-hosted ao Resend/Postmark, consolidando o e-mail
transacional de ~7 clientes (Clínica Positivamente, Túlio Academy, SignOS,
Athelier do Terno, ZapFlow/Volpa Chat, Basal, TrackForge/Volpa Track).

Stack do backend: Laravel 12, FilamentPHP v4 (multi-tenancy nativa),
PostgreSQL 16 (tabelas particionadas para emails/eventos), Redis, Laravel
Horizon, **Amazon SES** como provider de envio. Driver abstrato
`EmailProviderInterface` com SES/Resend/Mailgun + circuit-breaker.

> Nota operacional: a saída do sandbox SES foi rejeitada uma vez; em
> produção usou-se Resend transitoriamente. A API exposta ao cliente é a
> mesma independente do provider por trás.

## Contrato da API (consumido por este pacote)

Base URL: `https://api.mail.volpa.com.br/v1` (configurável). Auth via header
`X-API-Key`.

### POST /emails

Request body (campos opcionais omitidos quando vazios):

```json
{
  "from": { "email": "...", "name": "..." },
  "to": [{ "email": "...", "name": "..." }],
  "cc": [...], "bcc": [...], "reply_to": [...],
  "subject": "...",
  "html": "...",
  "text": "...",
  "template_id": "slug-ou-id",
  "variables": { "name": "João" },
  "tags": ["reserva"],
  "headers": { "X-Custom": "..." },
  "attachments": [{ "filename": "f.pdf", "content": "<base64>", "content_type": "application/pdf" }]
}
```

Response esperado (2xx):

```json
{ "id": "eml_123", "status": "queued", "created_at": "2026-06-17T08:00:00Z" }
```

### GET /emails/{id}

Retorna o mesmo shape de `SentEmail` com o `status` atual.

`status` mapeia para o enum `EmailStatus`: pending, queued, sent, delivered,
deferred, bounced, complained, failed.

Erros: corpo `{ "message": "...", "errors": {...} }` com HTTP 4xx/5xx →
vira `VolpaMailException` (com `->status` e `->errors`).

## Arquitetura do pacote

```
src/
  VolpaMailServiceProvider.php   # bind singleton do client + Mail::extend('volpa-mail')
  Facades/VolpaMail.php          # facade -> VolpaMailClient
  Client/
    VolpaMailClient.php          # core HTTP (Illuminate Http Factory injetada)
    Resources/EmailResource.php  # send() / get()
  Transport/
    VolpaMailTransport.php       # AbstractTransport do Symfony -> usa o client
  Data/                          # DTOs readonly finais
    SendEmailData.php            # fromArray() | fromSymfonyEmail() | toArray()
    Address.php  Attachment.php  SentEmail.php
  Enums/EmailStatus.php
  Exceptions/VolpaMailException.php
config/volpa-mail.php
tests/                           # Pest + Orchestra Testbench
```

### Convenções (Clean Code / Clean Architecture)

- `declare(strict_types=1)` em **todos** os arquivos (há ArchTest validando).
- DTOs são `final readonly` (validado por ArchTest).
- Sem chamada HTTP fora do `VolpaMailClient`. Resource e Transport dependem do
  client, nunca do `Http` diretamente.
- `HttpFactory` é **injetada** no client (não usa a facade `Http`), o que
  permite `Http::fake()` nos testes e mantém o client testável/desacoplado.
- Nada de `dd/dump/var_dump` (ArchTest valida).
- PHPStan nível 8, Pint preset Laravel + `declare_strict_types`.

### Pontos de extensão / o que falta

Roadmap aberto para evoluir o SDK conforme a API do backend cresce:

- [ ] `Resources/`: adicionar `BroadcastResource`, `ContactResource`,
      `SuppressionResource`, `DomainResource` espelhando a API REST do backend.
- [ ] Idempotency-Key header opcional no `send()` (evitar duplicidade em
      retries) — sugiro `Idempotency-Key` com UUID v7.
- [ ] Verificação de assinatura de **webhooks** (helper
      `VolpaMail::webhooks()->verify($payload, $signature)`), já que o backend
      emite eventos de status (delivered/bounced/...).
- [ ] Suporte a `template_id` com renderização local opcional (fallback).
- [ ] Macros/eventos Laravel (`MessageSent`) carregando o `id` do Volpa Mail.
      O Transport já faz `setMessageId($sent->id)` — pode-se disparar evento.
- [ ] Rate limit handling (HTTP 429 + `Retry-After`).
- [ ] DTO `SentEmail` expandir com `to`, `subject`, eventos.

## Fluxo de desenvolvimento

```bash
composer install
composer test          # Pest
composer analyse       # PHPStan nível 8 (larastan)
composer format        # Pint
```

Os testes usam `orchestra/testbench` (não precisa de app Laravel real) e
`Http::fake()` — nenhuma chamada de rede real.

## Publicação no Packagist

1. Push para `github.com/samuelterra22/volpa-mail-laravel` (branch `main`).
2. Tag SemVer: `git tag v1.0.0 && git push --tags`.
3. Submeter/atualizar no Packagist (webhook do GitHub mantém sincronizado).
4. Package discovery do Laravel já registra provider + alias `VolpaMail`
   (ver bloco `extra.laravel` no composer.json).

## Autor / negócio

Samuel Terra — dev full-stack (Lavras, MG). Stack principal Laravel 12 +
FilamentPHP v4 + PostgreSQL. Este pacote serve aos apps dos clientes que hoje
mandam e-mail e devem migrar para o Volpa Mail como infraestrutura única.
Preferência por respostas e código curtos, objetivos, Clean Code.
