<?php

namespace App\Integration\Vk\Exception;

final class VkGroupNotFoundException extends VkApiException
{
    public function getHttpStatus(): int
    {
        return 404;
    }
}
