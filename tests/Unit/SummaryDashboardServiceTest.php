<?php

namespace Tests\Unit;

use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Dashboard\SummaryDashboardService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SummaryDashboardServiceTest extends TestCase
{
    public function test_get_summary_matches_fixture_shape(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();
        $provider = new MockDashboardFixtureProvider($from, $to);

        $summary = (new SummaryDashboardService)->getSummary($provider);

        $arr = $summary->toArray();
        $this->assertArrayHasKey('total_posts', $arr);
        $this->assertArrayHasKey('avg_engagement', $arr);
        $this->assertArrayHasKey('most_active_day', $arr);
        $this->assertArrayHasKey('date', $arr['most_active_day']);
        $this->assertArrayHasKey('posts', $arr['most_active_day']);
        $this->assertArrayHasKey('max_engagement', $arr);
        $this->assertArrayHasKey('value', $arr['max_engagement']);
        $this->assertArrayHasKey('date', $arr['max_engagement']);
        $this->assertGreaterThan(0, $arr['total_posts']);
    }
}
