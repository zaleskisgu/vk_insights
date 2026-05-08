<?php

namespace App\Services;

use App\Data\Post\PostListItemData;
use App\Data\Report\ReportMetaData;
use App\Services\Dashboard\ContentTypesDashboardService;
use App\Services\Dashboard\DailyDashboardService;
use App\Services\Dashboard\DashboardFixtureBundle;
use App\Services\Dashboard\DashboardFixtureFactory;
use App\Services\Dashboard\SummaryDashboardService;
use App\Services\Dashboard\TopPostsDashboardService;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReportService
{
    public function __construct(
        private DashboardFixtureFactory $fixtureFactory,
        private SummaryDashboardService $summaryDashboard,
        private DailyDashboardService $dailyDashboard,
        private TopPostsDashboardService $topPostsDashboard,
        private ContentTypesDashboardService $contentTypesDashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getReportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $bundle = $this->fixtureFactory->create($groupInput, $from, $to);

        return $this->composeReportPayload($bundle, $groupInput, $from, $to);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $bundle = $this->fixtureFactory->create($groupInput, $from, $to);
        $data = $this->composeReportPayload($bundle, $groupInput, $from, $to);
        $data['all_posts'] = array_map(
            static fn (PostListItemData $p): array => $p->toArray(),
            $bundle->provider->allPostItems(trim($groupInput)),
        );

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function composeReportPayload(
        DashboardFixtureBundle $bundle,
        string $groupInput,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $groupNumericId = (int) ($bundle->group['id'] ?? 0);
        $membersForMeta = isset($bundle->group['members_count'])
            ? (int) $bundle->group['members_count']
            : $bundle->provider->membersCount();

        $tz = (string) config('vk.timezone', config('app.timezone', 'UTC'));

        $meta = new ReportMetaData(
            group_query: trim($groupInput),
            name: $bundle->name,
            screen_name: $bundle->screenName,
            owner_id: $groupNumericId > 0 ? -$groupNumericId : $groupNumericId,
            members_count: $membersForMeta,
            from: Carbon::instance($from)->toDateString(),
            to: Carbon::instance($to)->toDateString(),
            photo_200: isset($bundle->group['photo_200']) ? (string) $bundle->group['photo_200'] : null,
            generated_at: now()->setTimezone($tz)->format('d.m.Y, H:i:s'),
            truncated: $bundle->truncated,
            posts_limit: $bundle->postsLimit,
        );

        return [
            'meta' => $meta->toArray(),
            'summary' => $this->summaryDashboard->getSummary($bundle->provider)->toArray(),
            'daily' => $this->dailyDashboard->getDailyRows($bundle->provider),
            'top_posts' => $this->topPostsDashboard->getTopPosts($bundle->provider),
            'content_types' => $this->contentTypesDashboard->getContentTypes($bundle->provider),
        ];
    }
}
