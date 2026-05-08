<?php

namespace Tests\Unit;

use App\Contracts\VkClient;
use App\Services\Dashboard\ContentTypesDashboardService;
use App\Services\Dashboard\DailyDashboardService;
use App\Services\Dashboard\SummaryDashboardService;
use App\Services\Dashboard\TopPostsDashboardService;
use App\Services\ReportService;
use App\Services\Vk\WallPostsForReportLoader;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    public function test_get_report_data_includes_meta_and_dashboard_sections(): void
    {
        $groupVk = [
            'groups' => [
                [
                    'id' => 1,
                    'photo_200' => 'https://example.com/photo.jpg',
                ],
            ],
            'profiles' => [],
        ];

        $vk = $this->createMock(VkClient::class);
        $vk->expects($this->once())
            ->method('getGroupById')
            ->with(1)
            ->willReturn($groupVk);

        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-18')->startOfDay();

        $service = new ReportService(
            $vk,
            new SummaryDashboardService,
            new DailyDashboardService,
            new TopPostsDashboardService,
            new ContentTypesDashboardService,
            new WallPostsForReportLoader($vk),
            false,
        );

        $result = $service->getReportData('igm', $from, $to);

        $this->assertSame('IGM', $result['meta']['name']);
        $this->assertSame('igm', $result['meta']['screen_name']);
        $this->assertSame('2026-01-16', $result['meta']['from']);
        $this->assertSame('2026-01-18', $result['meta']['to']);
        $this->assertSame('https://example.com/photo.jpg', $result['meta']['photo_200']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('daily', $result);
        $this->assertCount(3, $result['daily']);
        $this->assertArrayHasKey('top_posts', $result);
        $this->assertCount(10, $result['top_posts']);
        $this->assertArrayHasKey('content_types', $result);
        $this->assertCount(3, $result['content_types']);
    }

    public function test_get_export_data_appends_all_posts(): void
    {
        $vk = $this->createMock(VkClient::class);
        $vk->method('getGroupById')->willReturn([
            'groups' => [['id' => 1, 'photo_200' => null]],
            'profiles' => [],
        ]);

        $service = new ReportService(
            $vk,
            new SummaryDashboardService,
            new DailyDashboardService,
            new TopPostsDashboardService,
            new ContentTypesDashboardService,
            new WallPostsForReportLoader($vk),
            false,
        );

        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-16')->startOfDay();

        $export = $service->getExportData('testgroup', $from, $to);

        $this->assertArrayHasKey('all_posts', $export);
        $this->assertGreaterThan(0, count($export['all_posts']));
        $this->assertArrayHasKey('post_id', $export['all_posts'][0]);
    }
}
