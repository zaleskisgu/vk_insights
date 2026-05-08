<?php

namespace App\Providers;

use App\Contracts\VkClient;
use App\Integration\Vk\HttpVkClient;
use App\Integration\Vk\MockVkClient;
use App\Integration\Vk\Support\VkApiCallStats;
use App\Integration\Vk\Support\WallPostsForPeriodCache;
use App\Services\Vk\WallPostsForReportLoader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(VkApiCallStats::class);

        $this->app->bind(VkClient::class, function ($app): VkClient {
            if (config('vk.use_mock')) {
                return new MockVkClient;
            }

            return new HttpVkClient(
                (string) config('vk.access_token', ''),
                (string) config('vk.version', '5.199'),
                $app->make(VkApiCallStats::class),
            );
        });

        $this->app->singleton(WallPostsForPeriodCache::class);

        $this->app->singleton(WallPostsForReportLoader::class, function ($app) {
            return new WallPostsForReportLoader(
                $app->make(VkClient::class),
                $app->make(WallPostsForPeriodCache::class),
            );
        });
    }
}
