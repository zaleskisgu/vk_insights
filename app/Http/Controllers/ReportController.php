<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, ReportService $reportService): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:512'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return response()->json($reportService->getReportData(
            $validated['group'],
            $request->date('from'),
            $request->date('to'),
        ));
    }
}
