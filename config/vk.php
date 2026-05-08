<?php

$useMockEnv = env('VK_USE_MOCK');

return [
    // Из .env всегда строка: (bool) 'false' === true. Явный разбор как у FILTER_VALIDATE_BOOLEAN.
    'use_mock' => $useMockEnv === null ? true : filter_var($useMockEnv, FILTER_VALIDATE_BOOLEAN),
    'access_token' => env('VK_SERVICE_TOKEN'),
    'version' => env('VK_API_VERSION', '5.199'),
    'integration_test_group_id' => filter_var(env('VK_INTEGRATION_TEST_GROUP_ID'), FILTER_VALIDATE_INT) ?: 22822305,
];
