<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardFixtureProvider;

/**
 * Готовая «выкладка» для отчёта: провайдер агрегатов + идентичность сообщества + флаг усечения.
 */
final readonly class DashboardFixtureBundle
{
    /**
     * @param  array<string, mixed>  $group  поля сообщества из VK API (id, name, screen_name, photo_200, members_count)
     */
    public function __construct(
        public DashboardFixtureProvider $provider,
        public array $group,
        public string $name,
        public string $screenName,
        public bool $truncated,
        public ?int $postsLimit,
    ) {}
}
