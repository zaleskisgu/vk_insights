<?php

namespace App\Integration\Vk\Exception;

class VkApiException extends VkIntegrationException
{
    public function __construct(
        string $message,
        private string $method,
        private int $vkErrorCode,
        private string $vkErrorMessage = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getVkErrorCode(): int
    {
        return $this->vkErrorCode;
    }

    public function getVkErrorMessage(): string
    {
        return $this->vkErrorMessage;
    }

    public function getHttpStatus(): int
    {
        return 502;
    }
}
