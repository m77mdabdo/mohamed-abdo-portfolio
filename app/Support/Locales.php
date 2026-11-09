<?php

namespace App\Support;

/**
 * The two site languages. Arabic is right-to-left; every translatable value falls back to the
 * other language when one side is still empty, so visitors never see a blank or a raw key.
 */
final class Locales
{
    public const ALL = ['ar', 'en'];

    public const PATTERN = 'ar|en';

    public static function isSupported(?string $locale): bool
    {
        return in_array($locale, self::ALL, true);
    }

    public static function default(): string
    {
        $locale = config('app.locale');

        return self::isSupported($locale) ? $locale : 'ar';
    }

    public static function other(string $locale): string
    {
        return $locale === 'ar' ? 'en' : 'ar';
    }

    public static function dir(string $locale): string
    {
        return $locale === 'ar' ? 'rtl' : 'ltr';
    }

    /** Native names, used by the language switch. */
    public static function label(string $locale): string
    {
        return $locale === 'ar' ? 'العربية' : 'English';
    }

    /** Picks the best supported language from an Accept-Language header (q-values respected). */
    public static function fromAcceptLanguage(?string $header): string
    {
        $best = null;
        $bestQ = -1.0;
        foreach (explode(',', (string) $header) as $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part)), 2, '1');
            $primary = strtolower(substr(trim($tag), 0, 2));
            $weight = (float) $q;
            if (self::isSupported($primary) && $weight > $bestQ) {
                $best = $primary;
                $bestQ = $weight;
            }
        }

        return $best ?? self::default();
    }
}
