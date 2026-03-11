<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Contracts;

use Dev1\Whatspass\OtpMessage;

interface WhatspassServiceInterface
{
    /**
     * Generate a new OTP code.
     */
    public function generateOtp(?int $length = null, ?bool $alphanumeric = null): string;

    /**
     * Send an OTP to the given phone number.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendOtp(string $phoneNumber, string $otp, array $options = []): array;

    /**
     * Generate an OTP and send it in one step.
     *
     * Returns an array with 'otp' and 'response' keys.
     *
     * @param  array<string, mixed>  $options
     * @return array{otp: string, response: array<string, mixed>}
     */
    public function generateAndSend(string $phoneNumber, array $options = []): array;

    /**
     * Send a pre-built OtpMessage instance.
     *
     * @return array<string, mixed>
     */
    public function send(OtpMessage $message): array;
}
