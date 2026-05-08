<?php

namespace App\Providers;

use App\Contracts\VkClient;
use App\Integration\Vk\HttpVkClient;
use App\Integration\Vk\MockVkClient;
use App\Services\Dashboard\ContentTypesDashboardService;
use App\Services\Dashboard\DailyDashboardService;
use App\Services\Dashboard\SummaryDashboardService;
use App\Services\Dashboard\TopPostsDashboardService;
use App\Services\Posts\ReportPostsService;
use App\Services\ReportService;
use App\Services\Vk\WallPostsForReportLoader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VkClient::class, function (): VkClient {
            if (config('vk.use_mock', true)) {
                return new MockVkClient;
            }

            return new HttpVkClient(
                (string) config('vk.access_token', ''),
                (string) config('vk.version', '5.199'),
            );
        });

        $this->app->singleton(WallPostsForReportLoader::class, function ($app) {
            return new WallPostsForReportLoader($app->make(VkClient::class));
        });

        $this->app->bind(ReportService::class, function ($app) {
            return new ReportService(
                $app->make(VkClient::class),
                $app->make(SummaryDashboardService::class),
                $app->make(DailyDashboardService::class),
                $app->make(TopPostsDashboardService::class),
                $app->make(ContentTypesDashboardService::class),
                $app->make(WallPostsForReportLoader::class),
                ! config('vk.use_mock', true),
            );
        });

        $this->app->bind(ReportPostsService::class, function ($app) {
            return new ReportPostsService(
                $app->make(WallPostsForReportLoader::class),
                ! config('vk.use_mock', true),
            );
        });
    }
}
