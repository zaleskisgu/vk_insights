<?php

namespace App\Services\Dashboard;

use App\Contracts\VkClient;
use App\Integration\Vk\Mock\MockCommunityAvatar;
use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Integration\Vk\Support\LiveDashboardFixtureProvider;
use App\Integration\Vk\Support\VkGroupInputParser;
use App\Services\Vk\WallPostsForReportLoader;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Единая точка выбора между мок-фикстурой и живой выборкой стены.
 * Освобождает {@see \App\Services\ReportService} и {@see \App\Services\Posts\ReportPostsService}
 * от знания про режим работы (use_mock).
 */
final class DashboardFixtureFactory
{
    public function __construct(
        private VkClient $vk,
        private WallPostsForReportLoader $wallPostsLoader,
    ) {}

    public function create(string $groupInput, CarbonInterface $from, CarbonInterface $to): DashboardFixtureBundle
    {
        $parsed = VkGroupInputParser::parse($groupInput);
        $fromC = Carbon::instance($from)->startOfDay();
        $toC = Carbon::instance($to)->startOfDay();

        if (config('vk.use_mock')) {
            $groupVk = $this->vk->getGroupById($parsed->query);
            $rawFirst = $groupVk['groups'][0] ?? [];
            $first = is_array($rawFirst) ? $rawFirst : [];
            $avatarLabel = $parsed->displayHint !== '' ? $parsed->displayHint : (string) ($first['name'] ?? '');
            $avatarUrl = MockCommunityAvatar::dataUrlFromLabel($avatarLabel);
            $first['photo_50'] = $avatarUrl;
            $first['photo_100'] = $avatarUrl;
            $first['photo_200'] = $avatarUrl;
            $gid = (int) ($first['id'] ?? 0);
            $wallOwnerId = $gid > 0 ? -$gid : ($gid !== 0 ? $gid : -1);

            $fixture = new MockDashboardFixtureProvider($fromC, $toC, $wallOwnerId);

            return new DashboardFixtureBundle(
                provider: $fixture,
                group: $first,
                name: $parsed->displayHint,
                screenName: $parsed->screenSlug,
                truncated: false,
                postsLimit: null,
            );
        }

        $loaded = $this->wallPostsLoader->loadGroupAndPostsInPeriod($groupInput, $fromC, $toC);
        $first = $loaded['group'];
        $members = isset($first['members_count']) ? (int) $first['members_count'] : 0;
        $fixture = new LiveDashboardFixtureProvider($loaded['posts'], $members, $fromC, $toC);

        return new DashboardFixtureBundle(
            provider: $fixture,
            group: $first,
            name: (string) ($first['name'] ?? $parsed->displayHint),
            screenName: (string) ($first['screen_name'] ?? $parsed->screenSlug),
            truncated: $loaded['truncated'],
            postsLimit: $loaded['limit'],
        );
    }
}
