<?php

namespace Tests\Unit;

use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Dashboard\TopPostsDashboardService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TopPostsDashboardServiceTest extends TestCase
{
    public function test_get_top_posts_has_ten_entries_with_ranks(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-20')->startOfDay();
        $provider = new MockDashboardFixtureProvider($from, $to);

        $top = (new TopPostsDashboardService)->getTopPosts($provider);

        $this->assertCount(10, $top);
        $this->assertSame(1, $top[0]['rank']);
        $this->assertSame(10, $top[9]['rank']);
        $this->assertArrayHasKey('engagement', $top[0]);
        $this->assertArrayHasKey('post_id', $top[0]);
        $this->assertNotEmpty($top[0]['text']);
    }
}
