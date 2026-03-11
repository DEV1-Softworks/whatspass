<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit;

use Dev1\Whatspass\Contracts\RateLimiterInterface;
use Dev1\Whatspass\Exceptions\ApiException;
use Dev1\Whatspass\Exceptions\RateLimitExceededException;
use Dev1\Whatspass\MessageType;
use Dev1\Whatspass\OtpGenerator;
use Dev1\Whatspass\OtpMessage;
use Dev1\Whatspass\Tests\TestCase;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use Dev1\Whatspass\WhatspassService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;

class WhatspassServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private WhatspassConfig $config;
    private WhatspassClient&MockInterface $mockClient;
    private WhatspassService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new WhatspassConfig(
            phoneNumberId: '123456789',
            accessToken: 'test-token',
            otpLength: 6,
            alphanumericOtp: false,
        );

        $this->mockClient = Mockery::mock(WhatspassClient::class);

        $this->service = new WhatspassService(
            config: $this->config,
            client: $this->mockClient,
            generator: new OtpGenerator(),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_generate_otp_returns_string(): void
    {
        $otp = $this->service->generateOtp();

        $this->assertIsString($otp);
    }

    public function test_generate_otp_uses_config_length_by_default(): void
    {
        $otp = $this->service->generateOtp();

        $this->assertSame(6, strlen($otp));
    }

    public function test_generate_otp_with_custom_length(): void
    {
        $otp = $this->service->generateOtp(length: 8);

        $this->assertSame(8, strlen($otp));
    }

    public function test_generate_otp_numeric_by_default(): void
    {
        $otp = $this->service->generateOtp();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function test_generate_otp_alphanumeric_when_specified(): void
    {
        $otp = $this->service->generateOtp(alphanumeric: true);

        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{6}$/', $otp);
    }

    public function test_generate_otp_uses_config_alphanumeric_when_not_specified(): void
    {
        $alphanumericConfig = new WhatspassConfig(
            phoneNumberId: '123',
            accessToken: 'token',
            alphanumericOtp: true,
        );

        $service = new WhatspassService(
            config: $alphanumericConfig,
            client: $this->mockClient,
            generator: new OtpGenerator(),
        );

        $otp = $service->generateOtp();

        $this->assertSame(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{6}$/', $otp);
    }

    public function test_send_otp_calls_client_with_template_message(): void
    {
        $expectedResponse = ['messaging_product' => 'whatsapp', 'messages' => [['id' => 'wamid.x']]];

        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (OtpMessage $message) {
                return $message->getTo() === '+15551234567'
                    && $message->getOtp() === '999888'
                    && $message->getType() === MessageType::Template;
            })
            ->andReturn($expectedResponse);

        $result = $this->service->sendOtp('+15551234567', '999888');

        $this->assertSame($expectedResponse, $result);
    }

    public function test_send_otp_calls_client_with_text_message_type(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (OtpMessage $message) {
                return $message->getType() === MessageType::Text;
            })
            ->andReturn(['messages' => [['id' => 'wamid.y']]]);

        $this->service->sendOtp('+15551234567', '123456', ['type' => 'text']);
    }

    public function test_send_otp_passes_custom_template_name(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (OtpMessage $message) {
                return $message->getTemplateName() === 'my_custom_template';
            })
            ->andReturn([]);

        $this->service->sendOtp('+15551234567', '123456', [
            'template_name' => 'my_custom_template',
        ]);
    }

    public function test_send_otp_passes_custom_message_for_text_type(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (OtpMessage $message) {
                return $message->getCustomMessage() === 'Your OTP: {otp}';
            })
            ->andReturn([]);

        $this->service->sendOtp('+15551234567', '123456', [
            'type' => 'text',
            'custom_message' => 'Your OTP: {otp}',
        ]);
    }

    public function test_generate_and_send_returns_otp_and_response(): void
    {
        $apiResponse = ['messages' => [['id' => 'wamid.abc']]];

        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->andReturn($apiResponse);

        $result = $this->service->generateAndSend('+15551234567');

        $this->assertArrayHasKey('otp', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertSame(6, strlen($result['otp']));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result['otp']);
        $this->assertSame($apiResponse, $result['response']);
    }

    public function test_generate_and_send_with_custom_otp_length(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->andReturn([]);

        $result = $this->service->generateAndSend('+15551234567', ['otp_length' => 8]);

        $this->assertSame(8, strlen($result['otp']));
    }

    public function test_generate_and_send_with_alphanumeric_option(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->andReturn([]);

        $result = $this->service->generateAndSend('+15551234567', ['alphanumeric_otp' => true]);

        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{6}$/', $result['otp']);
    }

    public function test_send_method_delegates_to_client(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');
        $expectedResponse = ['messages' => [['id' => 'wamid.direct']]];

        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->with($message)
            ->andReturn($expectedResponse);

        $result = $this->service->send($message);

        $this->assertSame($expectedResponse, $result);
    }

    public function test_send_otp_propagates_api_exception(): void
    {
        $this->mockClient
            ->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new ApiException('API error', 400));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API error');

        $this->service->sendOtp('+15551234567', '123456');
    }

    public function test_send_otp_calls_rate_limiter(): void
    {
        $mockLimiter = Mockery::mock(RateLimiterInterface::class);
        $mockLimiter->shouldReceive('attempt')->once()->with('+15551234567');

        $this->mockClient->shouldReceive('sendMessage')->once()->andReturn([]);

        $service = new WhatspassService(
            config: $this->config,
            client: $this->mockClient,
            generator: new OtpGenerator(),
            rateLimiter: $mockLimiter,
        );

        $service->sendOtp('+15551234567', '123456');
    }

    public function test_send_otp_throws_when_rate_limit_exceeded(): void
    {
        $mockLimiter = Mockery::mock(RateLimiterInterface::class);
        $mockLimiter->shouldReceive('attempt')->once()
            ->andThrow(new RateLimitExceededException('+15551234567'));

        $this->mockClient->shouldReceive('sendMessage')->never();

        $service = new WhatspassService(
            config: $this->config,
            client: $this->mockClient,
            generator: new OtpGenerator(),
            rateLimiter: $mockLimiter,
        );

        $this->expectException(RateLimitExceededException::class);

        $service->sendOtp('+15551234567', '123456');
    }

    public function test_send_method_calls_rate_limiter(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $mockLimiter = Mockery::mock(RateLimiterInterface::class);
        $mockLimiter->shouldReceive('attempt')->once()->with('+15551234567');

        $this->mockClient->shouldReceive('sendMessage')->once()->andReturn([]);

        $service = new WhatspassService(
            config: $this->config,
            client: $this->mockClient,
            generator: new OtpGenerator(),
            rateLimiter: $mockLimiter,
        );

        $service->send($message);
    }

    public function test_send_method_throws_when_rate_limit_exceeded(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $mockLimiter = Mockery::mock(RateLimiterInterface::class);
        $mockLimiter->shouldReceive('attempt')->once()
            ->andThrow(new RateLimitExceededException('+15551234567'));

        $this->mockClient->shouldReceive('sendMessage')->never();

        $service = new WhatspassService(
            config: $this->config,
            client: $this->mockClient,
            generator: new OtpGenerator(),
            rateLimiter: $mockLimiter,
        );

        $this->expectException(RateLimitExceededException::class);

        $service->send($message);
    }
}
