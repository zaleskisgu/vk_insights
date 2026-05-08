<?php

namespace App\Integration\Vk\Mock;

/**
 * Аватар для режима мока без запросов к VK CDN: SVG с инициалами в data URL.
 */
final class MockCommunityAvatar
{
    public static function dataUrlFromLabel(string $label): string
    {
        $raw = trim($label);
        $chars = $raw !== '' ? mb_strtoupper(mb_substr($raw, 0, 2)) : '?';

        $text = htmlspecialchars($chars, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
<rect width="200" height="200" rx="16" fill="#5181b8"/>
<text x="100" y="104" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="system-ui,-apple-system,sans-serif" font-size="64" font-weight="600">{$text}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
