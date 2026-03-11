<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Laravel\Facades;

use Dev1\Whatspass\OtpMessage;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string generateOtp(?int $length = null, ?bool $alphanumeric = null)
 * @method static array  sendOtp(string $phoneNumber, string $otp, array $options = [])
 * @method static array  generateAndSend(string $phoneNumber, array $options = [])
 * @method static array  send(OtpMessage $message)
 *
 * @see \Dev1\Whatspass\WhatspassService
 */
class Whatspass extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatspass';
    }
}
