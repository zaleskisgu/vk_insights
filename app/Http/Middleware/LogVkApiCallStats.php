<?php

namespace App\Http\Middleware;

use App\Integration\Vk\Support\VkApiCallStats;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogVkApiCallStats
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = null;

        try {
            $response = $next($request);
        } finally {
            $stats = app(VkApiCallStats::class);
            if ($stats->calls() > 0) {
                Log::channel('vk')->info(sprintf(
                    'vk_calls=%d total_ms=%.2f',
                    $stats->calls(),
                    $stats->totalMs(),
                ));

                if ($response instanceof Response && app()->environment('local')) {
                    $response->headers->set('X-Vk-Calls', (string) $stats->calls());
                    $response->headers->set('X-Vk-Total-Ms', sprintf('%.2f', $stats->totalMs()));
                }
            }
        }

        return $response;
    }
}
