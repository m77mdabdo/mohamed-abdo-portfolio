<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Safe icon registry: brand marks from simple-icons (CC0) shipped with the app in
 * resources/icons/simple-icons.json. Editors pick an icon by name; no SVG is ever uploaded or typed.
 */
final class Icons
{
    /** @var array<string, array{hex: string, path: string}>|null */
    private static ?array $icons = null;

    /** @return array<string, array{hex: string, path: string}> */
    public static function all(): array
    {
        return self::$icons ??= json_decode((string) file_get_contents(resource_path('icons/simple-icons.json')), true) ?: [];
    }

    /** @return array<string, string> name => name, for dashboard selects */
    public static function options(): array
    {
        $names = array_keys(self::all());

        return array_combine($names, $names);
    }

    public static function color(?string $name, string $fallback = '#C15B46'): string
    {
        return self::all()[$name]['hex'] ?? $fallback;
    }

    public static function svg(?string $name, int $size = 22): HtmlString
    {
        $icon = self::all()[$name] ?? null;
        $body = $icon
            ? '<path fill="currentColor" d="'.e($icon['path']).'"/>'
            : '<circle cx="12" cy="12" r="5" fill="currentColor"/>';

        return new HtmlString('<svg viewBox="0 0 24 24" width="'.$size.'" height="'.$size.'" aria-hidden="true" focusable="false">'.$body.'</svg>');
    }

    /** Best icon for a technology name ("Laravel 13" → "Laravel", "Tailwind 4" → "Tailwind CSS"). */
    public static function guess(string $name): ?string
    {
        if (isset(self::all()[$name])) {
            return $name;
        }
        $aliases = ['Tailwind' => 'Tailwind CSS', 'PHP' => 'PHP 8', 'Alpine' => 'Alpine.js'];
        foreach (array_keys(self::all()) as $key) {
            if (str_starts_with($name, explode(' ', $key)[0])) {
                return $key;
            }
        }
        foreach ($aliases as $prefix => $key) {
            if (str_starts_with($name, $prefix)) {
                return $key;
            }
        }

        return null;
    }
}
