<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ReportPostsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'vk_mode' => config('vk.use_mock') ? 'mock' : 'live',
        'time' => now()->toIso8601String(),
    ]);
});

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('report', ReportController::class);
    Route::post('report/export', [ReportExportController::class, 'store']);
    Route::post('report/posts', [ReportPostsController::class, 'index']);
});
