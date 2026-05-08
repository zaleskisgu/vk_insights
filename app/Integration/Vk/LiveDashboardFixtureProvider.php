<?php

namespace App\Integration\Vk;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\ContentTypeRowData;
use App\Data\Dashboard\DailyRowData;
use App\Data\Dashboard\SummaryData;
use App\Data\Dashboard\TopPostRowData;
use App\Data\Post\PostListItemData;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Дашборд из нормализованных постов стены (уже отфильтрованных по периоду).
 */
final class LiveDashboardFixtureProvider implements DashboardFixtureProvider
{
    /** @var list<PostListItemData> */
    private array $posts;

    public function __construct(
        array $posts,
        private int $membersCount,
        private Carbon $periodFrom,
        private Carbon $periodTo,
    ) {
        $this->posts = array_values($posts);
        $this->periodFrom = $periodFrom->copy()->startOfDay();
        $this->periodTo = $periodTo->copy()->startOfDay();
    }

    public function membersCount(): int
    {
        return $this->membersCount;
    }

    public function summary(): SummaryData
    {
        $n = count($this->posts);
        if ($n === 0) {
            $emptyDay = $this->periodFrom->format('Y-m-d');

            return new SummaryData(
                total_posts: 0,
                avg_engagement: 0,
                most_active_day: ['date' => $emptyDay, 'posts' => 0],
                max_engagement: ['value' => 0, 'date' => ''],
            );
        }

        $sumEng = 0;
        $byDay = [];
        $maxEng = 0;
        $maxEngDate = '';

        foreach ($this->posts as $p) {
            $sumEng += $p->engagement;
            $byDay[$p->date] = ($byDay[$p->date] ?? 0) + 1;
            if ($p->engagement > $maxEng) {
                $maxEng = $p->engagement;
                $maxEngDate = $p->date;
            }
        }

        $mostDate = '';
        $mostCount = 0;
        foreach ($byDay as $d => $c) {
            if ($c > $mostCount) {
                $mostCount = $c;
                $mostDate = $d;
            }
        }

        return new SummaryData(
            total_posts: $n,
            avg_engagement: (int) round($sumEng / $n),
            most_active_day: ['date' => $mostDate, 'posts' => $mostCount],
            max_engagement: ['value' => $maxEng, 'date' => $maxEngDate],
        );
    }

    public function daily(): array
    {
        $byDay = [];
        foreach ($this->posts as $p) {
            if ($p->date === '') {
                continue;
            }
            if (! isset($byDay[$p->date])) {
                $byDay[$p->date] = ['count' => 0, 'eng_sum' => 0];
            }
            $byDay[$p->date]['count']++;
            $byDay[$p->date]['eng_sum'] += $p->engagement;
        }

        $out = [];
        foreach (CarbonPeriod::create($this->periodFrom, $this->periodTo) as $day) {
            /** @var Carbon $day */
            $key = $day->format('Y-m-d');
            if (isset($byDay[$key])) {
                $c = $byDay[$key]['count'];
                $avg = (int) round($byDay[$key]['eng_sum'] / $c);
                $out[] = new DailyRowData(date: $key, avg_engagement: $avg, posts_count: $c);
            } else {
                $out[] = new DailyRowData(date: $key, avg_engagement: 0, posts_count: 0);
            }
        }

        return $out;
    }

    public function topPosts(): array
    {
        $sorted = $this->posts;
        usort($sorted, static function (PostListItemData $a, PostListItemData $b): int {
            if ($a->engagement !== $b->engagement) {
                return $b->engagement <=> $a->engagement;
            }

            return $b->post_id <=> $a->post_id;
        });

        $top = array_slice($sorted, 0, 10);
        $out = [];
        foreach ($top as $i => $p) {
            $out[] = new TopPostRowData(
                rank: $i + 1,
                engagement: $p->engagement,
                text: mb_strlen($p->text) > 500 ? mb_substr($p->text, 0, 500).'…' : $p->text,
                date: $p->date,
                likes: $p->likes,
                comments: $p->comments,
                post_id: $p->post_id,
            );
        }

        return $out;
    }

    public function contentTypes(): array
    {
        $counts = [];
        foreach ($this->posts as $p) {
            $counts[$p->type] = ($counts[$p->type] ?? 0) + 1;
        }

        $labels = [
            'photo' => 'Фото',
            'multi' => 'Мульти',
            'video' => 'Видео',
            'text' => 'Текст',
            'link' => 'Ссылка',
        ];

        arsort($counts, SORT_NUMERIC);
        $out = [];
        foreach ($counts as $type => $count) {
            $out[] = new ContentTypeRowData(
                type: $type,
                label: $labels[$type] ?? $type,
                count: $count,
            );
        }

        return $out;
    }

    public function allPostItems(string $groupQuery): array
    {
        return $this->posts;
    }
}
