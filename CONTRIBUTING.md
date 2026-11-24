# Contributing

Thank you for helping improve the wsign PHP library.

## Development setup

```bash
git clone https://github.com/wsign-io/wsign-php.git
cd wsign-php
composer install
```

## Checks

Run all of these before opening a pull request:

```bash
composer test          # unit tests (no network)
composer analyse       # PHPStan, level 8
composer cs            # code style (PSR-12); composer cs:fix to apply
```

The integration suite talks to a real API in test mode and is skipped unless configured:

```bash
WSIGN_API_KEY=sk_test_... WSIGN_API_BASE=https://wsign.io/api/v1 composer test:integration
```

It refuses live keys. It creates drafts and a test webhook endpoint and cancels or deletes them at the end.

## Guidelines

- Keep the public API backwards compatible within a major version.
- Add or update unit tests for every change, and update `CHANGELOG.md` under "Unreleased".
- Follow the existing naming: services mirror API resources and use the API's field names.
- Report security issues privately as described in [SECURITY.md](SECURITY.md).
