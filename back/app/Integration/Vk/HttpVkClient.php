<?php

namespace App\Integration\Vk;

use App\Contracts\VkClient;

class HttpVkClient implements VkClient
{
    public function __construct(
        private string $accessToken,
        private string $apiVersion,
    ) {}

    public function getGroupById(int $groupId): array
    {
        throw new \RuntimeException('groups.getById: реализация через VK API в процессе.');
    }

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array
    {
        throw new \RuntimeException('wall.get: реализация через VK API в процессе.');
    }
}
