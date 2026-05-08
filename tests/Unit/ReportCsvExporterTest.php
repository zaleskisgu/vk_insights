<?php

namespace Tests\Unit;

use App\Data\Export\FullReportExportData;
use App\Services\Export\ReportCsvExporter;
use PHPUnit\Framework\TestCase;

class ReportCsvExporterTest extends TestCase
{
    public function test_build_includes_bom_and_sections(): void
    {
        $data = [
            'meta' => [
                'group_query' => 'g',
                'name' => 'N',
                'screen_name' => 's',
                'owner_id' => -1,
                'members_count' => 100,
                'from' => '2026-01-01',
                'to' => '2026-01-02',
                'photo_200' => '',
                'generated_at' => '01.01.2026, 12:00:00',
            ],
            'summary' => [
                'total_posts' => 1,
                'avg_engagement' => 10,
                'most_active_day' => ['date' => '2026-01-01', 'posts' => 1],
                'max_engagement' => ['value' => 99, 'date' => '2026-01-01'],
            ],
            'daily' => [
                ['date' => '2026-01-01', 'avg_engagement' => 10, 'posts_count' => 1],
            ],
            'top_posts' => [
                ['rank' => 1, 'engagement' => 99, 'date' => '2026-01-01', 'likes' => 1, 'comments' => 2, 'post_id' => 3, 'text' => 't'],
            ],
            'content_types' => [
                ['type' => 'photo', 'label' => 'Фото', 'count' => 1],
            ],
            'all_posts' => [
                [
                    'post_id' => 3,
                    'date' => '2026-01-01',
                    'type' => 'photo',
                    'label' => 'Фото',
                    'likes' => 1,
                    'comments' => 2,
                    'reposts' => 0,
                    'engagement' => 3,
                    'text' => 'hello',
                ],
            ],
        ];

        $csv = (new ReportCsvExporter)->build($data);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('# meta', $csv);
        $this->assertStringContainsString('# summary', $csv);
        $this->assertStringContainsString('# daily', $csv);
        $this->assertStringContainsString('# top_posts', $csv);
        $this->assertStringContainsString('# content_types', $csv);
        $this->assertStringContainsString('# all_posts', $csv);
        $this->assertStringContainsString('group_query', $csv);
        $this->assertStringContainsString('hello', $csv);
    }

    public function test_build_from_full_export_data_delegates_to_build(): void
    {
        $export = new FullReportExportData(
            meta: ['group_query' => 'a', 'name' => 'b', 'screen_name' => 'c', 'owner_id' => 1, 'members_count' => 0, 'from' => 'x', 'to' => 'y', 'photo_200' => '', 'generated_at' => 'z'],
            summary: ['total_posts' => 0, 'avg_engagement' => 0, 'most_active_day' => ['date' => '', 'posts' => 0], 'max_engagement' => ['value' => 0, 'date' => '']],
            daily: [],
            top_posts: [],
            content_types: [],
            all_posts: [],
        );

        $csv = (new ReportCsvExporter)->buildFrom($export);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('# meta', $csv);
    }
}
