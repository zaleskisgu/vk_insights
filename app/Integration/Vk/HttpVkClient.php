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
        if ($this->accessToken === '') {
            throw new \RuntimeException('groups.getById: пустой VK service token.');
        }
        throw new \RuntimeException('groups.getById: реализация через VK API в процессе. API v'.$this->apiVersion);
    }

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array
    {
        if ($this->accessToken === '') {
            throw new \RuntimeException('wall.get: пустой VK service token.');
        }
        throw new \RuntimeException('wall.get: реализация через VK API в процессе. API v'.$this->apiVersion);
    }
}
