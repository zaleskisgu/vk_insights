<?php

namespace App\Contracts;

interface VkClient
{
    /**
     * @param  int|string  $groupId  числовой id или short name для {@see groups.getById}
     */
    public function getGroupById(int|string $groupId): array;

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array;
}
