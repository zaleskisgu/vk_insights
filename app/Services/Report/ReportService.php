<?php

namespace App\Services\Report;

use App\Contracts\VkClient;
use App\Integration\Vk\Mock\MockDashboardData;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReportService
{
    public function __construct(
        private VkClient $vk,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getReportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $parsed = $this->parseGroupInput($groupInput);
        $fixture = MockDashboardData::build(
            Carbon::instance($from)->startOfDay(),
            Carbon::instance($to)->startOfDay(),
        );

        $groupVk = $this->vk->getGroupById(1);
        $first = $groupVk['groups'][0] ?? [];

        $groupNumericId = (int) ($first['id'] ?? 1);

        return [
            'meta' => [
                'group_query' => trim($groupInput),
                'name' => $parsed['name'],
                'screen_name' => $parsed['screen_name'],
                'owner_id' => $groupNumericId > 0 ? -$groupNumericId : $groupNumericId,
                'members_count' => $fixture['members_count'],
                'from' => Carbon::instance($from)->toDateString(),
                'to' => Carbon::instance($to)->toDateString(),
                'photo_200' => $first['photo_200'] ?? null,
                'generated_at' => now()->format('d.m.Y, H:i:s'),
            ],
            'summary' => $fixture['summary'],
            'daily' => $fixture['daily'],
            'top_posts' => $fixture['top_posts'],
            'content_types' => $fixture['content_types'],
        ];
    }

    /**
     * Полный дамп для экспорта (дашборд + все посты периода, мок).
     *
     * @return array<string, mixed>
     */
    public function getExportData(string $groupInput, CarbonInterface $from, CarbonInterface $to): array
    {
        $data = $this->getReportData($groupInput, $from, $to);
        $data['all_posts'] = MockDashboardData::allPosts(
            Carbon::instance($from)->startOfDay(),
            Carbon::instance($to)->startOfDay(),
            trim($groupInput),
        );

        return $data;
    }

    /**
     * @return array{name: string, screen_name: string}
     */
    private function parseGroupInput(string $raw): array
    {
        $s = trim($raw);
        if ($s === '') {
            return ['name' => 'Demo', 'screen_name' => 'demo'];
        }

        if (preg_match('#vk\.com/(?:club|public|event)?([a-zA-Z0-9_]+)#iu', $s, $m)) {
            $slug = strtolower($m[1]);
        } else {
            $slug = strtolower(preg_replace('#^@#u', '', $s));
            $slug = preg_replace('#[^a-z0-9_]#iu', '', $slug) ?: 'demo';
        }

        if (preg_match('/^[a-z]+$/', $slug) && strlen($slug) <= 4) {
            $displayName = strtoupper($slug);
        } else {
            $parts = preg_split('#_+#', $slug) ?: [];
            $displayName = implode(' ', array_map(
                static fn (string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
                $parts,
            ));
            if ($displayName === '') {
                $displayName = ucfirst($slug);
            }
        }

        return ['name' => $displayName, 'screen_name' => $slug];
    }
}

