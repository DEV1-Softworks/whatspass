<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

use InvalidArgumentException;

class OtpGenerator
{
    private const NUMERIC_CHARS = '0123456789';
    private const ALPHANUMERIC_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Generate a cryptographically secure OTP code.
     *
     * @throws InvalidArgumentException
     */
    public function generate(int $length = 6, bool $alphanumeric = false): string
    {
        if ($length < 4 || $length > 12) {
            throw new InvalidArgumentException('OTP length must be between 4 and 12 characters.');
        }

        $chars = $alphanumeric ? self::ALPHANUMERIC_CHARS : self::NUMERIC_CHARS;
        $max = strlen($chars) - 1;
        $otp = '';

        for ($i = 0; $i < $length; $i++) {
            $otp .= $chars[random_int(0, $max)];
        }

        return $otp;
    }
}
