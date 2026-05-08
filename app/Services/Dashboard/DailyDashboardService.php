<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\DailyRowData;

final class DailyDashboardService
{
    /**
     * @return list<array{date: string, avg_engagement: int, posts_count: int}>
     */
    public function getDailyRows(DashboardFixtureProvider $provider): array
    {
        return array_map(
            static fn (DailyRowData $row): array => $row->toArray(),
            $provider->daily(),
        );
    }
}
