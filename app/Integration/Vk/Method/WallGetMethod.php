<?php

namespace App\Integration\Vk\Method;

use App\Integration\Vk\HttpVkClient;

/**
 * Обёртка над wall.get: только имя метода и параметры.
 */
final class WallGetMethod
{
    public function __construct(
        private HttpVkClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $params  owner_id, count, offset, …
     * @return array<string, mixed>
     */
    public function execute(array $params): array
    {
        return $this->client->callMethod('wall.get', $params);
    }
}
