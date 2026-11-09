<?php

namespace App\Filament\Resources\Lists\Experiences;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\Experience;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Experience": used by sections on several pages; published as one list. */
class ExperienceResource extends Resource
{
    protected static ?string $model = Experience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Experience');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Experience');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Bilingual::field('period', 'Period (e.g. Nov 2025 — Present)', max: 60, required: true),
            Bilingual::field('role', 'Role', max: 80, required: true),
            Bilingual::field('organization', 'Organisation and city', max: 120, required: true),
            Bilingual::field('description', 'Description', true, 600),
            Toggle::make('is_current')->label(__('Current job ("Now" sticker)')),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('role')->label(__('Role'))->formatStateUsing(fn ($record) => $record->tr('role'))->description(fn ($record) => $record->tr('organization')), TextColumn::make('period')->label(__('Period'))->formatStateUsing(fn ($record) => $record->tr('period'))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExperiences::route('/')];
    }
}
