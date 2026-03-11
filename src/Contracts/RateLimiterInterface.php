<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Contracts;

use Dev1\Whatspass\Exceptions\RateLimitExceededException;

interface RateLimiterInterface
{
    /**
     * Record an OTP attempt for the given phone number.
     *
     * Implementations must check the rate limit and record the attempt
     * atomically. Throws RateLimitExceededException when the limit is exceeded.
     *
     * @throws RateLimitExceededException
     */
    public function attempt(string $phoneNumber): void;
}
