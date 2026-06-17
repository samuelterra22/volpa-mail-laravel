# Changelog

All notable changes to this package are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and the project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-06-17

### Added
- `volpa-mail` Mail Transport for Laravel (`Mail::mailer('volpa-mail')`).
- Direct SDK via the `VolpaMail::emails()->send(...)` and `->get($id)` facade.
- Typed DTOs (`SendEmailData`, `Address`, `Attachment`, `SentEmail`).
- `EmailStatus` enum with delivery states and `isTerminal()`.
- Automatic retry and error handling via `VolpaMailException`.

[1.0.0]: https://github.com/samuelterra22/volpa-mail-laravel/releases/tag/v1.0.0
