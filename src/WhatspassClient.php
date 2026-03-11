<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

use Dev1\Whatspass\Exceptions\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WhatspassClient
{
    private readonly ClientInterface $httpClient;
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly WhatspassConfig $config,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
        ]);

        $this->logger = $logger ?? new NullLogger();
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= 6) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, 4) . str_repeat('*', $len - 6) . substr($phone, -2);
    }

    /**
     * Send a WhatsApp message via the Meta Cloud API.
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function sendMessage(OtpMessage $message): array
    {
        $payload = $message->toApiPayload(
            $this->config->defaultTemplateName,
            $this->config->defaultLanguageCode,
        );

        $this->logger->debug('Sending WhatsApp OTP message', [
            'to' => $this->maskPhone($message->getTo()),
            'type' => $message->getType()->value,
        ]);

        try {
            $response = $this->httpClient->request('POST', $this->config->getApiEndpoint(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            /** @var array<string, mixed> $body */
            $body = json_decode($response->getBody()->getContents(), true) ?? [];

            $this->logger->info('WhatsApp OTP sent successfully', [
                'to' => $this->maskPhone($message->getTo()),
                'message_id' => $body['messages'][0]['id'] ?? null,
            ]);

            return $body;
        } catch (ClientException $e) {
            $rawError = $e->getResponse()->getBody()->getContents();
            /** @var array<string, mixed> $errorBody */
            $errorBody = json_decode($rawError, true) ?? [];

            $this->logger->error('WhatsApp API client error', [
                'status' => $e->getResponse()->getStatusCode(),
                'error_code' => $errorBody['error']['code'] ?? null,
                'error_type' => $errorBody['error']['type'] ?? null,
            ]);

            throw new ApiException(
                message: $errorBody['error']['message'] ?? 'WhatsApp API request failed.',
                code: $e->getResponse()->getStatusCode(),
                previous: $e,
                apiError: $errorBody,
            );
        } catch (GuzzleException $e) {
            $this->logger->error('WhatsApp API connection error', [
                'error_class' => get_class($e),
            ]);

            throw new ApiException(
                message: 'Failed to connect to the WhatsApp API.',
                code: $e->getCode(),
                previous: $e,
            );
        }
    }
}
