<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit;

use Dev1\Whatspass\Exceptions\InvalidPhoneNumberException;
use Dev1\Whatspass\MessageType;
use Dev1\Whatspass\OtpMessage;
use Dev1\Whatspass\Tests\TestCase;
use InvalidArgumentException;

class OtpMessageTest extends TestCase
{
    public function test_creates_message_with_valid_e164_phone_number(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $this->assertSame('+15551234567', $message->getTo());
        $this->assertSame('123456', $message->getOtp());
    }

    public function test_normalizes_phone_number_without_plus_sign(): void
    {
        $message = new OtpMessage(to: '15551234567', otp: '654321');

        $this->assertSame('+15551234567', $message->getTo());
    }

    public function test_normalizes_phone_number_with_spaces(): void
    {
        $message = new OtpMessage(to: '+1 555 123 4567', otp: '111222');

        $this->assertSame('+15551234567', $message->getTo());
    }

    public function test_normalizes_phone_number_with_dashes(): void
    {
        $message = new OtpMessage(to: '+1-555-123-4567', otp: '999888');

        $this->assertSame('+15551234567', $message->getTo());
    }

    public function test_normalizes_phone_number_with_parentheses(): void
    {
        $message = new OtpMessage(to: '+1(555)1234567', otp: '777666');

        $this->assertSame('+15551234567', $message->getTo());
    }

    public function test_throws_for_invalid_phone_number(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        new OtpMessage(to: 'not-a-phone', otp: '123456');
    }

    public function test_throws_for_phone_number_starting_with_zero(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        new OtpMessage(to: '+05551234567', otp: '123456');
    }

    public function test_throws_for_too_short_phone_number(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        new OtpMessage(to: '+123456', otp: '123456'); // only 6 digits after +1, needs 7–15
    }

    public function test_throws_for_phone_number_exceeding_max_length(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);

        new OtpMessage(to: str_repeat('1', 31), otp: '123456');
    }

    public function test_throws_for_empty_otp(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/OTP code cannot be empty/i');

        new OtpMessage(to: '+15551234567', otp: '');
    }

    public function test_throws_for_whitespace_otp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OtpMessage(to: '+15551234567', otp: '   ');
    }

    public function test_default_message_type_is_template(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $this->assertSame(MessageType::Template, $message->getType());
    }

    public function test_can_set_text_message_type(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456', type: MessageType::Text);

        $this->assertSame(MessageType::Text, $message->getType());
    }

    public function test_template_name_defaults_to_null(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $this->assertNull($message->getTemplateName());
    }

    public function test_can_set_custom_template_name(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456', templateName: 'my_custom_otp');

        $this->assertSame('my_custom_otp', $message->getTemplateName());
    }

    public function test_language_code_defaults_to_en_us(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $this->assertSame('en_US', $message->getLanguageCode());
    }

    public function test_custom_message_defaults_to_null(): void
    {
        $message = new OtpMessage(to: '+15551234567', otp: '123456');

        $this->assertNull($message->getCustomMessage());
    }

    public function test_builds_template_api_payload(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '789012',
            type: MessageType::Template,
        );

        $payload = $message->toApiPayload('otp_auth', 'en_US');

        $this->assertSame('whatsapp', $payload['messaging_product']);
        $this->assertSame('+15551234567', $payload['to']);
        $this->assertSame('template', $payload['type']);
        $this->assertSame('otp_auth', $payload['template']['name']);
        $this->assertSame('en_US', $payload['template']['language']['code']);
        $this->assertSame('789012', $payload['template']['components'][0]['parameters'][0]['text']);
    }

    public function test_template_payload_uses_custom_template_name_over_default(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '123456',
            type: MessageType::Template,
            templateName: 'custom_template',
        );

        $payload = $message->toApiPayload('default_template', 'en_US');

        $this->assertSame('custom_template', $payload['template']['name']);
    }

    public function test_builds_text_api_payload_with_default_body(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '654321',
            type: MessageType::Text,
        );

        $payload = $message->toApiPayload('otp_auth', 'en_US');

        $this->assertSame('whatsapp', $payload['messaging_product']);
        $this->assertSame('+15551234567', $payload['to']);
        $this->assertSame('text', $payload['type']);
        $this->assertStringContainsString('654321', $payload['text']['body']);
    }

    public function test_builds_text_api_payload_with_custom_message(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '111222',
            type: MessageType::Text,
            customMessage: 'Your code is {otp}. Valid for 5 minutes.',
        );

        $payload = $message->toApiPayload('otp_auth', 'en_US');

        $this->assertSame('Your code is 111222. Valid for 5 minutes.', $payload['text']['body']);
    }

    public function test_text_payload_does_not_contain_template_key(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '123456',
            type: MessageType::Text,
        );

        $payload = $message->toApiPayload('otp_auth', 'en_US');

        $this->assertArrayNotHasKey('template', $payload);
    }

    public function test_template_payload_does_not_contain_text_key(): void
    {
        $message = new OtpMessage(
            to: '+15551234567',
            otp: '123456',
            type: MessageType::Template,
        );

        $payload = $message->toApiPayload('otp_auth', 'en_US');

        $this->assertArrayNotHasKey('text', $payload);
    }
}
