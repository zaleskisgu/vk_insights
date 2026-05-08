<?php

namespace App\Data\Vk;

readonly class GroupsGetByIdResultData
{
    /**
     * @param  list<VkGroupData>  $groups
     * @param  list<mixed>  $profiles
     */
    public function __construct(
        public array $groups,
        public array $profiles = [],
    ) {}

    /**
     * Поле {@code response} метода groups.getById: либо {@code { groups, profiles }}, либо устаревший плоский список сообществ.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     */
    public static function fromVkApiResponse(array $response): self
    {
        if (array_is_list($response)) {
            return self::fromVkApiGroupList($response);
        }

        $rawGroups = $response['groups'] ?? null;
        if (is_array($rawGroups)) {
            $groups = [];
            foreach ($rawGroups as $row) {
                if (is_array($row)) {
                    $groups[] = VkGroupData::fromVkApiArray($row);
                }
            }
            $profiles = $response['profiles'] ?? [];
            if (! is_array($profiles)) {
                $profiles = [];
            }

            return new self($groups, $profiles);
        }

        return new self([], []);
    }

    /**
     * Плоский список объектов сообщества (тесты / совместимость).
     *
     * @param  list<array<string, mixed>>  $vkGroups
     */
    public static function fromVkApiGroupList(array $vkGroups): self
    {
        $groups = [];
        foreach ($vkGroups as $row) {
            $groups[] = VkGroupData::fromVkApiArray($row);
        }

        return new self($groups, []);
    }

    /**
     * @return array{groups: list<array<string, mixed>>, profiles: list<mixed>}
     */
    public function toArray(): array
    {
        return [
            'groups' => array_map(static fn (VkGroupData $g): array => $g->toArray(), $this->groups),
            'profiles' => $this->profiles,
        ];
    }
}
