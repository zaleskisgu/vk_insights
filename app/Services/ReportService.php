<?php

namespace App\Services;

use App\Contracts\DashboardFixtureProvider;
use App\Contracts\VkClient;
use App\Data\Post\PostListItemData;
use App\Data\Report\ReportMetaData;
use App\Integration\Vk\Support\LiveDashboardFixtureProvider;
use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Dashboard\ContentTypesDashboardService;
use App\Services\Dashboard\DailyDashboardService;
use App\Services\Dashboard\SummaryDashboardService;
use App\Services\Dashboard\TopPostsDashboardService;
use App\Services\Vk\WallPostsForReportLoader;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReportService
{
    public function __construct(
        private VkClient $vk,
        private SummaryDashboardService $summaryDashboard,
        private DailyDashboardService $dailyDashboard,
        private TopPostsDashboardService $topPostsDashboard,
        private ContentTypesDashboardService $contentTypesDashboard,
        private WallPostsForReportLoader $wallPostsLoader,
        private bool $dashboardFromVkWall,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getReportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        [$fixture, $first, $name, $screenName] = $this->resolveFixtureAndIdentity($groupInput, $from, $to);

        return $this->composeReportPayload($fixture, $first, $name, $screenName, $groupInput, $from, $to);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        [$fixture, $first, $name, $screenName] = $this->resolveFixtureAndIdentity($groupInput, $from, $to);
        $data = $this->composeReportPayload($fixture, $first, $name, $screenName, $groupInput, $from, $to);
        $data['all_posts'] = array_map(
            static fn (PostListItemData $p): array => $p->toArray(),
            $fixture->allPostItems(trim($groupInput)),
        );

        return $data;
    }

    /**
     * @return array{0: DashboardFixtureProvider, 1: array<string, mixed>, 2: string, 3: string}
     */
    private function resolveFixtureAndIdentity(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $parsed = $this->parseGroupInput($groupInput);
        $fromC = Carbon::instance($from)->startOfDay();
        $toC = Carbon::instance($to)->startOfDay();

        if (! $this->dashboardFromVkWall) {
            $fixture = new MockDashboardFixtureProvider($fromC, $toC);
            $groupVk = $this->vk->getGroupById(1);
            $first = $groupVk['groups'][0] ?? [];

            return [$fixture, is_array($first) ? $first : [], $parsed['name'], $parsed['screen_name']];
        }

        $loaded = $this->wallPostsLoader->loadGroupAndPostsInPeriod($groupInput, $fromC, $toC);
        $first = $loaded['group'];
        $members = isset($first['members_count']) ? (int) $first['members_count'] : 0;
        $fixture = new LiveDashboardFixtureProvider($loaded['posts'], $members, $fromC, $toC);

        $name = (string) ($first['name'] ?? $parsed['name']);
        $screenName = (string) ($first['screen_name'] ?? $parsed['screen_name']);

        return [$fixture, $first, $name, $screenName];
    }

    /**
     * @param  array<string, mixed>  $first
     * @return array<string, mixed>
     */
    private function composeReportPayload(
        DashboardFixtureProvider $fixture,
        array $first,
        string $name,
        string $screenName,
        string $groupInput,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $groupNumericId = (int) ($first['id'] ?? 0);
        if ($groupNumericId === 0 && ! $this->dashboardFromVkWall) {
            $groupNumericId = 1;
        }

        $membersForMeta = $this->dashboardFromVkWall
            ? (isset($first['members_count']) ? (int) $first['members_count'] : $fixture->membersCount())
            : $fixture->membersCount();

        $meta = new ReportMetaData(
            group_query: trim($groupInput),
            name: $name,
            screen_name: $screenName,
            owner_id: $groupNumericId > 0 ? -$groupNumericId : $groupNumericId,
            members_count: $membersForMeta,
            from: Carbon::instance($from)->toDateString(),
            to: Carbon::instance($to)->toDateString(),
            photo_200: isset($first['photo_200']) ? (string) $first['photo_200'] : null,
            generated_at: now()->format('d.m.Y, H:i:s'),
        );

        return [
            'meta' => $meta->toArray(),
            'summary' => $this->summaryDashboard->getSummary($fixture)->toArray(),
            'daily' => $this->dailyDashboard->getDailyRows($fixture),
            'top_posts' => $this->topPostsDashboard->getTopPosts($fixture),
            'content_types' => $this->contentTypesDashboard->getContentTypes($fixture),
        ];
    }

    /**
     * @return array{name: string, screen_name: string}
     */
    private function parseGroupInput(string $raw): array
    {
        $s = trim($raw);
        if ($s === '') {
            return ['name' => 'Demo', 'screen_name' => 'demo'];
        }

        if (preg_match('#vk\.com/(?:club|public|event)?([a-zA-Z0-9_]+)#iu', $s, $m)) {
            $slug = strtolower($m[1]);
        } else {
            $slug = strtolower(preg_replace('#^@#u', '', $s));
            $slug = preg_replace('#[^a-z0-9_]#iu', '', $slug) ?: 'demo';
        }

        if (preg_match('/^[a-z]+$/', $slug) && strlen($slug) <= 4) {
            $displayName = strtoupper($slug);
        } else {
            $parts = preg_split('#_+#', $slug) ?: [];
            $displayName = implode(' ', array_map(
                static fn (string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
                $parts,
            ));
            if ($displayName === '') {
                $displayName = ucfirst($slug);
            }
        }

        return ['name' => $displayName, 'screen_name' => $slug];
    }
}
