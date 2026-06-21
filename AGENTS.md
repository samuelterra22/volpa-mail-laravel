# Repository Guidelines

## Project Structure & Module Organization

This repository is a Laravel package for the Volpa Mail SDK and mail transport. PHP source code lives in `src/` under the `SamuelTerra\VolpaMail` namespace. Key areas include `src/Client/` for API access, `src/Data/` for DTOs, `src/Transport/` for Laravel mail transport integration, `src/Facades/`, `src/Enums/`, and `src/Exceptions/`. Package configuration is in `config/volpa-mail.php`. Tests live in `tests/`, split between `tests/Unit/`, `tests/Feature/`, and architecture checks in `tests/ArchTest.php`. Docker support is in `docker/` and CI/release workflows are in `.github/workflows/`.

## Build, Test, and Development Commands

Use the Makefile targets for the standard container workflow:

- `make build` builds the Docker image.
- `make install` installs Composer dependencies.
- `make test` runs the Pest test suite.
- `make test-coverage` runs tests with coverage.
- `make test-filter FILTER=Transport` runs matching tests only.
- `make format` formats code with Laravel Pint.
- `make analyse` runs PHPStan/Larastan at level 8.
- `make shell` opens a shell in the app container.

Composer scripts mirror the main checks: `composer test`, `composer analyse`, and `composer format`.

## Coding Style & Naming Conventions

Follow PSR-4 autoloading with namespace `SamuelTerra\VolpaMail\` mapped to `src/`. Use Laravel Pint with the `laravel` preset; `pint.json` also requires strict types, alphabetical imports, and does not force classes to be `final`. Prefer typed DTOs for request/response payloads. Use descriptive class names that match their role, such as `VolpaMailClient`, `EmailResource`, `SendEmailData`, and `VolpaMailTransport`.

## Testing Guidelines

Tests use Pest with Orchestra Testbench for Laravel package testing. Place focused behavior tests in `tests/Feature/` and small DTO or value-object checks in `tests/Unit/`. Name test files by subject, for example `ClientTest.php` or `DtoTest.php`. Run `make test` before pushing, and use `make test-filter FILTER=<name>` while iterating.

## Commit & Pull Request Guidelines

Git history and README require Conventional Commits. Use examples like `feat: add retry support`, `fix(transport): preserve reply_to`, `docs: update installation notes`, or `style: fix code styling`. Release automation reads commits on `main`, computes SemVer, and creates tags/releases automatically; do not create release tags manually.

Pull requests should include a short description, the reason for the change, linked issues when available, and test results such as `make test`, `make analyse`, and `make format`. Include screenshots only for documentation or workflow changes that affect rendered output.

## Security & Configuration Tips

Do not commit real Volpa Mail API keys. Document configuration with environment variables such as `VOLPA_MAIL_API_KEY` and `VOLPA_MAIL_BASE_URL`, and keep package defaults in `config/volpa-mail.php`.
