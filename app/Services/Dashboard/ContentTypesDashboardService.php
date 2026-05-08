<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardFixtureProvider;
use App\Data\Dashboard\ContentTypeRowData;

final class ContentTypesDashboardService
{
    /**
     * @return list<array{type: string, label: string, count: int}>
     */
    public function getContentTypes(DashboardFixtureProvider $provider): array
    {
        return array_map(
            static fn (ContentTypeRowData $row): array => $row->toArray(),
            $provider->contentTypes(),
        );
    }
}
