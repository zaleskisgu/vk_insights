<?php

namespace App\Integration\Vk\Mock;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\ContentTypeRowData;
use App\Data\Dashboard\DailyRowData;
use App\Data\Dashboard\SummaryData;
use App\Data\Dashboard\TopPostRowData;
use App\Data\Post\PostListItemData;
use Carbon\Carbon;

final class MockDashboardFixtureProvider implements DashboardFixtureProvider
{
    /** @var array<string, mixed>|null */
    private ?array $cached = null;

    public function __construct(
        private Carbon $from,
        private Carbon $to,
    ) {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->startOfDay();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return $this->cached ??= MockDashboardData::build($this->from, $this->to);
    }

    public function membersCount(): int
    {
        return (int) ($this->payload()['members_count'] ?? 0);
    }

    public function summary(): SummaryData
    {
        /** @var array<string, mixed> $s */
        $s = $this->payload()['summary'];

        /** @var array{date: string, posts: int} $most */
        $most = $s['most_active_day'];
        /** @var array{value: int, date: string} $max */
        $max = $s['max_engagement'];

        return new SummaryData(
            total_posts: (int) ($s['total_posts'] ?? 0),
            avg_engagement: (int) ($s['avg_engagement'] ?? 0),
            most_active_day: $most,
            max_engagement: $max,
        );
    }

    public function daily(): array
    {
        $rows = $this->payload()['daily'] ?? [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new DailyRowData(
                date: (string) ($row['date'] ?? ''),
                avg_engagement: (int) ($row['avg_engagement'] ?? 0),
                posts_count: (int) ($row['posts_count'] ?? 0),
            );
        }

        return $out;
    }

    public function topPosts(): array
    {
        $rows = $this->payload()['top_posts'] ?? [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new TopPostRowData(
                rank: (int) ($row['rank'] ?? 0),
                engagement: (int) ($row['engagement'] ?? 0),
                text: (string) ($row['text'] ?? ''),
                date: (string) ($row['date'] ?? ''),
                likes: (int) ($row['likes'] ?? 0),
                comments: (int) ($row['comments'] ?? 0),
                post_id: (int) ($row['post_id'] ?? 0),
            );
        }

        return $out;
    }

    public function contentTypes(): array
    {
        $rows = $this->payload()['content_types'] ?? [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new ContentTypeRowData(
                type: (string) ($row['type'] ?? ''),
                label: (string) ($row['label'] ?? ''),
                count: (int) ($row['count'] ?? 0),
            );
        }

        return $out;
    }

    public function allPostItems(string $groupQuery): array
    {
        $raw = MockDashboardData::allPosts($this->from, $this->to, $groupQuery);
        $out = [];
        foreach ($raw as $row) {
            $out[] = PostListItemData::fromArray($row);
        }

        return $out;
    }
}
