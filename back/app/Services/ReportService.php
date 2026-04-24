<?php

namespace App\Services\Report;

use App\Contracts\VkClient;

class ReportService
{
    public function __construct(
        private VkClient $vk,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $groupId = 1;
        $ownerId = -$groupId;

        $group = $this->vk->getGroupById($groupId);
        $wall = $this->vk->getWall($ownerId);

        return [
            'group' => $group,
            'wall' => $wall,
        ];
    }
}
