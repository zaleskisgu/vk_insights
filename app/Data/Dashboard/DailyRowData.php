<?php

namespace App\Data\Dashboard;

readonly class DailyRowData
{
    public function __construct(
        public string $date,
        public int $avg_engagement,
        public int $posts_count,
    ) {}

    /**
     * @return array{date: string, avg_engagement: int, posts_count: int}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'avg_engagement' => $this->avg_engagement,
            'posts_count' => $this->posts_count,
        ];
    }
}
