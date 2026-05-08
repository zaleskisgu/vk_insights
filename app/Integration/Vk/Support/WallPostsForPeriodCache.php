<?php

namespace App\Integration\Vk\Support;

use App\Data\Post\PostListItemData;
use Illuminate\Cache\CacheManager;

/**
 * Кэш полной выборки стены + карточки группы за период (ключ vk:wall:…, см. {@see self::payloadKey}).
 */
final class WallPostsForPeriodCache
{
    public function __construct(
        private CacheManager $cache,
    ) {}

    /**
     * @param  callable(): array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}  $callback
     * @return array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}
     */
    public function remember(string $ownerKey, string $fromDate, string $toDate, callable $callback): array
    {
        $ttl = (int) config('vk.cache_ttl', 1200);
        if ($ttl <= 0) {
            return $callback();
        }

        $payloadKey = $this->payloadKey($ownerKey, $fromDate, $toDate);
        $cached = $this->cache->get($payloadKey);
        if ($cached !== null && is_array($cached)) {
            return $this->hydrate($cached);
        }

        $lockKey = $this->lockKey($ownerKey, $fromDate, $toDate);
        $lock = $this->cache->lock($lockKey, 30);

        try {
            $lock->block(15);
            $cached = $this->cache->get($payloadKey);
            if ($cached !== null && is_array($cached)) {
                return $this->hydrate($cached);
            }

            $result = $callback();
            $this->cache->put($payloadKey, $this->dehydrate($result), $ttl);

            return $result;
        } finally {
            optional($lock)->release();
        }
    }

    private function payloadKey(string $ownerKey, string $fromDate, string $toDate): string
    {
        return "vk:wall:{$ownerKey}:{$fromDate}:{$toDate}";
    }

    private function lockKey(string $ownerKey, string $fromDate, string $toDate): string
    {
        return "vk:wall:lock:{$ownerKey}:{$fromDate}:{$toDate}";
    }

    /**
     * @param  array{group: array<string, mixed>, posts: list<array<string, mixed>>, truncated?: bool, limit?: int}  $row
     * @return array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}
     */
    private function hydrate(array $row): array
    {
        $posts = [];
        foreach ($row['posts'] as $p) {
            $posts[] = PostListItemData::fromArray($p);
        }

        return [
            'group' => $row['group'],
            'posts' => $posts,
            'truncated' => (bool) ($row['truncated'] ?? false),
            'limit' => (int) ($row['limit'] ?? 0),
        ];
    }

    /**
     * @param  array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}  $result
     * @return array{group: array<string, mixed>, posts: list<array<string, mixed>>, truncated: bool, limit: int}
     */
    private function dehydrate(array $result): array
    {
        $posts = [];
        foreach ($result['posts'] as $p) {
            $posts[] = $p->toArray();
        }

        return [
            'group' => $result['group'],
            'posts' => $posts,
            'truncated' => $result['truncated'],
            'limit' => $result['limit'],
        ];
    }
}
