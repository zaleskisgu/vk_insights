<?php

namespace App\Data\Export;

readonly class FullReportExportData
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $summary
     * @param  list<array<string, mixed>>  $daily
     * @param  list<array<string, mixed>>  $top_posts
     * @param  list<array<string, mixed>>  $content_types
     * @param  list<array<string, mixed>>  $all_posts
     */
    public function __construct(
        public array $meta,
        public array $summary,
        public array $daily,
        public array $top_posts,
        public array $content_types,
        public array $all_posts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'meta' => $this->meta,
            'summary' => $this->summary,
            'daily' => $this->daily,
            'top_posts' => $this->top_posts,
            'content_types' => $this->content_types,
            'all_posts' => $this->all_posts,
        ];
    }
}
