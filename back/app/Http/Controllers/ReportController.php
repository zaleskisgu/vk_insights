<?php

namespace App\Http\Controllers;

use App\Services\Report\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __invoke(ReportService $reportService): JsonResponse
    {
        return response()->json($reportService->getReportData());
    }
}
