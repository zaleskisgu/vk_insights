<?php

namespace App\Integration\Vk;

use App\Contracts\VkClient;
use App\Data\Vk\WallGetResultData;
use App\Integration\Vk\Mock\MockGroupsGetByIdResponse;
use App\Integration\Vk\Mock\MockWallGetItems;

/**
 * Реализация {@see VkClient} на моках VK-формата из {@see Mock\}.
 */
class MockVkClient implements VkClient
{
    public function getGroupById(int|string $groupId): array
    {
        return MockGroupsGetByIdResponse::forGroupId($groupId);
    }

    public function getWall(int $ownerId, int $count = 100, int $offset = 0): array
    {
        $items = MockWallGetItems::all($ownerId);
        $sliced = array_slice($items, $offset, max(0, $count));

        return (new WallGetResultData(count($items), $sliced))->toArray();
    }
}
