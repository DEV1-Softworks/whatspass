# Whatspass

[![CI](https://github.com/dev1/whatspass/actions/workflows/ci.yml/badge.svg)](https://github.com/dev1/whatspass/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/dev1/whatspass.svg)](https://packagist.org/packages/dev1/whatspass)
[![PHP Version](https://img.shields.io/packagist/php-v/dev1/whatspass.svg)](https://packagist.org/packages/dev1/whatspass)
[![License](https://img.shields.io/packagist/l/dev1/whatspass.svg)](LICENSE.md)

**Send OTP authentication codes via WhatsApp using Meta's official Cloud API.**

Whatspass is a framework-agnostic PHP library with first-class support for **Laravel** and **Symfony**. It talks directly to Meta's WhatsApp Business Cloud API — no third-party intermediaries, no extra billing layers.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ≥ 8.2 |
| Laravel (optional) | 11 or 12 |
| Symfony (optional) | 6.4 or 7.x |

---

## Installation

```bash
composer require dev1/whatspass
```

---

## Before You Start — Meta Setup

You need a **Meta for Developers** account with a WhatsApp Business App. Here is the one-time setup:

1. Go to [developers.facebook.com](https://developers.facebook.com) and create an App (type: **Business**).
2. Add the **WhatsApp** product to your app.
3. In *WhatsApp → API Setup*, copy your:
   - **Phone Number ID** — the numeric ID that identifies the number you send from.
   - **Temporary access token** — or generate a permanent System User token from the Business Manager.
4. Create and get a **Message Template** approved in the Meta Business Manager. For OTP delivery, a simple body like *"Your verification code is {{1}}."* is all you need.

> **Note:** Template messages work at any time. Free-form text messages (`type: text`) only work within a 24-hour customer-care window (i.e., the recipient must have messaged you first).

---

## Laravel

### 1. Publish the config

```bash
php artisan vendor:publish --tag=whatspass-config
```

This creates `config/whatspass.php`.

### 2. Set environment variables

Copy `.env.example` entries to your `.env` file and fill in your credentials:

```env
# Required
WHATSPASS_PHONE_NUMBER_ID=123456789012345
WHATSPASS_ACCESS_TOKEN=your_access_token_here

# Optional (defaults shown)
WHATSPASS_API_VERSION=v19.0
WHATSPASS_TEMPLATE_NAME=otp_authentication
WHATSPASS_LANGUAGE_CODE=en_US
WHATSPASS_OTP_LENGTH=6
WHATSPASS_OTP_EXPIRY=300
WHATSPASS_ALPHANUMERIC_OTP=false
```

### 3. Send an OTP

**Using the Facade:**

```php
use Dev1\Whatspass\Laravel\Facades\Whatspass;

// Generate a code and send it in one step
$result = Whatspass::generateAndSend('+15551234567');

$otp      = $result['otp'];       // "483920"  — store this to verify later
$response = $result['response'];  // raw Meta API response
```

**Using dependency injection:**

```php
use Dev1\Whatspass\Contracts\WhatspassServiceInterface;

class AuthController extends Controller
{
    public function __construct(
        private readonly WhatspassServiceInterface $whatspass,
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $phone = $request->input('phone'); // e.g. "+15551234567"

        $result = $this->whatspass->generateAndSend($phone);

        // Store $result['otp'] in your session/cache to verify later
        session(['otp' => $result['otp'], 'otp_phone' => $phone]);

        return response()->json(['message' => 'OTP sent.']);
    }
}
```

### Configuration reference

```php
// config/whatspass.php
return [
    'phone_number_id'       => env('WHATSPASS_PHONE_NUMBER_ID'),
    'access_token'          => env('WHATSPASS_ACCESS_TOKEN'),
    'api_version'           => env('WHATSPASS_API_VERSION', 'v19.0'),
    'base_url'              => env('WHATSPASS_BASE_URL', 'https://graph.facebook.com'),
    'default_template_name' => env('WHATSPASS_TEMPLATE_NAME', 'otp_authentication'),
    'default_language_code' => env('WHATSPASS_LANGUAGE_CODE', 'en_US'),
    'otp_length'            => (int) env('WHATSPASS_OTP_LENGTH', 6),     // 4–12 characters
    'otp_expiry'            => (int) env('WHATSPASS_OTP_EXPIRY', 300),   // seconds (≥ 60)
    'alphanumeric_otp'      => (bool) env('WHATSPASS_ALPHANUMERIC_OTP', false),
];
```

### Rate limiting (Laravel)

By default the library uses a no-op rate limiter. To enforce per-phone-number limits in production, bind your own implementation to `RateLimiterInterface`:

```php
// app/Providers/AppServiceProvider.php
use Dev1\Whatspass\Contracts\RateLimiterInterface;
use App\Services\RedisWhatspassRateLimiter;

public function register(): void
{
    $this->app->bind(RateLimiterInterface::class, RedisWhatspassRateLimiter::class);
}
```

Example implementation using Laravel Cache:

```php
use Dev1\Whatspass\Contracts\RateLimiterInterface;
use Dev1\Whatspass\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;

class CacheRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly int $decaySeconds = 60,
    ) {}

    public function attempt(string $phoneNumber): void
    {
        $key   = 'whatspass:' . hash('sha256', $phoneNumber);
        $hits  = (int) Cache::get($key, 0);

        if ($hits >= $this->maxAttempts) {
            throw new RateLimitExceededException($phoneNumber);
        }

        Cache::put($key, $hits + 1, $this->decaySeconds);
    }
}
```

---

## Symfony

### 1. Register the bundle

```php
// config/bundles.php
return [
    // ...
    Dev1\Whatspass\Symfony\WhatspassBundle::class => ['all' => true],
];
```

### 2. Add configuration

```yaml
# config/packages/whatspass.yaml
whatspass:
    phone_number_id:       '%env(WHATSPASS_PHONE_NUMBER_ID)%'
    access_token:          '%env(WHATSPASS_ACCESS_TOKEN)%'
    api_version:           'v19.0'
    default_template_name: 'otp_authentication'
    default_language_code: 'en_US'
    otp_length:            6
    otp_expiry:            300
    alphanumeric_otp:      false
```

Set the required values in your `.env` file:

```env
WHATSPASS_PHONE_NUMBER_ID=123456789012345
WHATSPASS_ACCESS_TOKEN=your_access_token_here
```

### 3. Send an OTP

```php
use Dev1\Whatspass\Contracts\WhatspassServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly WhatspassServiceInterface $whatspass,
    ) {}

    public function sendOtp(string $phone): JsonResponse
    {
        $result = $this->whatspass->generateAndSend($phone);

        // Store $result['otp'] in your session/cache for verification
        return new JsonResponse(['message' => 'OTP sent.']);
    }
}
```

### Rate limiting (Symfony)

Register your implementation as an alias for `RateLimiterInterface`:

```yaml
# config/services.yaml
services:
    App\Service\WhatspassRateLimiter:
        arguments:
            $maxAttempts: 3
            $decaySeconds: 60

    Dev1\Whatspass\Contracts\RateLimiterInterface:
        alias: App\Service\WhatspassRateLimiter
```

---

## Framework-agnostic usage

No framework? No problem. Instantiate everything manually:

```php
use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use Dev1\Whatspass\WhatspassService;

$config = WhatspassConfig::fromArray([
    'phone_number_id' => getenv('WHATSPASS_PHONE_NUMBER_ID'),
    'access_token'    => getenv('WHATSPASS_ACCESS_TOKEN'),
    'otp_length'      => 6,
]);

$service = new WhatspassService(
    config:    $config,
    client:    new WhatspassClient($config),
    generator: new OtpGenerator(),
);

// Generate and send
$result = $service->generateAndSend('+15551234567');
echo $result['otp']; // "749201"

// Or send a code you generated yourself
$otp = $service->generateOtp();
$service->sendOtp('+15551234567', $otp);
```

---

## API Reference

### `WhatspassService`

All methods are available through the facade, dependency injection, or direct instantiation.

#### `generateOtp(?int $length, ?bool $alphanumeric): string`

Generate a cryptographically secure OTP code.

```php
$otp = $service->generateOtp();                   // "483920"   (6-digit numeric, from config)
$otp = $service->generateOtp(length: 8);          // "20938471"
$otp = $service->generateOtp(alphanumeric: true); // "4aB9Qz"
```

#### `sendOtp(string $phone, string $otp, array $options): array`

Send an existing OTP to a phone number. Returns the raw Meta API response.

```php
// Template message (default)
$response = $service->sendOtp('+15551234567', '483920');

// Free-form text message
$response = $service->sendOtp('+15551234567', '483920', [
    'type'           => 'text',
    'custom_message' => 'Your Acme code is {otp}. Expires in 5 minutes.',
]);

// Override template per message
$response = $service->sendOtp('+15551234567', '483920', [
    'template_name' => 'acme_otp_es',
    'language_code' => 'es_ES',
]);
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `type` | `string` | `'template'` | `'template'` or `'text'` |
| `template_name` | `string` | config value | Override the Meta template name |
| `language_code` | `string` | config value | BCP-47 language code (e.g. `pt_BR`) |
| `custom_message` | `string` | — | Text body for `type: text`. Use `{otp}` as placeholder |

#### `generateAndSend(string $phone, array $options): array`

Generate a code and send it in one step.

```php
$result = $service->generateAndSend('+15551234567');

$result['otp'];      // "483920"  — persist this to verify the user later
$result['response']; // Meta API response array
```

Accepts the same `$options` as `sendOtp()`, plus:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `otp_length` | `int` | config value | Override OTP length for this call |
| `alphanumeric_otp` | `bool` | config value | Override alphanumeric flag for this call |

#### `send(OtpMessage $message): array`

Send a manually constructed `OtpMessage` object directly.

```php
use Dev1\Whatspass\MessageType;
use Dev1\Whatspass\OtpMessage;

$message = new OtpMessage(
    to:            '+15551234567',
    otp:           '839201',
    type:          MessageType::Text,
    customMessage: 'Hi! Your login code is {otp}.',
);

$response = $service->send($message);
```

---

## Phone Number Format

Phone numbers are automatically normalized to **E.164** format. All of these are accepted:

```
+1 (555) 123-4567  →  +15551234567
+1-555-123-4567    →  +15551234567
15551234567        →  +15551234567
```

An `InvalidPhoneNumberException` is thrown if the number cannot be normalized (e.g. too short, longer than 30 characters, starts with `0`, or contains letters).

---

## Error Handling

```php
use Dev1\Whatspass\Exceptions\ApiException;
use Dev1\Whatspass\Exceptions\InvalidConfigException;
use Dev1\Whatspass\Exceptions\InvalidPhoneNumberException;
use Dev1\Whatspass\Exceptions\RateLimitExceededException;

try {
    $result = $service->generateAndSend($phone);
} catch (RateLimitExceededException $e) {
    // Too many OTP requests for this phone number
    return response()->json(['message' => 'Too many requests. Please wait and try again.'], 429);
} catch (InvalidPhoneNumberException $e) {
    // The phone number format is invalid
    logger()->warning('Bad phone number', ['error' => $e->getMessage()]);
} catch (ApiException $e) {
    // Meta API returned an error (4xx/5xx) or the connection failed
    logger()->error('WhatsApp API error', ['code' => $e->getCode()]);
} catch (InvalidConfigException $e) {
    // Misconfigured phone_number_id, access_token, otp_length, etc.
}
```

### Exception hierarchy

```
\RuntimeException
└── WhatspassException
    ├── ApiException              — Meta API / network errors
    └── RateLimitExceededException — Rate limit hit for a phone number

\InvalidArgumentException
├── InvalidConfigException        — Bad configuration values
└── InvalidPhoneNumberException   — Unparseable phone number
```

---

## Security

The library enforces the following security constraints out of the box:

- **HTTPS only** — `base_url` must start with `https://`. Plain HTTP is rejected at config-load time.
- **TLS verification** — Guzzle is explicitly configured with `verify: true`. It cannot be silently disabled.
- **Numeric Phone Number ID** — `phone_number_id` must be all digits, matching Meta's format.
- **Phone masking in logs** — recipients are logged as `+155*****67` to avoid PII leakage.
- **Redacted API errors** — only `error_code` and `error_type` are logged; full Meta error bodies are never written to logs.
- **Cryptographic OTP generation** — all codes are generated with `random_int()` (CSPRNG).
- **ReDoS guard** — phone number inputs longer than 30 characters are rejected before any regex processing.

---

## Logging

`WhatspassClient` accepts any PSR-3 logger. In Laravel and Symfony it is injected automatically. When provided, it logs:

- **`debug`** — before every request (masked recipient, message type)
- **`info`** — on success (masked recipient, Meta message ID)
- **`error`** — on API errors (HTTP status, error code and type only) and connection failures (exception class only)

Phone numbers are always masked in log output (e.g. `+155*****67`).

---

## Testing

```bash
# Run the test suite
composer test

# Run with code coverage report (requires Xdebug or PCOV)
composer test:coverage
```

### Mocking in your own tests

The library uses Guzzle's `MockHandler`, making it straightforward to test your own code without hitting the real API:

```php
use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use Dev1\Whatspass\WhatspassService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(200, [], json_encode([
        'messaging_product' => 'whatsapp',
        'messages' => [['id' => 'wamid.test123']],
    ])),
]);

$httpClient = new Client(['handler' => HandlerStack::create($mock)]);
$config     = WhatspassConfig::fromArray([
    'phone_number_id' => '123456789012345',
    'access_token'    => 'test-token',
]);

$service = new WhatspassService(
    config:    $config,
    client:    new WhatspassClient($config, $httpClient),
    generator: new OtpGenerator(),
);

$result = $service->generateAndSend('+15551234567');
// No real HTTP request is made
```

---

## CI / CD

The repository ships with two GitHub Actions workflows:

| Workflow | Trigger | What it does |
|----------|---------|--------------|
| **CI** (`.github/workflows/ci.yml`) | Push / PR to `main`, `master`, `develop` | Runs the test suite on PHP 8.2, 8.3, and 8.4, and enforces ≥ 80% coverage |
| **Release** (`.github/workflows/release.yml`) | Push of a `v*.*.*` tag | Runs tests, then creates a GitHub Release with auto-generated notes |

### Publishing a release

```bash
git tag v1.0.0
git push origin v1.0.0
```

The Release workflow runs the full test suite and creates a GitHub Release automatically.

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.

Made in Mexico with ❤️ by [DEV1 Labs](https://dev1.mx/labs), part of DEV1 Softworks.
