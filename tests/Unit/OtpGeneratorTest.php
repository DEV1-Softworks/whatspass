<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit;

use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\Tests\TestCase;
use InvalidArgumentException;

class OtpGeneratorTest extends TestCase
{
    private OtpGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new OtpGenerator();
    }

    public function test_generates_numeric_otp_with_default_length(): void
    {
        $otp = $this->generator->generate();

        $this->assertSame(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function test_generates_numeric_otp_with_custom_length(): void
    {
        $otp = $this->generator->generate(length: 8);

        $this->assertSame(8, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $otp);
    }

    public function test_generates_minimum_length_otp(): void
    {
        $otp = $this->generator->generate(length: 4);

        $this->assertSame(4, strlen($otp));
    }

    public function test_generates_maximum_length_otp(): void
    {
        $otp = $this->generator->generate(length: 12);

        $this->assertSame(12, strlen($otp));
    }

    public function test_numeric_otp_contains_only_digits(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $otp = $this->generator->generate(alphanumeric: false);
            $this->assertMatchesRegularExpression('/^\d+$/', $otp, "OTP \"{$otp}\" contains non-digit characters.");
        }
    }

    public function test_generates_alphanumeric_otp(): void
    {
        $otp = $this->generator->generate(length: 8, alphanumeric: true);

        $this->assertSame(8, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{8}$/', $otp);
    }

    public function test_alphanumeric_otp_uses_expected_character_set(): void
    {
        // Generate many codes and ensure no invalid characters appear
        for ($i = 0; $i < 20; $i++) {
            $otp = $this->generator->generate(length: 10, alphanumeric: true);
            $this->assertMatchesRegularExpression(
                '/^[0-9A-Za-z]+$/',
                $otp,
                "Alphanumeric OTP \"{$otp}\" contains unexpected characters."
            );
        }
    }

    public function test_generates_different_codes_statistically(): void
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->generator->generate();
        }

        // With 10^6 possible 6-digit codes, the chance all 10 are identical is negligible
        $this->assertGreaterThan(1, count(array_unique($codes)));
    }

    public function test_throws_exception_for_length_below_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 4 and 12/i');

        $this->generator->generate(length: 3);
    }

    public function test_throws_exception_for_length_above_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 4 and 12/i');

        $this->generator->generate(length: 13);
    }
}
