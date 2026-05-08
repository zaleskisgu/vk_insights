<?php

namespace App\Integration\Vk\Support;

/**
 * Единая разборка ввода сообщества: ссылки vk.com, @slug, числовой id.
 */
final class VkGroupInputParser
{
    /** Фрагмент шаблона без ограничителей. */
    private const VK_COM_BASE = '(?:https?://)?(?:m\.)?vk\.com/';

    public static function parse(string $raw): VkGroupInputParseResult
    {
        $s = trim($raw);
        if ($s === '') {
            return new VkGroupInputParseResult('1', 'Demo', 'demo');
        }

        if (preg_match('~'.self::VK_COM_BASE.'(club|public|event)(\d+)(?=[/?#]|$)~iu', $s, $m)) {
            $prefix = strtolower($m[1]);
            $id = $m[2];
            $screenSlug = $prefix.$id;

            return new VkGroupInputParseResult(
                $id,
                self::displayHintFromSlug($screenSlug),
                $screenSlug,
            );
        }

        if (preg_match('~'.self::VK_COM_BASE.'([a-zA-Z0-9_]+)~iu', $s, $m)) {
            $screenSlug = strtolower($m[1]);

            return new VkGroupInputParseResult(
                $screenSlug,
                self::displayHintFromSlug($screenSlug),
                $screenSlug,
            );
        }

        $compact = preg_replace('#\s+#u', '', $s) ?? '';
        if (preg_match('#^-?\d+$#', $compact)) {
            $id = ltrim($compact, '-');
            if ($id === '') {
                $id = '1';
            }
            $screenSlug = $id;

            return new VkGroupInputParseResult(
                $id,
                self::displayHintFromSlug($screenSlug),
                $screenSlug,
            );
        }

        $slug = strtolower(preg_replace('#^@#u', '', $s));
        $slug = preg_replace('#[^a-z0-9_]#iu', '', $slug) ?: '1';

        return new VkGroupInputParseResult(
            $slug,
            self::displayHintFromSlug($slug),
            $slug,
        );
    }

    private static function displayHintFromSlug(string $slug): string
    {
        if (preg_match('/^[a-z]+$/', $slug) && strlen($slug) <= 4) {
            return strtoupper($slug);
        }

        $parts = preg_split('#_+#', $slug) ?: [];
        $displayName = implode(' ', array_map(
            static fn (string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'),
            $parts,
        ));

        if ($displayName === '') {
            return ucfirst($slug);
        }

        return $displayName;
    }
}
