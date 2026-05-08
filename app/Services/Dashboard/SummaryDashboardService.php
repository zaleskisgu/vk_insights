<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\SummaryData;

final class SummaryDashboardService
{
    public function getSummary(DashboardFixtureProvider $provider): SummaryData
    {
        return $provider->summary();
    }
}
