<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExportRequest;
use App\Services\Export\ReportCsvExporter;
use App\Services\ReportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function store(ReportExportRequest $request, ReportService $reportService, ReportCsvExporter $csvExporter): Response
    {
        $validated = $request->validated();

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
            return new StreamedResponse(
                static function () use ($payload): void {
                    $fh = fopen('php://output', 'wb');
                    if ($fh === false) {
                        return;
                    }
                    fwrite($fh, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    fclose($fh);
                },
                200,
                [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="'.$base.'.json"',
                ],
            );
        }

        return new StreamedResponse(
            static function () use ($csvExporter, $payload): void {
                $fh = fopen('php://output', 'wb');
                if ($fh === false) {
                    return;
                }
                $csvExporter->streamTo($fh, $payload);
                fclose($fh);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$base.'.csv"',
            ],
        );
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
