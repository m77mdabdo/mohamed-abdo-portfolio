<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditHeader extends EditSettings
{
    protected static string $group = 'header';

    protected static bool $ownerOnly = false;

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string $menuGroup = 'Navigation & texts';

    protected static ?string $slug = 'settings/header';
}
