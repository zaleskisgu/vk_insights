<?php

namespace App\Integration\Vk\Exception;

abstract class VkIntegrationException extends \RuntimeException
{
    abstract public function getHttpStatus(): int;
}
