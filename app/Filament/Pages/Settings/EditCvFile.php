<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditCvFile extends EditSettings
{
    protected static string $group = 'cv';

    protected static bool $ownerOnly = false;

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static ?string $slug = 'settings/cv';
}
