<?php

namespace App\Services\Report;

/**
 * Табличный экспорт отчёта для Excel (UTF-8 BOM, блоки с заголовком-строкой).
 */
final class ReportCsvExporter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function build(array $data): string
    {
        $fh = fopen('php://temp', 'r+b');
        if ($fh === false) {
            return '';
        }

        fwrite($fh, "\xEF\xBB\xBF");
        $meta = $data['meta'] ?? [];
        $summary = $data['summary'] ?? [];
        $most = $summary['most_active_day'] ?? [];
        $maxE = $summary['max_engagement'] ?? [];

        $this->section($fh, 'meta');
        fputcsv($fh, [
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
        fputcsv($fh, [
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
        fputcsv($fh, [
            'total_posts',
            'avg_engagement',
            'most_active_day_date',
            'most_active_day_posts',
            'max_engagement_value',
            'max_engagement_date',
        ]);
        fputcsv($fh, [
            (string) ($summary['total_posts'] ?? ''),
            (string) ($summary['avg_engagement'] ?? ''),
            (string) ($most['date'] ?? ''),
            (string) ($most['posts'] ?? ''),
            (string) ($maxE['value'] ?? ''),
            (string) ($maxE['date'] ?? ''),
        ]);

        $this->section($fh, 'daily');
        fputcsv($fh, ['date', 'avg_engagement', 'posts_count']);
        foreach ($data['daily'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            fputcsv($fh, [
                (string) ($row['date'] ?? ''),
                (string) ($row['avg_engagement'] ?? ''),
                (string) ($row['posts_count'] ?? ''),
            ]);
        }

        $this->section($fh, 'top_posts');
        fputcsv($fh, ['rank', 'engagement', 'date', 'likes', 'comments', 'post_id', 'text']);
        foreach ($data['top_posts'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            fputcsv($fh, [
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
        fputcsv($fh, ['type', 'label', 'count']);
        foreach ($data['content_types'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            fputcsv($fh, [
                (string) ($row['type'] ?? ''),
                (string) ($row['label'] ?? ''),
                (string) ($row['count'] ?? ''),
            ]);
        }

        $this->section($fh, 'all_posts');
        fputcsv($fh, ['post_id', 'date', 'type', 'label', 'likes', 'comments', 'reposts', 'engagement', 'text']);
        foreach ($data['all_posts'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            fputcsv($fh, [
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

        rewind($fh);
        $out = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $out;
    }

    /** @param resource $fh */
    private function section($fh, string $name): void
    {
        fwrite($fh, "\n");
        fputcsv($fh, ['# '.$name]);
    }
}
