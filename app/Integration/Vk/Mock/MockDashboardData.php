<?php

namespace App\Integration\Vk\Mock;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Мок тела отчёта дашборда (не ответ VK API): summary, daily, top_posts, content_types.
 */
final class MockDashboardData
{
    /** @var list<int> */
    private const TOP_ENGAGEMENT = [99_438, 35_200, 31_800, 29_500, 27_200, 25_100, 23_300, 21_800, 20_600, 19_800];

    /** @var list<array{0: int, 1: int}> [likes, comments] — вместе с репостами дают engagement из TOP_ENGAGEMENT */
    private const TOP_LIKES_COMMENTS = [
        [39_897, 3_125],
        [14_020, 1_890],
        [12_600, 1_720],
        [11_800, 1_510],
        [10_880, 1_340],
        [10_040, 1_260],
        [9_320, 1_180],
        [8_720, 1_020],
        [8_240, 960],
        [7_920, 880],
    ];

    /** @var list<int> Стабильные id постов для UI (мок) */
    private const TOP_POST_IDS = [
        47_387_695,
        47_210_003,
        46_998_412,
        46_854_201,
        46_701_889,
        46_520_774,
        46_388_102,
        46_201_556,
        46_044_823,
        45_912_340,
    ];

    /** @var list<string> */
    private const TOP_SNIPPETS = [
        'Прямой эфир: главные новости недели, разбор ситуации и ответы на вопросы подписчиков в комментариях.',
        'Новая подборка материалов — ссылки в описании. Сохраните пост, чтобы не потерять.',
        'Итоги месяца: цифры, графики и планы на следующий квартал. Обсуждаем вместе с вами.',
        'Фотоотчёт с мероприятия: больше сотни кадров в альбоме, лучшие — в этом посте.',
        'Важное объявление для участников сообщества: изменения в правилах и расписании.',
        'Опрос: какую тему разобрать в следующем выпуске? Голосуйте и пишите свои варианты.',
        'Подкаст уже в эфире — слушайте на любимой площадке и делитесь мнением в комментариях.',
        'Розыгрыш среди активных подписчиков: условия в посте, итоги через неделю.',
        'Технический пост: обновления API, известные ограничения и дорожная карта на весну.',
        'Спасибо за поддержку — отдельная благодарность тем, кто помог с модерацией и идеями.',
    ];

    /**
     * @return array{
     *     members_count: int,
     *     summary: array<string, mixed>,
     *     daily: list<array{date: string, avg_engagement: int, posts_count: int}>,
     *     top_posts: list<array{rank: int, engagement: int, text: string, date: string, likes: int, comments: int, post_id: int, owner_id: int}>,
     *     content_types: list<array{type: string, label: string, count: int}>
     * }
     */
    public static function build(Carbon $from, Carbon $to, int $wallOwnerId = -1): array
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

