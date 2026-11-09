<?php

namespace App\Support;

/**
 * URL allow-list for anything an editor can type: http(s), mailto, tel, site-relative paths and
 * in-page anchors. javascript:, data:, protocol-relative (//host) and other schemes are refused.
 */
final class SafeUrl
{
    public static function isAllowed(?string $url): bool
    {
        if ($url === null || $url === '') {
            return true;
        }
        $url = trim($url);
        if (preg_match('/[\x00-\x1F\x7F\s]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '#')) {
            return (bool) preg_match('/^#[A-Za-z0-9\-_]+$/', $url);
        }
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_contains($url, '\\');
        }
        if (preg_match('/^mailto:[^@\s]+@[^@\s]+$/i', $url)) {
            return true;
        }
        if (preg_match('/^tel:\+?[0-9 ()\-]{4,20}$/i', $url)) {
            return true;
        }

        return (bool) preg_match('#^https?://[^\s/$.?\#].[^\s]*$#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function isExternal(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url) && ! str_starts_with($url, (string) config('app.url'));
    }
}
