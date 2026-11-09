<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditSeo extends EditSettings
{
    protected static string $group = 'seo';

    protected static bool $ownerOnly = true;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $slug = 'settings/seo';
}
