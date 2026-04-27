<?php

namespace Tests\Unit;

use App\Contracts\VkClient;
use App\Services\Report\ReportService;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    public function test_get_report_data_asks_vk_for_group_and_wall(): void
    {
        $group = ['id' => 1, 'name' => 'Test'];
        $wall = ['count' => 0, 'items' => []];

        $vk = $this->createMock(VkClient::class);
        $vk->method('getGroupById')
            ->with(1)
            ->willReturn($group);
        $vk->method('getWall')
            ->with(-1)
            ->willReturn($wall);

        $result = (new ReportService($vk))->getReportData();

        $this->assertSame(['group' => $group, 'wall' => $wall], $result);
    }
}
