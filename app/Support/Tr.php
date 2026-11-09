<?php

namespace App\Support;

/**
 * Reads a translatable value ({"en": "...", "ar": "..."}) for one language.
 * Fallback order: requested language → the other language → empty string.
 */
final class Tr
{
    public static function pick(mixed $value, ?string $locale = null): string
    {
        if ($value === null) {
            return '';
        }
        if (! is_array($value)) {
            return (string) $value;
        }

        $locale ??= app()->getLocale();
        $own = $value[$locale] ?? null;
        if (is_string($own) && trim($own) !== '') {
            return $own;
        }
        $other = $value[Locales::other($locale)] ?? null;

        return is_string($other) ? $other : '';
    }

    /** True when the requested language is filled in (used by the dashboard to flag missing translations). */
    public static function has(mixed $value, string $locale): bool
    {
        return is_array($value) && is_string($value[$locale] ?? null) && trim($value[$locale]) !== '';
    }

    /** Builds a translatable value from two strings. */
    public static function make(string $en, string $ar): array
    {
        return ['en' => $en, 'ar' => $ar];
    }
}
