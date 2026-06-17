# Changelog

Todas as mudanças relevantes deste pacote são documentadas aqui.
O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
e o projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- Mail Transport `volpa-mail` para Laravel (`Mail::mailer('volpa-mail')`).
- SDK direto via Facade `VolpaMail::emails()->send(...)`.
- DTOs tipados (`SendEmailData`, `Address`, `Attachment`, `SentEmail`).
- Enum `EmailStatus` com estados de entrega.
- Retry automático e tratamento de erros via `VolpaMailException`.
