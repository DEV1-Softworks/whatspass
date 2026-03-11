<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

use Dev1\Whatspass\Exceptions\InvalidConfigException;

class WhatspassConfig
{
    public function __construct(
        public readonly string $phoneNumberId,
        public readonly string $accessToken,
        public readonly string $apiVersion = 'v19.0',
        public readonly string $baseUrl = 'https://graph.facebook.com',
        public readonly string $defaultTemplateName = 'otp_authentication',
        public readonly string $defaultLanguageCode = 'en_US',
        public readonly int $otpLength = 6,
        public readonly int $otpExpiry = 300,
        public readonly bool $alphanumericOtp = false,
    ) {
        if (empty(trim($phoneNumberId))) {
            throw new InvalidConfigException('The "phone_number_id" configuration value is required.');
        }

        if (empty(trim($accessToken))) {
            throw new InvalidConfigException('The "access_token" configuration value is required.');
        }

        if ($otpLength < 4 || $otpLength > 12) {
            throw new InvalidConfigException('The "otp_length" must be between 4 and 12 characters.');
        }

        if ($otpExpiry < 60) {
            throw new InvalidConfigException('The "otp_expiry" must be at least 60 seconds.');
        }
    }

    /**
     * Build the full API endpoint URL for sending messages.
     */
    public function getApiEndpoint(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . $this->apiVersion . '/' . $this->phoneNumberId . '/messages';
    }

    /**
     * Create a WhatspassConfig from an associative array.
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        if (empty($config['phone_number_id'])) {
            throw new InvalidConfigException('The "phone_number_id" configuration value is required.');
        }

        if (empty($config['access_token'])) {
            throw new InvalidConfigException('The "access_token" configuration value is required.');
        }

        return new self(
            phoneNumberId: (string) $config['phone_number_id'],
            accessToken: (string) $config['access_token'],
            apiVersion: (string) ($config['api_version'] ?? 'v19.0'),
            baseUrl: (string) ($config['base_url'] ?? 'https://graph.facebook.com'),
            defaultTemplateName: (string) ($config['default_template_name'] ?? 'otp_authentication'),
            defaultLanguageCode: (string) ($config['default_language_code'] ?? 'en_US'),
            otpLength: (int) ($config['otp_length'] ?? 6),
            otpExpiry: (int) ($config['otp_expiry'] ?? 300),
            alphanumericOtp: (bool) ($config['alphanumeric_otp'] ?? false),
        );
    }
}
