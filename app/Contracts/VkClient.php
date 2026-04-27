<?php

namespace App\Contracts;

interface VkClient
{
    public function getGroupById(int $groupId): array;

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array;
}
