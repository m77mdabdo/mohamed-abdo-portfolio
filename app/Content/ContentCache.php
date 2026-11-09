<?php

namespace App\Content;

use Illuminate\Support\Facades\Cache;

/** Cache keys for published content and their invalidation. Nothing from a preview is ever stored here. */
final class ContentCache
{
    public static function subjectKey(string $subject): string
    {
        return 'content:subject:'.$subject;
    }

    public static function indexKey(string $prefix): string
    {
        return 'content:index:'.$prefix;
    }

    public static function forget(string $subject): void
    {
        Cache::forget(self::subjectKey($subject));
        Cache::forget(self::indexKey(explode(':', $subject)[0]));
        Cache::forget('content:sitemap');
    }
}
