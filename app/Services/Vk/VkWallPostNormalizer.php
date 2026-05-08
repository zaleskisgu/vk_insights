<?php

namespace App\Services\Vk;

use App\Data\Post\PostListItemData;
use Carbon\Carbon;

final class VkWallPostNormalizer
{
    /**
     * @param  array<string, mixed>  $item  элемент {@see wall.get} → items[]
     */
    public static function toPostListItem(array $item): PostListItemData
    {
        $src = self::sourceForFields($item);
        [$type, $label] = self::classifyContentType($src);

        $likes = self::nestedCount($item, 'likes');
        $comments = self::nestedCount($item, 'comments');
        $reposts = self::nestedCount($item, 'reposts');
        $engagement = $likes + $comments + $reposts;

        $text = trim((string) ($item['text'] ?? ''));
        if ($text === '') {
            $text = trim((string) ($src['text'] ?? ''));
        }

        $ts = (int) ($item['date'] ?? 0);
        $date = $ts > 0
            ? Carbon::createFromTimestamp($ts)->timezone(config('app.timezone'))->format('Y-m-d')
            : '';

        return new PostListItemData(
            post_id: (int) ($item['id'] ?? 0),
            date: $date,
            type: $type,
            label: $label,
            text: $text,
            likes: $likes,
            comments: $comments,
            reposts: $reposts,
            engagement: $engagement,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function sourceForFields(array $item): array
    {
        $hist = $item['copy_history'] ?? null;
        if (is_array($hist) && $hist !== [] && is_array($hist[0])) {
            return $hist[0];
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{0: string, 1: string} type, label
     */
    private static function classifyContentType(array $item): array
    {
        $attachments = $item['attachments'] ?? [];
        if (! is_array($attachments)) {
            return ['text', 'Текст'];
        }

        $kinds = [];
        foreach ($attachments as $a) {
            if (! is_array($a)) {
                continue;
            }
            $t = (string) ($a['type'] ?? '');
            if ($t === 'photo' || $t === 'posted_photo') {
                $kinds['photo'] = true;
            } elseif ($t === 'video' || $t === 'video_playlist') {
                $kinds['video'] = true;
            } elseif ($t === 'link') {
                $kinds['link'] = true;
            } elseif ($t !== '' && $t !== 'sticker') {
                $kinds['other'] = true;
            }
        }

        $n = count($kinds);
        if ($n >= 2) {
            return ['multi', 'Мульти'];
        }
        if (isset($kinds['video'])) {
            return ['video', 'Видео'];
        }
        if (isset($kinds['photo'])) {
            return ['photo', 'Фото'];
        }
        if (isset($kinds['link'])) {
            return ['link', 'Ссылка'];
        }

        return ['text', 'Текст'];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function nestedCount(array $item, string $key): int
    {
        $v = $item[$key] ?? null;
        if (is_array($v) && array_key_exists('count', $v)) {
            return (int) $v['count'];
        }

        return 0;
    }
}
