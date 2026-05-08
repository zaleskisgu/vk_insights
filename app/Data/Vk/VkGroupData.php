<?php

namespace App\Data\Vk;

readonly class VkGroupData
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromVkApiArray(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['name'] ?? ''),
            screen_name: (string) ($row['screen_name'] ?? ''),
            is_closed: (int) ($row['is_closed'] ?? 0),
            type: (string) ($row['type'] ?? 'group'),
            photo_50: isset($row['photo_50']) ? (string) $row['photo_50'] : null,
            photo_100: isset($row['photo_100']) ? (string) $row['photo_100'] : null,
            photo_200: isset($row['photo_200']) ? (string) $row['photo_200'] : null,
            members_count: isset($row['members_count']) ? (int) $row['members_count'] : null,
        );
    }

    public function __construct(
        public int $id,
        public string $name,
        public string $screen_name,
        public int $is_closed,
        public string $type,
        public ?string $photo_50,
        public ?string $photo_100,
        public ?string $photo_200,
        public ?int $members_count,
    ) {}

    /**
     * Форма, которую ждёт приложение (см. {@see \App\Services\ReportService}).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [
            'id' => $this->id,
            'name' => $this->name,
            'screen_name' => $this->screen_name,
            'is_closed' => $this->is_closed,
            'type' => $this->type,
            'photo_50' => $this->photo_50,
            'photo_100' => $this->photo_100,
            'photo_200' => $this->photo_200,
        ];
        if ($this->members_count !== null) {
            $out['members_count'] = $this->members_count;
        }

        return $out;
    }
}
