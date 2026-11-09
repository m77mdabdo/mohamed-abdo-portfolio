<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditAppearance extends EditSettings
{
    protected static string $group = 'appearance';

    protected static bool $ownerOnly = true;

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $slug = 'settings/appearance';
}
