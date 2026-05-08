<?php

namespace App\Contracts;

use App\Data\Dashboard\ContentTypeRowData;
use App\Data\Dashboard\DailyRowData;
use App\Data\Dashboard\SummaryData;
use App\Data\Dashboard\TopPostRowData;
use App\Data\Post\PostListItemData;

interface DashboardFixtureProvider
{
    public function membersCount(): int;

    public function summary(): SummaryData;

    /**
     * @return list<DailyRowData>
     */
    public function daily(): array;

    /**
     * @return list<TopPostRowData>
     */
    public function topPosts(): array;

    /**
     * @return list<ContentTypeRowData>
     */
    public function contentTypes(): array;

    /**
     * @return list<PostListItemData>
     */
    public function allPostItems(string $groupQuery): array;
}
