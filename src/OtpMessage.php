<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

use Dev1\Whatspass\Exceptions\InvalidPhoneNumberException;
use InvalidArgumentException;

class OtpMessage
{
    private readonly string $to;

    public function __construct(
        string $to,
        private readonly string $otp,
        private readonly MessageType $type = MessageType::Template,
        private readonly ?string $templateName = null,
        private readonly string $languageCode = 'en_US',
        private readonly ?string $customMessage = null,
    ) {
        if (empty(trim($otp))) {
            throw new InvalidArgumentException('OTP code cannot be empty.');
        }

        $this->to = $this->normalizePhoneNumber($to);
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getOtp(): string
    {
        return $this->otp;
    }

    public function getType(): MessageType
    {
        return $this->type;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }

    /**
     * Build the API payload for the Meta WhatsApp Cloud API.
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(string $defaultTemplateName, string $defaultLanguageCode): array
    {
        if ($this->type === MessageType::Template) {
            return $this->buildTemplatePayload($defaultTemplateName, $defaultLanguageCode);
        }

        return $this->buildTextPayload();
    }

    /**
     * Normalize a phone number to E.164 format.
     *
     * @throws InvalidPhoneNumberException
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Guard against excessively long input before regex processing
        if (strlen($phone) > 30) {
            throw new InvalidPhoneNumberException('Phone number is too long.');
        }

        // Strip whitespace, dashes, dots, and parentheses
        $normalized = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        if ($normalized === null || $normalized === '') {
            throw new InvalidPhoneNumberException("Invalid phone number: {$phone}");
        }

        // Ensure it starts with +
        if (!str_starts_with($normalized, '+')) {
            $normalized = '+' . $normalized;
        }

        // Validate E.164 format: + followed by 7–15 digits, first digit non-zero
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $normalized)) {
            throw new InvalidPhoneNumberException(
                "Phone number \"{$phone}\" is not a valid E.164 international format (e.g. +15551234567)."
            );
        }

        return $normalized;
    }

    /**
     * Build a template-based message payload for Meta WhatsApp API.
     *
     * @return array<string, mixed>
     */
    private function buildTemplatePayload(string $defaultTemplateName, string $defaultLanguageCode): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $this->to,
            'type' => 'template',
            'template' => [
                'name' => $this->templateName ?? $defaultTemplateName,
                'language' => [
                    'code' => !empty($this->languageCode) ? $this->languageCode : $defaultLanguageCode,
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $this->otp,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a free-form text message payload.
     *
     * @return array<string, mixed>
     */
    private function buildTextPayload(): array
    {
        if ($this->customMessage !== null) {
            $body = str_replace('{otp}', $this->otp, $this->customMessage);
        } else {
            $body = "Your verification code is: {$this->otp}. Do not share it with anyone.";
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $this->to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ];
    }
}
