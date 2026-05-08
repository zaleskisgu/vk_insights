<?php

namespace App\Integration\Vk\Exception;

final class VkClosedCommunityException extends VkApiException
{
    public function getHttpStatus(): int
    {
        return 422;
    }
}
