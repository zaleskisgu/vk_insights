<?php

namespace Tests\Unit;

use App\Integration\Vk\Mock\MockDashboardFixtureProvider;
use App\Services\Dashboard\ContentTypesDashboardService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ContentTypesDashboardServiceTest extends TestCase
{
    public function test_get_content_types_three_known_labels(): void
    {
        $from = Carbon::parse('2026-01-16')->startOfDay();
        $to = Carbon::parse('2026-01-16')->startOfDay();
        $provider = new MockDashboardFixtureProvider($from, $to);

        $types = (new ContentTypesDashboardService)->getContentTypes($provider);

        $this->assertCount(3, $types);
        $labels = array_column($types, 'label');
        $this->assertContains('Фото', $labels);
        $this->assertContains('Мульти', $labels);
        $this->assertContains('Видео', $labels);
        foreach ($types as $row) {
            $this->assertArrayHasKey('type', $row);
            $this->assertArrayHasKey('count', $row);
            $this->assertGreaterThanOrEqual(0, $row['count']);
        }
    }
}
