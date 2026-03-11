<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Exceptions;

class RateLimitExceededException extends WhatspassException
{
    public function __construct(string $phoneNumber)
    {
        parent::__construct(
            'OTP rate limit exceeded for the given phone number.',
        );
    }
}
