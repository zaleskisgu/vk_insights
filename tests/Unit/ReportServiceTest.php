<?php

namespace Tests\Unit;

use App\Contracts\VkClient;
use App\Services\Report\ReportService;
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

        $result = (new ReportService($vk))->getReportData('igm', $from, $to);

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
}
