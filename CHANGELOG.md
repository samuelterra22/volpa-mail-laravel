# Changelog

Todas as mudanças relevantes deste pacote são documentadas aqui.
O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
e o projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-06-17

### Added
- Mail Transport `volpa-mail` para Laravel (`Mail::mailer('volpa-mail')`).
- SDK direto via Facade `VolpaMail::emails()->send(...)` e `->get($id)`.
- DTOs tipados (`SendEmailData`, `Address`, `Attachment`, `SentEmail`).
- Enum `EmailStatus` com estados de entrega e `isTerminal()`.
- Retry automático e tratamento de erros via `VolpaMailException`.

[1.0.0]: https://github.com/samuelterra22/volpa-mail-laravel/releases/tag/v1.0.0
