<?php

namespace App\Data\Dashboard;

readonly class SummaryData
{
    /**
     * @param  array{date: string, posts: int}  $most_active_day
     * @param  array{value: int, date: string}  $max_engagement
     */
    public function __construct(
        public int $total_posts,
        public int $avg_engagement,
        public array $most_active_day,
        public array $max_engagement,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_posts' => $this->total_posts,
            'avg_engagement' => $this->avg_engagement,
            'most_active_day' => $this->most_active_day,
            'max_engagement' => $this->max_engagement,
        ];
    }
}
