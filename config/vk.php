<?php

$useMockEnv = env('VK_USE_MOCK');
$tokenRaw = env('VK_SERVICE_TOKEN');
$hasServiceToken = $tokenRaw !== null && trim((string) $tokenRaw) !== '';
$forceMockFromEnv = $useMockEnv !== null && $useMockEnv !== '' && filter_var($useMockEnv, FILTER_VALIDATE_BOOLEAN);

$cacheTtlEnv = env('VK_CACHE_TTL');
$periodMaxDaysEnv = env('VK_PERIOD_MAX_DAYS');
$wallMaxPagesEnv = env('VK_WALL_MAX_PAGES');
$wallPageSizeEnv = env('VK_WALL_PAGE_SIZE');

return [
    // Нет токена — только мок; иначе мок только при VK_USE_MOCK=true.
    'use_mock' => ! $hasServiceToken || $forceMockFromEnv,
    'has_service_token' => $hasServiceToken,
    'access_token' => $tokenRaw,
    'version' => env('VK_API_VERSION', '5.199'),
    'integration_test_group_id' => filter_var(env('VK_INTEGRATION_TEST_GROUP_ID'), FILTER_VALIDATE_INT) ?: 22822305,
    // TTL кэша wall+group для live VK (сек). 0 — без кэша. Диапазон ТЗ: 600–1800.
    'cache_ttl' => $cacheTtlEnv === null || $cacheTtlEnv === ''
        ? 1200
        : max(0, (int) $cacheTtlEnv),
    // Максимальная ширина периода from..to в днях. Защита от бесполезно длинных запросов.
    'period_max_days' => $periodMaxDaysEnv === null || $periodMaxDaysEnv === ''
        ? 365
        : max(1, (int) $periodMaxDaysEnv),
    // Часовой пояс для отображения дат постов и времени генерации отчёта.
    'timezone' => env('VK_TIMEZONE', 'Europe/Moscow'),
    'wall' => [
        // Лимит страниц wall.get на один отчёт. max_pages * page_size = лимит постов в отчёте.
        'max_pages' => $wallMaxPagesEnv === null || $wallMaxPagesEnv === ''
            ? 50
            : max(1, (int) $wallMaxPagesEnv),
        // Размер страницы wall.get; VK ограничивает 100.
        'page_size' => $wallPageSizeEnv === null || $wallPageSizeEnv === ''
            ? 100
            : min(100, max(1, (int) $wallPageSizeEnv)),
    ],
];
