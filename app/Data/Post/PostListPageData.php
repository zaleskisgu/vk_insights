<?php

namespace App\Data\Post;

readonly class PostListPageData
{
    /**
     * @param  list<array<string, mixed>>  $data
     * @param  array{total: int, filtered: int, page: int, per_page: int, last_page: int}  $meta
     */
    public function __construct(
        public array $data,
        public array $meta,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{total: int, filtered: int, page: int, per_page: int, last_page: int}}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }
}
