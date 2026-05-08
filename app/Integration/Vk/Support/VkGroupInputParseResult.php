<?php

namespace App\Integration\Vk\Support;

/** Результат разбора ввода: API-query, подсказка для имени и slug для meta.screen_name. */
final readonly class VkGroupInputParseResult
{
    public function __construct(
        public string $query,
        public string $displayHint,
        public string $screenSlug,
    ) {}
}
