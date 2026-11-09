<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class EditProjectLabels extends EditSettings
{
    protected static string $group = 'project_labels';

    protected static bool $ownerOnly = false;

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string $menuGroup = 'Navigation & texts';

    protected static ?string $slug = 'settings/project_labels';
}
