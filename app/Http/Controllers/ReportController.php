<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __invoke(ReportRequest $request, ReportService $reportService): JsonResponse
    {
        $validated = $request->validated();

        return response()->json($reportService->getReportData(
            $validated['group'],
            $request->date('from'),
            $request->date('to'),
        ));
    }
}
