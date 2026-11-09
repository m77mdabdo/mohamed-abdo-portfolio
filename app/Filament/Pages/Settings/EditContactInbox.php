<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditContactInbox extends EditSettings
{
    protected static string $group = 'contact';

    protected static bool $ownerOnly = true;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $slug = 'settings/contact';
}
