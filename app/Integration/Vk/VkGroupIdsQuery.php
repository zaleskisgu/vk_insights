<?php

namespace App\Integration\Vk;

/**
 * Строка для параметра {@code group_ids} в {@see groups.getById}: id или короткое имя.
 */
final class VkGroupIdsQuery
{
    public static function fromUserInput(string $raw): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '1';
        }

        if (preg_match('#vk\.com/(?:club|public|event)(\d+)#iu', $s, $m)) {
            return $m[1];
        }

        if (preg_match('#vk\.com/([a-zA-Z0-9_]+)#iu', $s, $m)) {
            return strtolower($m[1]);
        }

        $compact = preg_replace('#\s+#u', '', $s);
        if (preg_match('#^-?\d+$#', $compact)) {
            return $compact;
        }

        $slug = strtolower(preg_replace('#^@#u', '', $s));
        $slug = preg_replace('#[^a-z0-9_]#iu', '', $slug) ?: '1';

        return $slug;
    }
}
