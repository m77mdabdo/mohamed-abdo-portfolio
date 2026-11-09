<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditIdentity extends EditSettings
{
    protected static string $group = 'identity';

    protected static bool $ownerOnly = false;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $slug = 'settings/identity';
}
