<?php

namespace App\Integration\Vk\Mock;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Мок тела отчёта (summary, daily, top_posts, content_types, members_count) для дашборда.
 */
final class MockDashboardData
{
    /** @var list<int> */
    private const TOP_ENGAGEMENT = [99_438, 35_200, 31_800, 29_500, 27_200, 25_100, 23_300, 21_800, 20_600, 19_800];

    /**
     * @return array{
     *     members_count: int,
     *     summary: array<string, mixed>,
     *     daily: list<array{date: string, avg_engagement: int, posts_count: int}>,
     *     top_posts: list<array{rank: int, engagement: int}>,
     *     content_types: list<array{type: string, label: string, count: int}>
     * }
     */
    public static function build(Carbon $from, Carbon $to): array
    {
        $fromDay = $from->copy()->startOfDay();
        $toDay = $to->copy()->startOfDay();

        $daily = [];
        $idx = 0;
        foreach (CarbonPeriod::create($fromDay, $toDay) as $date) {
            /** @var Carbon $date */
            $span = max(1, $fromDay->diffInDays($toDay));
            $t = ($idx / $span) * 2 * M_PI;
            $base = 3_200 + (int) (1_400 * sin($t * 1.7));
            $posts = 18 + ($idx % 10);
            $avg = $base + ($idx % 6) * 95;
            if ($idx > 0 && $idx % 27 === 0) {
                $avg = min(9_800, $avg + 4_200);
            }
            $daily[] = [
                'date' => $date->format('Y-m-d'),
                'avg_engagement' => $avg,
                'posts_count' => $posts,
            ];
            ++$idx;
        }

        $totalPosts = array_sum(array_column($daily, 'posts_count'));
        $weighted = 0;
        foreach ($daily as $row) {
            $weighted += $row['avg_engagement'] * $row['posts_count'];
        }
        $avgEngagement = $totalPosts > 0 ? (int) round($weighted / $totalPosts) : 0;

        $mostActive = ['date' => $fromDay->format('Y-m-d'), 'posts' => 0];
        foreach ($daily as $row) {
            if ($row['posts_count'] > $mostActive['posts']) {
                $mostActive = ['date' => $row['date'], 'posts' => $row['posts_count']];
            }
        }

        $topPosts = [];
        foreach (self::TOP_ENGAGEMENT as $i => $engagement) {
            $topPosts[] = ['rank' => $i + 1, 'engagement' => $engagement];
        }

        $maxEngagementValue = self::TOP_ENGAGEMENT[0];
        $maxEngDay = $fromDay->format('Y-m-d');
        $bestScore = -1;
        foreach ($daily as $row) {
            $score = $row['avg_engagement'] * $row['posts_count'];
            if ($score > $bestScore) {
                $bestScore = $score;
                $maxEngDay = $row['date'];
            }
        }

        $photo = (int) max(0, round($totalPosts * 0.87));
        $multi = (int) max(0, round($totalPosts * 0.09));
        $video = max(0, $totalPosts - $photo - $multi);

        return [
            'members_count' => 2_881_936,
            'summary' => [
                'total_posts' => $totalPosts,
                'avg_engagement' => $avgEngagement,
                'most_active_day' => $mostActive,
                'max_engagement' => [
                    'value' => $maxEngagementValue,
                    'date' => $maxEngDay,
                ],
            ],
            'daily' => $daily,
            'top_posts' => $topPosts,
            'content_types' => [
                ['type' => 'photo', 'label' => 'Фото', 'count' => $photo],
                ['type' => 'multi', 'label' => 'Мульти', 'count' => $multi],
                ['type' => 'video', 'label' => 'Видео', 'count' => $video],
            ],
        ];
    }
}