        $spanDays = max(0, $fromDay->diffInDays($toDay));
        $topPosts = [];
        foreach (self::TOP_ENGAGEMENT as $i => $engagement) {
            $dayOffset = $spanDays > 0 ? (int) round($i * $spanDays / 9) : 0;
            $postDate = $fromDay->copy()->addDays(min($dayOffset, $spanDays));
            [$likes, $comments] = self::TOP_LIKES_COMMENTS[$i];
            $topPosts[] = [
                'rank' => $i + 1,
                'engagement' => $engagement,
                'text' => self::TOP_SNIPPETS[$i],
                'date' => $postDate->format('Y-m-d'),
                'likes' => $likes,
                'comments' => $comments,
                'post_id' => self::TOP_POST_IDS[$i],
                'owner_id' => $wallOwnerId,
            ];
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

    /**
     * Полный детерминированный список постов за период (число строк = summary.total_posts в {@see build()}).
     *
     * @return list<array{
     *     post_id: int,
     *     date: string,
     *     type: string,
     *     label: string,
     *     text: string,
     *     likes: int,
     *     comments: int,
     *     reposts: int,
     *     engagement: int,
     *     owner_id: int
     * }>
     */
    public static function allPosts(Carbon $from, Carbon $to, string $groupSeed = '', int $wallOwnerId = -1): array
    {
        $fixture = self::build($from, $to, $wallOwnerId);
        $fromDay = $from->copy()->startOfDay();
        $toDay = $to->copy()->startOfDay();
        $seed = $fromDay->format('Y-m-d').'|'.$toDay->format('Y-m-d').'|'.hash('sha256', $groupSeed);

        $totalPosts = (int) ($fixture['summary']['total_posts'] ?? 0);
        if ($totalPosts <= 0) {
            return [];
        }

        $countsByType = [];
        foreach ($fixture['content_types'] as $row) {
            $countsByType[$row['type']] = (int) $row['count'];
        }
        $photoBase = (int) ($countsByType['photo'] ?? 0);
        $multi = (int) ($countsByType['multi'] ?? 0);
        $video = (int) ($countsByType['video'] ?? 0);

        $text = (int) max(0, min((int) round($totalPosts * 0.02), max(0, $photoBase - 1)));
        $link = (int) max(0, min((int) round($totalPosts * 0.015), max(0, $photoBase - $text - 1)));
        $photo = max(0, $photoBase - $text - $link);

        $typeSlots = array_merge(
            array_fill(0, $photo, 'photo'),
            array_fill(0, $multi, 'multi'),
            array_fill(0, $video, 'video'),
            array_fill(0, $text, 'text'),
            array_fill(0, $link, 'link'),
        );
        $typeSlots = self::seededShuffle($typeSlots, $seed.'|types');

        $dates = [];
        foreach ($fixture['daily'] as $row) {
            $n = (int) $row['posts_count'];
            $d = $row['date'];
            for ($i = 0; $i < $n; ++$i) {
                $dates[] = $d;
            }
        }

        $nDates = count($dates);
        if ($nDates !== $totalPosts) {
            if ($nDates > $totalPosts) {
                $dates = array_slice($dates, 0, $totalPosts);
            } else {
                $last = $dates[$nDates - 1] ?? $fromDay->format('Y-m-d');
                while (count($dates) < $totalPosts) {
                    $dates[] = $last;
                }
            }
        }

        $labels = [
            'photo' => 'Фото',
            'multi' => 'Мульти',
            'video' => 'Видео',
            'text' => 'Текст',
            'link' => 'Ссылка',
        ];

        $snippetCount = count(self::TOP_SNIPPETS);
        $out = [];
        for ($g = 0; $g < $totalPosts; ++$g) {
            $type = $typeSlots[$g] ?? 'photo';
            $h = (int) sprintf('%u', crc32($seed.'|m|'.$g));
            $likes = 12 + ($h % 4_800);
            $comments = 1 + (($h >> 3) % 900);
            $reposts = ($h >> 7) % 1_100;
            $engagement = $likes + $comments + $reposts;

            $baseText = self::TOP_SNIPPETS[$g % $snippetCount];
            $textBody = $baseText.' [пост #'.($g + 1).']';
            if (($g & 3) === 0) {
                $textBody .= ' Ссылка: https://vk.com/wall-1_'.$g;
            }

            $postId = 40_000_000 + $g * 17 + ($h % 997);

            $out[] = [
                'post_id' => $postId,
                'owner_id' => $wallOwnerId,
                'date' => $dates[$g],
                'type' => $type,
                'label' => $labels[$type],
                'text' => $textBody,
                'likes' => $likes,
                'comments' => $comments,
                'reposts' => $reposts,
                'engagement' => $engagement,
            ];
        }

        return $out;
    }

    /**
     * @template T
     * @param list<T> $items
     * @return list<T>
     */
    private static function seededShuffle(array $items, string $seed): array
    {
        $arr = $items;
        $n = count($arr);
        for ($i = $n - 1; $i > 0; --$i) {
            $bin = hash('sha256', $seed.'|'.$i, true);
            $j = self::uintFromBytes(substr($bin, 0, 4)) % ($i + 1);
            if ($j !== $i) {
                $tmp = $arr[$i];
                $arr[$i] = $arr[$j];
                $arr[$j] = $tmp;
            }
        }

        return $arr;
    }

    private static function uintFromBytes(string $four): int
    {
        $u = unpack('N', $four);
        $v = (int) ($u[1] ?? 0);

        return $v >= 0 ? $v : $v + (1 << 32);
    }
}
