<?php

namespace App\Data\Dashboard;

readonly class TopPostRowData
{
    public function __construct(
        public int $rank,
        public int $engagement,
        public string $text,
        public string $date,
        public int $likes,
        public int $comments,
        public int $post_id,
    ) {}

    /**
     * @return array{rank: int, engagement: int, text: string, date: string, likes: int, comments: int, post_id: int}
     */
    public function toArray(): array
    {
        return [
            'rank' => $this->rank,
            'engagement' => $this->engagement,
            'text' => $this->text,
            'date' => $this->date,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'post_id' => $this->post_id,
        ];
    }
}
