<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditFooter extends EditSettings
{
    protected static string $group = 'footer';

    protected static bool $ownerOnly = false;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static string $menuGroup = 'Navigation & texts';

    protected static ?string $slug = 'settings/footer';
}
