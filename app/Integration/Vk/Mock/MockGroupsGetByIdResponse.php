<?php

namespace App\Integration\Vk\Mock;

use App\Data\Vk\GroupsGetByIdResultData;
use App\Data\Vk\VkGroupData;

/**
 * Мок ответа приложения для {@see \App\Contracts\VkClient::getGroupById} (ключи groups / profiles).
 */
final class MockGroupsGetByIdResponse
{
    /**
     * @return array{groups: list<array<string, mixed>>, profiles: list<mixed>}
     */
    public static function forGroupId(int|string $groupId): array
    {
        $id = self::normalizeId($groupId);

        $group = new VkGroupData(
            id: $id,
            name: 'Mock Group',
            screen_name: 'mockgroup',
            is_closed: 0,
            type: 'group',
            photo_50: '/media/vk/group-photo.svg',
            photo_100: '/media/vk/group-photo.svg',
            photo_200: '/media/vk/group-photo.svg',
            members_count: null,
        );

        return (new GroupsGetByIdResultData([$group], []))->toArray();
    }

    private static function normalizeId(int|string $groupId): int
    {
        if (is_int($groupId)) {
            return $groupId > 0 ? $groupId : 1;
        }

        if (is_numeric($groupId)) {
            $n = (int) $groupId;

            return $n > 0 ? $n : 1;
        }

        $h = (int) (sprintf('%u', crc32((string) $groupId)) % 200_000_000);

        return $h > 0 ? $h : 1;
    }
}
