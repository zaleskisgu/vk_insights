<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\TopPostRowData;

final class TopPostsDashboardService
{
    /**
     * @return list<array{rank: int, engagement: int, text: string, date: string, likes: int, comments: int, post_id: int}>
     */
    public function getTopPosts(DashboardFixtureProvider $provider): array
    {
        return array_map(
            static fn (TopPostRowData $row): array => $row->toArray(),
            $provider->topPosts(),
        );
    }
}
