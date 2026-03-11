<?php

declare(strict_types=1);

namespace Dev1\Whatspass\Exceptions;

class ApiException extends WhatspassException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $apiError = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getApiError(): array
    {
        return $this->apiError;
    }
}
