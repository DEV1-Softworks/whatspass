<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit\RateLimiter;

use Dev1\Whatspass\Contracts\RateLimiterInterface;
use Dev1\Whatspass\RateLimiter\NullRateLimiter;
use Dev1\Whatspass\Tests\TestCase;

class NullRateLimiterTest extends TestCase
{
    public function test_implements_rate_limiter_interface(): void
    {
        $this->assertInstanceOf(RateLimiterInterface::class, new NullRateLimiter());
    }

    public function test_attempt_does_not_throw(): void
    {
        $limiter = new NullRateLimiter();

        // Should silently allow unlimited attempts
        $limiter->attempt('+15551234567');
        $limiter->attempt('+15551234567');
        $limiter->attempt('+15551234567');

        $this->assertTrue(true);
    }
}
