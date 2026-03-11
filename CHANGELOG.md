# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

#### Core Library
- `WhatspassConfig` — configuration DTO with `fromArray()` factory and `getApiEndpoint()` builder
- `OtpGenerator` — cryptographically secure OTP generation via `random_int()`, supporting numeric and alphanumeric codes (4–12 characters)
- `OtpMessage` — message DTO with automatic E.164 phone number normalization and `toApiPayload()` for the Meta Cloud API
- `WhatspassClient` — Guzzle-based HTTP client wrapping the Meta WhatsApp Cloud API (`POST /{version}/{phone_number_id}/messages`)
- `WhatspassService` — high-level service with `generateOtp()`, `sendOtp()`, `generateAndSend()`, and `send()` methods
- `MessageType` enum — `Template` and `Text` message types
- `WhatspassServiceInterface` contract for type-safe dependency injection
- Exception hierarchy: `WhatspassException`, `ApiException`, `InvalidConfigException`, `InvalidPhoneNumberException`
- PSR-3 logger support in `WhatspassClient` — debug, info, and error log levels

#### Framework Integrations
- Laravel auto-discovery via `WhatspassServiceProvider` (supports Laravel 11 and 12)
- Laravel `Whatspass` facade
- Publishable `config/whatspass.php` with all options backed by environment variables
- Symfony bundle (`WhatspassBundle`) with DI extension and config tree builder (supports Symfony 6.4 and 7.x)

#### Rate Limiting
- `RateLimiterInterface` — contract for per-phone-number OTP rate limiting (`attempt(string $phoneNumber): void`)
- `NullRateLimiter` — no-op default implementation; allows all requests through
- `RateLimitExceededException` — thrown by rate limiter implementations when the limit is exceeded
- `WhatspassService` now calls `RateLimiterInterface::attempt()` before every send; the API is never called if the limit is exceeded
- Laravel service provider registers `NullRateLimiter` via `bindIf`, allowing easy replacement without modifying library code
- Symfony DI extension registers `NullRateLimiter` only when no other binding for `RateLimiterInterface` exists

#### Project Infrastructure
- `.env.example` — documents all supported `WHATSPASS_*` environment variables with descriptions and defaults
- `.gitignore` — ignores `vendor/`, `coverage/`, `.phpunit.cache/`, `.phpunit.result.cache`, and `composer.lock`
- `LICENSE.md` — MIT license (© 2026 DEV1 Softworks S.A.S. de C.V.)
- `phpunit.xml` — PHPUnit 11 configuration with source coverage exclusions for framework integration layers
- GitHub Actions CI workflow — matrix across PHP 8.2, 8.3, and 8.4; coverage enforced at ≥ 80% on PHP 8.4 via PCOV
- GitHub Actions Release workflow — runs tests on tag push and creates a GitHub Release

#### Tests
- 82 tests, 177 assertions — PHPUnit 11 with Mockery 1.6
- `WhatspassConfigTest` — configuration validation, `fromArray()`, endpoint generation
- `OtpGeneratorTest` — length, character sets, uniqueness
- `OtpMessageTest` — E.164 normalization, payload construction, edge cases
- `WhatspassClientTest` — HTTP success/error paths, authorization headers, endpoint targeting, logging
- `WhatspassServiceTest` — full service integration with mocked client, rate limiter interaction
- `NullRateLimiterTest` — interface contract, unlimited pass-through

### Changed

- Minimum PHP version set to **8.2** (PHP 8.1 reached end-of-life November 2024)
- Dropped Laravel 10 support (reached end-of-life February 2025); now supports Laravel **11 and 12**

### Fixed

#### CI / CD
- Removed PHP 8.1 from the CI matrix to match the updated minimum requirement
- Fixed `phpunit.xml` comment containing `--` (double hyphen), which is invalid in XML comments and caused PHPUnit to exit with code 2
- Added `--no-coverage` flag to non-coverage CI runs to suppress the "No code coverage driver available" PHPUnit warning (exit code 1) caused by the `<coverage>` block in `phpunit.xml`
- Replaced broken coverage threshold check (`php -r "... '{$LINES}' ..."` cast `{99.39}` to `0.0` due to curly-brace interpolation) with `awk` arithmetic comparison

### Security

- **HTTPS enforcement** — `WhatspassConfig` rejects any `base_url` not starting with `https://` at instantiation time
- **Numeric Phone Number ID validation** — `phone_number_id` must consist of digits only, matching Meta's format
- **Explicit TLS verification** — Guzzle client is instantiated with `verify: true`; SSL certificate verification cannot be silently disabled
- **Phone number masking in logs** — recipients are written as `+155*****67` across all log levels; raw phone numbers are never logged
- **API error body redaction** — only `error_code` and `error_type` fields are logged on API errors; full Meta error responses (which may contain trace IDs, quota details, OAuth metadata) are never written to logs
- **Connection error message sanitization** — Guzzle exception messages (which may expose DNS, TLS, or network infrastructure details) are not propagated into the public exception message; only the exception class name is logged
- **ReDoS guard** — phone number inputs longer than 30 characters are rejected before any regex processing, preventing catastrophic backtracking on malformed input

---

[Unreleased]: https://github.com/dev1/whatspass/compare/HEAD...HEAD
