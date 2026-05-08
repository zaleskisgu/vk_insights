<?php

namespace App\Data\Report;

readonly class ReportMetaData
{
    public function __construct(
        public string $group_query,
        public string $name,
        public string $screen_name,
        public int $owner_id,
        public int $members_count,
        public string $from,
        public string $to,
        public ?string $photo_200,
        public string $generated_at,
        public bool $truncated = false,
        public ?int $posts_limit = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group_query' => $this->group_query,
            'name' => $this->name,
            'screen_name' => $this->screen_name,
            'owner_id' => $this->owner_id,
            'members_count' => $this->members_count,
            'from' => $this->from,
            'to' => $this->to,
            'photo_200' => $this->photo_200,
            'generated_at' => $this->generated_at,
            'truncated' => $this->truncated,
            'posts_limit' => $this->posts_limit,
        ];
    }
}
