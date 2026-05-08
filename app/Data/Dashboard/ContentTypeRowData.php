<?php

namespace App\Data\Dashboard;

readonly class ContentTypeRowData
{
    public function __construct(
        public string $type,
        public string $label,
        public int $count,
    ) {}

    /**
     * @return array{type: string, label: string, count: int}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'count' => $this->count,
        ];
    }
}
