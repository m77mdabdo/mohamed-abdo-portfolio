<?php

use App\Support\Tr;

if (! function_exists('tr')) {
    /** Translatable value ({"en","ar"}) in the current language, with fallback to the other one. */
    function tr(mixed $value, ?string $locale = null): string
    {
        return Tr::pick($value, $locale);
    }
}

if (! function_exists('hex_color')) {
    /** A #RRGGBB colour from content, or the fallback — never arbitrary text inside a style attribute. */
    function hex_color(mixed $value, string $fallback = '#C15B46'): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
    }
}
