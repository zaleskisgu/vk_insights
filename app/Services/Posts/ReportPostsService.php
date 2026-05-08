<?php

namespace App\Services\Posts;

use App\Data\Post\PostListItemData;
use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Vk\WallPostsForReportLoader;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class ReportPostsService
{
    public function __construct(
        private WallPostsForReportLoader $wallPostsLoader,
        private bool $dashboardFromVkWall,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{total: int, filtered: int, page: int, per_page: int, last_page: int}}
     */
    public function listPage(
        string $group,
        CarbonInterface $from,
        CarbonInterface $to,
        int $page,
        int $perPage,
        string $sort,
        string $order,
        ?string $q,
        string $type,
    ): array {
        $fromC = Carbon::instance($from)->startOfDay();
        $toC = Carbon::instance($to)->startOfDay();

        if ($this->dashboardFromVkWall) {
            $loaded = $this->wallPostsLoader->loadGroupAndPostsInPeriod($group, $fromC, $toC);
            $all = array_map(
                static fn (PostListItemData $p): array => $p->toArray(),
                $loaded['posts'],
            );
        } else {
            $fixture = new MockDashboardFixtureProvider($fromC, $toC);
            $all = array_map(
                static fn (PostListItemData $p): array => $p->toArray(),
                $fixture->allPostItems($group),
            );
        }

        $total = count($all);

        $filtered = $all;
        if ($type !== 'all') {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (array $r): bool => $r['type'] === $type,
            ));
        }
        if ($q !== null && $q !== '') {
            $needle = mb_strtolower(trim($q));
            if ($needle !== '') {
                $filtered = array_values(array_filter(
                    $filtered,
                    static function (array $r) use ($needle): bool {
                        return mb_strpos(mb_strtolower($r['text']), $needle) !== false;
                    },
                ));
            }
        }

        $cmp = $this->sorter($sort, $order);
        usort($filtered, $cmp);

        $filteredCount = count($filtered);
        $lastPage = max(1, (int) ceil($filteredCount / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($filtered, $offset, $perPage);

        $data = [];
        foreach ($slice as $i => $row) {
            $data[] = array_merge($row, [
                'row_index' => $offset + $i + 1,
            ]);
        }

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'filtered' => $filteredCount,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ];
    }

    /** @return callable(array<string, mixed>, array<string, mixed>): int */
    private function sorter(string $sort, string $order): callable
    {
        $desc = $order === 'desc';

        return function (array $a, array $b) use ($sort, $desc): int {
            $va = self::sortValue($a, $sort);
            $vb = self::sortValue($b, $sort);

            $c = match (true) {
                is_int($va) && is_int($vb) => $va <=> $vb,
                default => strcmp((string) $va, (string) $vb),
            };

            if ($c !== 0) {
                return $desc ? -$c : $c;
            }

            return ($a['post_id'] ?? 0) <=> ($b['post_id'] ?? 0);
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function sortValue(array $row, string $sort): int|string
    {
        return match ($sort) {
            'likes' => (int) ($row['likes'] ?? 0),
            'comments' => (int) ($row['comments'] ?? 0),
            'reposts' => (int) ($row['reposts'] ?? 0),
            'engagement' => (int) ($row['engagement'] ?? 0),
            'type' => (string) ($row['type'] ?? ''),
            'text' => (string) ($row['text'] ?? ''),
            default => (string) ($row['date'] ?? ''),
        };
    }
}
