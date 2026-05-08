<?php

namespace Tests\Unit;

use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Dashboard\DailyDashboardService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DailyDashboardServiceTest extends TestCase
{
    public function test_get_daily_rows_covers_period(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();
        $provider = new MockDashboardFixtureProvider($from, $to);

        $rows = (new DailyDashboardService)->getDailyRows($provider);

        $this->assertCount(3, $rows);
        $this->assertSame('2026-01-16', $rows[0]['date']);
        $this->assertSame('2026-01-18', $rows[2]['date']);
        $this->assertArrayHasKey('avg_engagement', $rows[0]);
        $this->assertArrayHasKey('posts_count', $rows[0]);
    }
}
