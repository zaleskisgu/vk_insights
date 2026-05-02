<?php

namespace App\Integration\Vk\Mock;

/**
 * Мок ответа VK API {@see https://dev.vk.com/ru/method/groups.getById groups.getById} (тело response).
 */
final class MockGroupsGetByIdResponse
{
    /**
     * @return array{groups: list<array<string, mixed>>, profiles: list<mixed>}
     */
    public static function forGroupId(int $groupId): array
    {
        $id = $groupId > 0 ? $groupId : 1;

        return [
            'groups' => [
                [
                    'id' => $id,
                    'name' => 'Mock Group',
                    'screen_name' => 'mockgroup',
                    'is_closed' => 0,
                    'type' => 'group',
                    'photo_50' => '/media/vk/group-photo.svg',
                    'photo_100' => '/media/vk/group-photo.svg',
                    'photo_200' => '/media/vk/group-photo.svg',
                ],
            ],
            'profiles' => [],
        ];
    }
}
