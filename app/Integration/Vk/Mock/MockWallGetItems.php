<?php

namespace App\Integration\Vk\Mock;

/**
 * Мок элементов стены VK API {@see https://dev.vk.com/ru/method/wall.get wall.get} (массив items).
 */
final class MockWallGetItems
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(int $ownerId): array
    {
        $base = (int) time();

        return [
            self::makeWallPost(101, $ownerId, $base - 86_400, 'Пост с текстом', 'post', [], 12, 3, 1),
            self::makeWallPost(
                102,
                $ownerId,
                $base - 43_200,
                'Пост с фото',
                'post',
                [
                    [
                        'type' => 'photo',
                        'photo' => [
                            'id' => 1,
                            'album_id' => -1,
                            'owner_id' => $ownerId,
                            'date' => $base - 43_200,
                            'sizes' => [
                                [
                                    'type' => 'm',
                                    'url' => 'https://sun9-1.userapi.com/s/photo-1_1.jpg',
                                    'width' => 510,
                                    'height' => 320,
                                ],
                            ],
                        ],
                    ],
                ],
                20,
                0,
                0,
            ),
            self::makeWallPost(103, $ownerId, $base - 3600, 'Видео', 'post', [
                [
                    'type' => 'video',
                    'video' => [
                        'id' => 1,
                        'owner_id' => $ownerId,
                        'title' => 'Mock video',
                        'duration' => 120,
                        'views' => 1000,
                    ],
                ],
            ], 5, 0, 0),
            self::makeWallPost(104, $ownerId, $base - 1800, 'Со ссылкой', 'post', [
                [
                    'type' => 'link',
                    'link' => [
                        'url' => 'https://dev.vk.com/ru',
                        'title' => 'VK for developers',
                        'description' => 'Mock link attachment',
                    ],
                ],
            ], 2, 0, 0),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     * @return array<string, mixed>
     */
    private static function makeWallPost(
        int $postId,
        int $ownerId,
        int $date,
        string $text,
        string $postType,
        array $attachments,
        int $likesCount,
        int $commentsCount,
        int $repostsCount,
    ): array {
        return [
            'id' => $postId,
            'owner_id' => $ownerId,
            'from_id' => $ownerId,
            'date' => $date,
            'text' => $text,
            'post_type' => $postType,
            'is_favorite' => false,
            'text_post_id' => 0,
            'marked_as_ads' => 0,
            'is_archived' => false,
            'attachments' => $attachments,
            'comments' => [
                'count' => $commentsCount,
                'can_post' => 0,
                'groups_can_post' => 0,
            ],
            'likes' => [
                'count' => $likesCount,
                'user_likes' => 0,
                'can_like' => 1,
                'can_publish' => 0,
            ],
            'reposts' => [
                'count' => $repostsCount,
                'user_reposted' => 0,
            ],
            'views' => [
                'count' => 1000,
            ],
        ];
    }
}
