<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ReportPostsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('report', ReportController::class);
Route::post('report/export', [ReportExportController::class, 'store']);
Route::post('report/posts', [ReportPostsController::class, 'index']);
