<?php

namespace Tests\Unit;

use App\Integration\Vk\MockVkClient;
use App\Services\Dashboard\DashboardFixtureFactory;
use App\Services\Posts\ReportPostsService;
use App\Services\Vk\WallPostsForReportLoader;
use Carbon\Carbon;
use Tests\TestCase;

class ReportPostsServiceTest extends TestCase
{
    private ReportPostsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('vk.use_mock', true);
        $vk = new MockVkClient;
        $factory = new DashboardFixtureFactory($vk, new WallPostsForReportLoader($vk));
        $this->service = new ReportPostsService($factory);
    }

    public function test_list_page_meta_totals_and_pagination(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-16')->startOfDay();

        $result = $this->service->listPage('seed-a', $from, $to, 1, 10, 'date', 'asc', null, 'all');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $meta = $result['meta'];
        $this->assertSame($meta['total'], $meta['filtered']);
        $this->assertSame(1, $meta['page']);
        $this->assertSame(10, $meta['per_page']);
        $this->assertGreaterThanOrEqual(1, $meta['last_page']);
        $this->assertCount(min(10, $meta['filtered']), $result['data']);
        $this->assertArrayHasKey('row_index', $result['data'][0]);
    }

    public function test_filter_by_type_reduces_filtered(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();

        $all = $this->service->listPage('x', $from, $to, 1, 500, 'date', 'asc', null, 'all');
        $photo = $this->service->listPage('x', $from, $to, 1, 500, 'date', 'asc', null, 'photo');

        $this->assertLessThanOrEqual($all['meta']['filtered'], $photo['meta']['filtered']);
        foreach ($photo['data'] as $row) {
            $this->assertSame('photo', $row['type']);
        }
    }

    public function test_search_q_filters_text(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();

        $result = $this->service->listPage('x', $from, $to, 1, 100, 'date', 'asc', 'прямой эфир', 'all');

        $this->assertNotEmpty($result['data']);
        foreach ($result['data'] as $row) {
            $this->assertNotFalse(mb_stripos((string) $row['text'], 'прямой эфир'));
        }
    }

    public function test_sort_engagement_desc(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();

        $result = $this->service->listPage('x', $from, $to, 1, 50, 'engagement', 'desc', null, 'all');
        $this->assertGreaterThanOrEqual(2, count($result['data']));
        $engagements = array_map(static fn (array $r): int => (int) ($r['engagement'] ?? 0), $result['data']);
        $expected = $engagements;
        rsort($expected, SORT_NUMERIC);
        $this->assertSame($expected, $engagements);
    }
}
