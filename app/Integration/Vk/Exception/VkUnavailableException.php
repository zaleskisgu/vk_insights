<?php

namespace App\Integration\Vk\Exception;

final class VkUnavailableException extends VkIntegrationException
{
    public function getHttpStatus(): int
    {
        return 503;
    }
}
