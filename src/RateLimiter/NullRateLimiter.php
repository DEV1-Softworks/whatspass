<?php

declare(strict_types=1);

namespace Dev1\Whatspass\RateLimiter;

use Dev1\Whatspass\Contracts\RateLimiterInterface;

/**
 * No-op rate limiter — allows every attempt through.
 *
 * This is the default implementation when no rate limiter is configured.
 * Replace it with a real implementation (e.g. backed by Redis or a database)
 * to enforce per-phone-number OTP sending limits in production.
 */
class NullRateLimiter implements RateLimiterInterface
{
    public function attempt(string $phoneNumber): void
    {
        // No rate limiting applied.
    }
}
