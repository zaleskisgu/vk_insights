<?php

namespace App\Integration\Vk\Method;

use App\Integration\Vk\HttpVkClient;

/**
 * Обёртка над groups.getById: только имя метода и параметры, без бизнес-логики дашборда.
 */
final class GroupsGetByIdMethod
{
    public function __construct(
        private HttpVkClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $params  поля VK API (group_id, fields, …)
     * @return array<string, mixed>  тело ответа VK после decode (ожидается с ключом response или как вернёт клиент)
     */
    public function execute(array $params): array
    {
        return $this->client->callMethod('groups.getById', $params);
    }
}
