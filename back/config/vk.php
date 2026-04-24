<?php

return [
    'use_mock' => (bool) env('VK_USE_MOCK', true),
    'access_token' => env('VK_SERVICE_TOKEN'),
    'version' => env('VK_API_VERSION', '5.199'),
];
