<?php

namespace App\Http\Controllers;

use App\Services\Report\ReportCsvExporter;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ReportExportController extends Controller
{
    public function store(Request $request, ReportService $reportService, ReportCsvExporter $csvExporter): Response
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:512'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'format' => ['required', Rule::in(['json', 'csv'])],
        ]);

        $payload = $reportService->getExportData(
            $validated['group'],
            $request->date('from'),
            $request->date('to'),
        );

        $meta = $payload['meta'] ?? [];
        $base = $this->exportBasename(
            (string) ($meta['screen_name'] ?? 'report'),
            (string) ($meta['from'] ?? 'from'),
            (string) ($meta['to'] ?? 'to'),
        );

        if ($validated['format'] === 'json') {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return response($json, 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$base.'.json"',
            ]);
        }

        $csv = $csvExporter->build($payload);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$base.'.csv"',
        ]);
    }

    private function exportBasename(string $screenName, string $from, string $to): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $screenName);
        $slug = trim((string) $slug, '-');
        if ($slug === '') {
            $slug = 'report';
        }

        return 'vk-report-'.$slug.'-'.$from.'-'.$to;
    }
}
