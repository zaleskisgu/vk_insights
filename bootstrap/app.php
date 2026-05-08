<?php

use App\Http\Middleware\LogVkApiCallStats;
use App\Integration\Vk\Exception\VkApiException;
use App\Integration\Vk\Exception\VkIntegrationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(LogVkApiCallStats::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (VkIntegrationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $payload = ['message' => $e->getMessage()];
            if ($e instanceof VkApiException) {
                $payload['vk_error_code'] = $e->getVkErrorCode();
                $payload['method'] = $e->getMethod();
            }

            return response()->json($payload, $e->getHttpStatus());
        });
    })->create();
