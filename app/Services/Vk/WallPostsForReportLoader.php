<?php

namespace App\Services\Vk;

use App\Contracts\VkClient;
use App\Data\Post\PostListItemData;
use App\Integration\Vk\Support\VkGroupInputParser;
use App\Integration\Vk\Support\WallPostsForPeriodCache;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class WallPostsForReportLoader
{
    private const DEFAULT_MAX_WALL_PAGES = 50;

    private const DEFAULT_PAGE_SIZE = 100;

    public function __construct(
        private VkClient $vk,
        private ?WallPostsForPeriodCache $periodCache = null,
    ) {}

    public function postsLimit(): int
    {
        return $this->maxPages() * $this->pageSize();
    }

    private function maxPages(): int
    {
        return max(1, (int) config('vk.wall.max_pages', self::DEFAULT_MAX_WALL_PAGES));
    }

    private function pageSize(): int
    {
        return min(100, max(1, (int) config('vk.wall.page_size', self::DEFAULT_PAGE_SIZE)));
    }

    /**
     * @return array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}
     */
    public function loadGroupAndPostsInPeriod(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $query = VkGroupInputParser::parse($groupInput)->query;

        if (config('vk.use_mock', true) || (int) config('vk.cache_ttl', 0) <= 0 || $this->periodCache === null) {
            return $this->loadUncachedForQuery($query, $from, $to);
        }

        $fromDate = Carbon::instance($from)->startOfDay()->toDateString();
        $toDate = Carbon::instance($to)->startOfDay()->toDateString();
        $ownerKey = $this->wallOwnerKeyForCache($query);

        return $this->periodCache->remember(
            $ownerKey,
            $fromDate,
            $toDate,
            fn (): array => $this->loadUncachedForQuery($query, $from, $to),
        );
    }

    /**
     * Идентификатор стены для ключа кэша: для числового id группы — отрицательный owner_id (-123), иначе screen name из API-параметра.
     */
    private function wallOwnerKeyForCache(string $groupIdsParam): string
    {
        if (preg_match('/^-?\d+$/', $groupIdsParam)) {
            $n = (int) $groupIdsParam;

            return (string) ($n > 0 ? -$n : $n);
        }

        return $groupIdsParam;
    }

    /**
     * @return array{group: array<string, mixed>, posts: list<PostListItemData>, truncated: bool, limit: int}
     */
    private function loadUncachedForQuery(string $groupIdsQuery, CarbonInterface $from, CarbonInterface $to): array
    {
        $groupVk = $this->vk->getGroupById($groupIdsQuery);
        $first = $groupVk['groups'][0] ?? null;
        if (! is_array($first) || (int) ($first['id'] ?? 0) === 0) {
            throw new \RuntimeException('Сообщество не найдено или недоступно.');
        }

        $id = (int) $first['id'];
        $ownerId = $id > 0 ? -$id : $id;

        $fromC = Carbon::instance($from)->startOfDay();
        $toC = Carbon::instance($to)->endOfDay();
        $fromTs = $fromC->timestamp;
        $toTs = $toC->timestamp;

        ['items' => $rawItems, 'truncated' => $truncated] = $this->fetchWallItemsInWindow($ownerId, $fromTs, $toTs);
        $posts = [];
        foreach ($rawItems as $row) {
            $posts[] = VkWallPostNormalizer::toPostListItem($row);
        }

        return [
            'group' => $first,
            'posts' => $posts,
            'truncated' => $truncated,
            'limit' => $this->postsLimit(),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, truncated: bool}
     */
    private function fetchWallItemsInWindow(int $ownerId, int $fromTs, int $toTs): array
    {
        $maxPages = $this->maxPages();
        $pageSize = $this->pageSize();
        $offset = 0;
        $all = [];
        $reachedPeriodStart = false;
        $reachedWallEnd = false;

        for ($page = 0; $page < $maxPages; $page++) {
            $wall = $this->vk->getWall($ownerId, $pageSize, $offset);
            $items = $wall['items'] ?? [];
            if (! is_array($items) || $items === []) {
                $reachedWallEnd = true;
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $d = (int) ($item['date'] ?? 0);
                if ($d > $toTs) {
                    continue;
                }
                if ($d < $fromTs) {
                    $reachedPeriodStart = true;
                    break;
                }
                $all[] = $item;
            }

            if ($reachedPeriodStart) {
                break;
            }

            if (count($items) < $pageSize) {
                $reachedWallEnd = true;
                break;
            }

            $offset += $pageSize;
        }

        return [
            'items' => $all,
            'truncated' => ! $reachedPeriodStart && ! $reachedWallEnd,
        ];
    }
}
