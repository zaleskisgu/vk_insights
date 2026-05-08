<?php

namespace App\Services\Export;

use App\Data\Export\FullReportExportData;

/**
 * Табличный экспорт отчёта для Excel (UTF-8 BOM, блоки с заголовком-строкой).
 */
final class ReportCsvExporter
{
    public function buildFrom(FullReportExportData $export): string
    {
        return $this->build($export->toArray());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function build(array $data): string
    {
        $fh = fopen('php://temp', 'r+b');
        if ($fh === false) {
            return '';
        }

        $this->streamTo($fh, $data);

        rewind($fh);
        $out = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $out;
    }

    /**
     * Пишет CSV напрямую в открытый поток (для {@see \Symfony\Component\HttpFoundation\StreamedResponse}).
     *
     * @param  resource  $fh
     * @param  array<string, mixed>  $data
     */
    public function streamTo($fh, array $data): void
    {
        fwrite($fh, "\xEF\xBB\xBF");
        $meta = $data['meta'] ?? [];
        $summary = $data['summary'] ?? [];
        $most = $summary['most_active_day'] ?? [];
        $maxE = $summary['max_engagement'] ?? [];

        $this->section($fh, 'meta');
        $this->putCsv($fh, [
            'group_query',
            'name',
            'screen_name',
            'owner_id',
            'members_count',
            'from',
            'to',
            'photo_200',
            'generated_at',
        ]);
        $this->putCsv($fh, [
            (string) ($meta['group_query'] ?? ''),
            (string) ($meta['name'] ?? ''),
            (string) ($meta['screen_name'] ?? ''),
            (string) ($meta['owner_id'] ?? ''),
            (string) ($meta['members_count'] ?? ''),
            (string) ($meta['from'] ?? ''),
            (string) ($meta['to'] ?? ''),
            (string) ($meta['photo_200'] ?? ''),
            (string) ($meta['generated_at'] ?? ''),
        ]);

        $this->section($fh, 'summary');
        $this->putCsv($fh, [
            'total_posts',
            'avg_engagement',
            'most_active_day_date',
            'most_active_day_posts',
            'max_engagement_value',
            'max_engagement_date',
        ]);
        $this->putCsv($fh, [
            (string) ($summary['total_posts'] ?? ''),
            (string) ($summary['avg_engagement'] ?? ''),
            (string) ($most['date'] ?? ''),
            (string) ($most['posts'] ?? ''),
            (string) ($maxE['value'] ?? ''),
            (string) ($maxE['date'] ?? ''),
        ]);

        $this->section($fh, 'daily');
        $this->putCsv($fh, ['date', 'avg_engagement', 'posts_count']);
        foreach ($data['daily'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->putCsv($fh, [
                (string) ($row['date'] ?? ''),
                (string) ($row['avg_engagement'] ?? ''),
                (string) ($row['posts_count'] ?? ''),
            ]);
        }

        $this->section($fh, 'top_posts');
        $this->putCsv($fh, ['rank', 'engagement', 'date', 'likes', 'comments', 'post_id', 'text']);
        foreach ($data['top_posts'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->putCsv($fh, [
                (string) ($row['rank'] ?? ''),
                (string) ($row['engagement'] ?? ''),
                (string) ($row['date'] ?? ''),
                (string) ($row['likes'] ?? ''),
                (string) ($row['comments'] ?? ''),
                (string) ($row['post_id'] ?? ''),
                (string) ($row['text'] ?? ''),
            ]);
        }

        $this->section($fh, 'content_types');
        $this->putCsv($fh, ['type', 'label', 'count']);
        foreach ($data['content_types'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->putCsv($fh, [
                (string) ($row['type'] ?? ''),
                (string) ($row['label'] ?? ''),
                (string) ($row['count'] ?? ''),
            ]);
        }

        $this->section($fh, 'all_posts');
        $this->putCsv($fh, ['post_id', 'date', 'type', 'label', 'likes', 'comments', 'reposts', 'engagement', 'text']);
        foreach ($data['all_posts'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $this->putCsv($fh, [
                (string) ($row['post_id'] ?? ''),
                (string) ($row['date'] ?? ''),
                (string) ($row['type'] ?? ''),
                (string) ($row['label'] ?? ''),
                (string) ($row['likes'] ?? ''),
                (string) ($row['comments'] ?? ''),
                (string) ($row['reposts'] ?? ''),
                (string) ($row['engagement'] ?? ''),
                (string) ($row['text'] ?? ''),
            ]);
        }
    }

    /** @param resource $fh */
    private function section($fh, string $name): void
    {
        fwrite($fh, "\n");
        $this->putCsv($fh, ['# '.$name]);
    }

    /**
     * @param  resource  $fh
     * @param  list<string|int|float>  $fields
     */
    private function putCsv($fh, array $fields): void
    {
        fputcsv($fh, $fields, ',', '"', '\\');
    }
}
