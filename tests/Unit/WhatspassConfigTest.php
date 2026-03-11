<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit;

use Dev1\Whatspass\Exceptions\InvalidConfigException;
use Dev1\Whatspass\Tests\TestCase;
use Dev1\Whatspass\WhatspassConfig;

class WhatspassConfigTest extends TestCase
{
    public function test_creates_config_with_required_parameters(): void
    {
        $config = new WhatspassConfig(
            phoneNumberId: '123456789',
            accessToken: 'token_abc',
        );

        $this->assertSame('123456789', $config->phoneNumberId);
        $this->assertSame('token_abc', $config->accessToken);
    }

    public function test_creates_config_with_default_values(): void
    {
        $config = new WhatspassConfig(
            phoneNumberId: '123456789',
            accessToken: 'token_abc',
        );

        $this->assertSame('v19.0', $config->apiVersion);
        $this->assertSame('https://graph.facebook.com', $config->baseUrl);
        $this->assertSame('otp_authentication', $config->defaultTemplateName);
        $this->assertSame('en_US', $config->defaultLanguageCode);
        $this->assertSame(6, $config->otpLength);
        $this->assertSame(300, $config->otpExpiry);
        $this->assertFalse($config->alphanumericOtp);
    }

    public function test_creates_config_with_custom_values(): void
    {
        $config = new WhatspassConfig(
            phoneNumberId: 'phone-id',
            accessToken: 'my-token',
            apiVersion: 'v20.0',
            baseUrl: 'https://custom.api.com',
            defaultTemplateName: 'my_otp',
            defaultLanguageCode: 'pt_BR',
            otpLength: 8,
            otpExpiry: 600,
            alphanumericOtp: true,
        );

        $this->assertSame('v20.0', $config->apiVersion);
        $this->assertSame('https://custom.api.com', $config->baseUrl);
        $this->assertSame('my_otp', $config->defaultTemplateName);
        $this->assertSame('pt_BR', $config->defaultLanguageCode);
        $this->assertSame(8, $config->otpLength);
        $this->assertSame(600, $config->otpExpiry);
        $this->assertTrue($config->alphanumericOtp);
    }

    public function test_throws_exception_for_empty_phone_number_id(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/phone_number_id/i');

        new WhatspassConfig(phoneNumberId: '', accessToken: 'token');
    }

    public function test_throws_exception_for_whitespace_phone_number_id(): void
    {
        $this->expectException(InvalidConfigException::class);

        new WhatspassConfig(phoneNumberId: '   ', accessToken: 'token');
    }

    public function test_throws_exception_for_empty_access_token(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/access_token/i');

        new WhatspassConfig(phoneNumberId: '123', accessToken: '');
    }

    public function test_throws_exception_for_otp_length_below_minimum(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/otp_length/i');

        new WhatspassConfig(phoneNumberId: '123', accessToken: 'token', otpLength: 3);
    }

    public function test_throws_exception_for_otp_length_above_maximum(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/otp_length/i');

        new WhatspassConfig(phoneNumberId: '123', accessToken: 'token', otpLength: 13);
    }

    public function test_throws_exception_for_expiry_below_minimum(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/otp_expiry/i');

        new WhatspassConfig(phoneNumberId: '123', accessToken: 'token', otpExpiry: 59);
    }

    public function test_get_api_endpoint_returns_correct_url(): void
    {
        $config = new WhatspassConfig(
            phoneNumberId: '987654321',
            accessToken: 'token',
            apiVersion: 'v19.0',
            baseUrl: 'https://graph.facebook.com',
        );

        $this->assertSame(
            'https://graph.facebook.com/v19.0/987654321/messages',
            $config->getApiEndpoint(),
        );
    }

    public function test_get_api_endpoint_strips_trailing_slash_from_base_url(): void
    {
        $config = new WhatspassConfig(
            phoneNumberId: '123',
            accessToken: 'token',
            baseUrl: 'https://graph.facebook.com/',
        );

        $this->assertStringNotContainsString(
            '//v19.0',
            $config->getApiEndpoint(),
        );
    }

    public function test_from_array_creates_config_with_required_keys(): void
    {
        $config = WhatspassConfig::fromArray([
            'phone_number_id' => 'pid-123',
            'access_token' => 'tok-abc',
        ]);

        $this->assertSame('pid-123', $config->phoneNumberId);
        $this->assertSame('tok-abc', $config->accessToken);
        $this->assertSame('v19.0', $config->apiVersion);
    }

    public function test_from_array_creates_config_with_all_keys(): void
    {
        $config = WhatspassConfig::fromArray([
            'phone_number_id' => 'pid-123',
            'access_token' => 'tok-abc',
            'api_version' => 'v20.0',
            'base_url' => 'https://custom.url',
            'default_template_name' => 'custom_otp',
            'default_language_code' => 'es_ES',
            'otp_length' => 8,
            'otp_expiry' => 120,
            'alphanumeric_otp' => true,
        ]);

        $this->assertSame('v20.0', $config->apiVersion);
        $this->assertSame('es_ES', $config->defaultLanguageCode);
        $this->assertSame(8, $config->otpLength);
        $this->assertTrue($config->alphanumericOtp);
    }

    public function test_from_array_throws_for_missing_phone_number_id(): void
    {
        $this->expectException(InvalidConfigException::class);

        WhatspassConfig::fromArray(['access_token' => 'token']);
    }

    public function test_from_array_throws_for_missing_access_token(): void
    {
        $this->expectException(InvalidConfigException::class);

        WhatspassConfig::fromArray(['phone_number_id' => '123']);
    }
}
