# Volpa Mail para Laravel

[![tests](https://github.com/samuelterra22/volpa-mail-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/samuelterra22/volpa-mail-laravel/actions)
[![Packagist](https://img.shields.io/packagist/v/samuelterra22/volpa-mail-laravel.svg)](https://packagist.org/packages/samuelterra22/volpa-mail-laravel)

SDK e Mail Transport oficiais do **Volpa Mail** para Laravel. Envie e-mails
transacionais pela API do Volpa Mail usando o `Mail` nativo do Laravel ou o
SDK direto.

## Instalação

```bash
composer require samuelterra22/volpa-mail-laravel
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

## Contribuindo — Conventional Commits & releases

As versões deste pacote são **automáticas**. Ao dar push em `main`, o workflow
`CI` roda os testes; se passar, o workflow `Release` lê as mensagens de commit,
calcula a próxima versão (SemVer) e publica a tag + GitHub Release — o que
sincroniza o Packagist. **Você nunca cria tag à mão.**

Para isso funcionar, os commits **devem** seguir o padrão
[Conventional Commits](https://www.conventionalcommits.org/):

```
<tipo>[escopo opcional]: <descrição>

[corpo opcional]

[rodapé opcional]
```

### Tipos e impacto na versão

| Tipo do commit | Exemplo | Efeito na versão |
|---|---|---|
| `feat:` | `feat: adiciona ContactResource` | **minor** (`1.2.0` → `1.3.0`) |
| `fix:` | `fix: corrige retry em 429` | **patch** (`1.2.0` → `1.2.1`) |
| `perf:` | `perf: reduz alocação no toArray` | **patch** |
| `BREAKING CHANGE` | veja abaixo | **major** (`1.2.0` → `2.0.0`) |
| `chore:` `docs:` `test:` `ci:` `style:` `refactor:` `build:` | — | **nenhum** (não gera release) |

> Como o workflow usa `default_bump: false`, um push que contenha **apenas**
> commits sem efeito (ex.: só `docs:`) **não** gera release — comportamento
> correto de SemVer.

### Mudança incompatível (major)

Use `!` após o tipo **ou** um rodapé `BREAKING CHANGE:`:

```
feat!: renomeia VolpaMail::emails()->find() para ->get()

BREAKING CHANGE: o método find() foi removido; use get().
```

### Exemplos

```bash
git commit -m "feat: suporte a Idempotency-Key no send()"
git commit -m "fix(transport): propaga reply_to ao converter Symfony Email"
git commit -m "docs: documenta consulta de status"   # não gera release
```

Antes do push, garanta o gate verde localmente:

```bash
composer test && composer analyse && composer format
```

## Licença

MIT. Veja [LICENSE.md](LICENSE.md).
