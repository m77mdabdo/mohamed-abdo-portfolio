<?php

namespace App\Content\Settings;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Small interface texts (form messages, accessibility labels, error pages…).
 * Shipped defaults live in lang/{en,ar}/site.php; the owner overrides any of them from
 * Settings › Interface texts. Resolution: override → default in that language → English default → readable key.
 * A raw key like "form.sending" never reaches the page.
 */
final class UiStrings
{
    /** @var array<string, array<string, string>> */
    private static array $defaults = [];

    /** @return array<string, string> key => default text */
    public static function defaults(string $locale): array
    {
        return self::$defaults[$locale] ??= Arr::dot((array) trans('site', [], $locale));
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::defaults('en'));
    }

    public static function resolve(string $key, string $locale, array $overrides = []): string
    {
        $override = $overrides[$key][$locale] ?? '';
        if (is_string($override) && trim($override) !== '') {
            return $override;
        }

        return self::defaults($locale)[$key]
            ?? self::defaults('en')[$key]
            ?? Str::of($key)->afterLast('.')->replace('_', ' ')->ucfirst()->toString();
    }

    /** Keeps only known keys with string values (max 300 characters). */
    public static function cleanOverrides(array $data): array
    {
        $known = array_flip(self::keys());
        $out = [];
        foreach ($data as $key => $value) {
            if (! isset($known[$key]) || ! is_array($value)) {
                continue;
            }
            $en = mb_substr(trim((string) ($value['en'] ?? '')), 0, 300);
            $ar = mb_substr(trim((string) ($value['ar'] ?? '')), 0, 300);
            if ($en !== '' || $ar !== '') {
                $out[$key] = ['en' => $en, 'ar' => $ar];
            }
        }
        ksort($out);

        return $out;
    }
}
