<?php

namespace App\Providers;

use App\Contracts\VkClient;
use App\Integration\Vk\MockVkClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VkClient::class, MockVkClient::class);
    }
}
