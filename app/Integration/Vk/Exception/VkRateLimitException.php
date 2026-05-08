<?php

namespace App\Integration\Vk\Exception;

final class VkRateLimitException extends VkApiException
{
    public function getHttpStatus(): int
    {
        return 429;
    }
}
