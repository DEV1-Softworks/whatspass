<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Tests\Unit;

use Dev1\Whatspass\Exceptions\ApiException;
use Dev1\Whatspass\MessageType;
use Dev1\Whatspass\OtpMessage;
use Dev1\Whatspass\Tests\TestCase;
use Dev1\Whatspass\WhatspassClient;
use Dev1\Whatspass\WhatspassConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\AbstractLogger;

class WhatspassClientTest extends TestCase
{
    private WhatspassConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new WhatspassConfig(
            phoneNumberId: '123456789',
            accessToken: 'test-access-token',
        );
    }

    private function makeClient(MockHandler $mock, ?AbstractLogger $logger = null): WhatspassClient
    {
        $stack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $stack]);

        return new WhatspassClient($this->config, $httpClient, $logger);
    }

    private function makeMessage(MessageType $type = MessageType::Template): OtpMessage
    {
        return new OtpMessage(
            to: '+15551234567',
            otp: '123456',
            type: $type,
        );
    }

    public function test_sends_template_message_successfully(): void
    {
        $apiResponse = [
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '+15551234567', 'wa_id' => '15551234567']],
            'messages' => [['id' => 'wamid.abc123']],
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $client = $this->makeClient($mock);
        $result = $client->sendMessage($this->makeMessage());

        $this->assertSame('whatsapp', $result['messaging_product']);
        $this->assertSame('wamid.abc123', $result['messages'][0]['id']);
    }

    public function test_sends_text_message_successfully(): void
    {
        $apiResponse = [
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.text123']],
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($apiResponse)),
        ]);

        $client = $this->makeClient($mock);
        $result = $client->sendMessage($this->makeMessage(MessageType::Text));

        $this->assertSame('wamid.text123', $result['messages'][0]['id']);
    }

    public function test_throws_api_exception_on_400_client_error(): void
    {
        $errorBody = [
            'error' => [
                'message' => 'Invalid phone number.',
                'type' => 'OAuthException',
                'code' => 100,
                'fbtrace_id' => 'trace123',
            ],
        ];

        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode($errorBody)),
        ]);

        $client = $this->makeClient($mock);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid phone number.');
        $this->expectExceptionCode(400);

        $client->sendMessage($this->makeMessage());
    }

    public function test_throws_api_exception_on_401_unauthorized(): void
    {
        $errorBody = [
            'error' => [
                'message' => 'Invalid OAuth access token.',
                'type' => 'OAuthException',
                'code' => 190,
            ],
        ];

        $mock = new MockHandler([
            new Response(401, ['Content-Type' => 'application/json'], json_encode($errorBody)),
        ]);

        $client = $this->makeClient($mock);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);

        $client->sendMessage($this->makeMessage());
    }

    public function test_throws_api_exception_on_connection_failure(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('POST', 'test')),
        ]);

        $client = $this->makeClient($mock);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Failed to connect/i');

        $client->sendMessage($this->makeMessage());
    }

    public function test_api_exception_contains_error_details(): void
    {
        $errorBody = [
            'error' => [
                'message' => 'Template not found.',
                'type' => 'OAuthException',
                'code' => 132001,
                'fbtrace_id' => 'trace456',
            ],
        ];

        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode($errorBody)),
        ]);

        $client = $this->makeClient($mock);

        try {
            $client->sendMessage($this->makeMessage());
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame($errorBody, $e->getApiError());
            $this->assertSame('Template not found.', $e->getMessage());
        }
    }

    public function test_request_includes_correct_authorization_header(): void
    {
        $capturedRequest = null;

        $mock = new MockHandler([
            new Response(200, [], json_encode(['messages' => [['id' => 'wamid.x']]])),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(function (callable $handler) use (&$capturedRequest) {
            return function ($request, $options) use ($handler, &$capturedRequest) {
                $capturedRequest = $request;
                return $handler($request, $options);
            };
        });

        $httpClient = new Client(['handler' => $stack]);
        $client = new WhatspassClient($this->config, $httpClient);
        $client->sendMessage($this->makeMessage());

        $this->assertNotNull($capturedRequest);
        $this->assertSame('Bearer test-access-token', $capturedRequest->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $capturedRequest->getHeaderLine('Content-Type'));
    }

    public function test_request_targets_correct_api_endpoint(): void
    {
        $capturedRequest = null;

        $mock = new MockHandler([
            new Response(200, [], json_encode(['messages' => [['id' => 'wamid.x']]])),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(function (callable $handler) use (&$capturedRequest) {
            return function ($request, $options) use ($handler, &$capturedRequest) {
                $capturedRequest = $request;
                return $handler($request, $options);
            };
        });

        $httpClient = new Client(['handler' => $stack]);
        $client = new WhatspassClient($this->config, $httpClient);
        $client->sendMessage($this->makeMessage());

        $this->assertNotNull($capturedRequest);
        $this->assertStringContainsString('123456789/messages', (string) $capturedRequest->getUri());
    }

    public function test_logs_success_when_logger_provided(): void
    {
        $logs = [];
        $logger = new class ($logs) extends AbstractLogger {
            public function __construct(private array &$logs) {}
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message];
            }
        };

        $mock = new MockHandler([
            new Response(200, [], json_encode(['messages' => [['id' => 'wamid.x']]])),
        ]);

        $client = $this->makeClient($mock, $logger);
        $client->sendMessage($this->makeMessage());

        $levels = array_column($logs, 'level');
        $this->assertContains('info', $levels);
    }

    public function test_logs_error_when_api_returns_client_error(): void
    {
        $logs = [];
        $logger = new class ($logs) extends AbstractLogger {
            public function __construct(private array &$logs) {}
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message];
            }
        };

        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => ['message' => 'Bad request.']])),
        ]);

        $client = $this->makeClient($mock, $logger);

        try {
            $client->sendMessage($this->makeMessage());
        } catch (ApiException) {
            // expected
        }

        $levels = array_column($logs, 'level');
        $this->assertContains('error', $levels);
    }

    public function test_works_without_injected_http_client(): void
    {
        // WhatspassClient should instantiate its own Guzzle client when none is provided.
        // We only check that the object is created without errors — no real HTTP call.
        $client = new WhatspassClient($this->config);
        $this->assertInstanceOf(WhatspassClient::class, $client);
    }
}
