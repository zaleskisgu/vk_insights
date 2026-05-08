<?php

namespace App\Data\Vk;

readonly class WallGetResultData
{
    /**
     * @param  list<array<string, mixed>>  $items  элементы как в VK API wall.get → response.items
     */
    public function __construct(
        public int $count,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $vkResponse  поле {@code response} метода wall.get
     */
    public static function fromVkApiResponse(array $vkResponse): self
    {
        $count = (int) ($vkResponse['count'] ?? 0);
        $items = $vkResponse['items'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        /** @var list<array<string, mixed>> $normalized */
        $normalized = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $normalized[] = $item;
            }
        }

        return new self($count, $normalized);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function fromItems(array $items): self
    {
        return new self(count($items), $items);
    }

    /**
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'items' => $this->items,
        ];
    }
}
