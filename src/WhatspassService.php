<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

use Dev1\Whatspass\Contracts\RateLimiterInterface;
use Dev1\Whatspass\Contracts\WhatspassServiceInterface;
use Dev1\Whatspass\RateLimiter\NullRateLimiter;

class WhatspassService implements WhatspassServiceInterface
{
    public function __construct(
        private readonly WhatspassConfig $config,
        private readonly WhatspassClient $client,
        private readonly OtpGenerator $generator,
        private readonly RateLimiterInterface $rateLimiter = new NullRateLimiter(),
    ) {}

    /**
     * Generate a new OTP code.
     *
     * Falls back to the configured defaults if parameters are not provided.
     */
    public function generateOtp(?int $length = null, ?bool $alphanumeric = null): string
    {
        return $this->generator->generate(
            length: $length ?? $this->config->otpLength,
            alphanumeric: $alphanumeric ?? $this->config->alphanumericOtp,
        );
    }

    /**
     * Send an OTP to the given phone number.
     *
     * Supported options:
     *   - type          (string)  'template' or 'text'     (default: 'template')
     *   - template_name (string)  Override the template name
     *   - language_code (string)  Override the language code
     *   - custom_message (string) Custom text body with optional {otp} placeholder
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendOtp(string $phoneNumber, string $otp, array $options = []): array
    {
        $this->rateLimiter->attempt($phoneNumber);

        $message = new OtpMessage(
            to: $phoneNumber,
            otp: $otp,
            type: MessageType::from($options['type'] ?? MessageType::Template->value),
            templateName: $options['template_name'] ?? null,
            languageCode: $options['language_code'] ?? $this->config->defaultLanguageCode,
            customMessage: $options['custom_message'] ?? null,
        );

        return $this->client->sendMessage($message);
    }

    /**
     * Generate an OTP and send it to the given phone number in one step.
     *
     * Returns an array with:
     *   - otp      (string) The generated OTP code
     *   - response (array)  The raw Meta API response
     *
     * @param  array<string, mixed>  $options
     * @return array{otp: string, response: array<string, mixed>}
     */
    public function generateAndSend(string $phoneNumber, array $options = []): array
    {
        $otp = $this->generateOtp(
            length: isset($options['otp_length']) ? (int) $options['otp_length'] : null,
            alphanumeric: isset($options['alphanumeric_otp']) ? (bool) $options['alphanumeric_otp'] : null,
        );

        $response = $this->sendOtp($phoneNumber, $otp, $options);

        return [
            'otp' => $otp,
            'response' => $response,
        ];
    }

    /**
     * Send a pre-built OtpMessage instance directly.
     *
     * @return array<string, mixed>
     */
    public function send(OtpMessage $message): array
    {
        $this->rateLimiter->attempt($message->getTo());

        return $this->client->sendMessage($message);
    }
}
