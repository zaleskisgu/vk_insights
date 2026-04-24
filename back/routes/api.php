<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('report', ReportController::class)
    ->middleware('api.token');
