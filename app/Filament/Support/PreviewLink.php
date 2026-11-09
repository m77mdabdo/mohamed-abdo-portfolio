<?php

namespace App\Filament\Support;

use Illuminate\Support\Facades\URL;

/** Signed preview address, valid for 30 minutes and only for signed-in staff. */
final class PreviewLink
{
    public static function to(string $path, string $locale): string
    {
        return URL::temporarySignedRoute('preview', now()->addMinutes(30), ['locale' => $locale, 'path' => ltrim($path, '/') ?: null]);
    }
}
