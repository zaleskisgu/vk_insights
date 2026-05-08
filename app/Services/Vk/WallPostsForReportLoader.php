<?php

namespace App\Services\Vk;

use App\Contracts\VkClient;
use App\Data\Post\PostListItemData;
use App\Integration\Vk\VkGroupIdsQuery;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class WallPostsForReportLoader
{
    private const MAX_WALL_PAGES = 50;

    public function __construct(
        private VkClient $vk,
    ) {}

    /**
     * @return array{group: array<string, mixed>, posts: list<PostListItemData>}
     */
    public function loadGroupAndPostsInPeriod(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $param = VkGroupIdsQuery::fromUserInput($groupInput);
        $groupVk = $this->vk->getGroupById($param);
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

        $rawItems = $this->fetchWallItemsInWindow($ownerId, $fromTs, $toTs);
        $posts = [];
        foreach ($rawItems as $row) {
            $posts[] = VkWallPostNormalizer::toPostListItem($row);
        }

        return ['group' => $first, 'posts' => $posts];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchWallItemsInWindow(int $ownerId, int $fromTs, int $toTs): array
    {
        $offset = 0;
        $all = [];

        for ($page = 0; $page < self::MAX_WALL_PAGES; ++$page) {
            $wall = $this->vk->getWall($ownerId, 100, $offset);
            $items = $wall['items'] ?? [];
            if (! is_array($items) || $items === []) {
                break;
            }

            $stopped = false;
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $d = (int) ($item['date'] ?? 0);
                if ($d > $toTs) {
                    continue;
                }
                if ($d < $fromTs) {
                    $stopped = true;
                    break;
                }
                $all[] = $item;
            }

            if ($stopped) {
                break;
            }

            if (count($items) < 100) {
                break;
            }

            $offset += 100;
        }

        return $all;
    }
}
