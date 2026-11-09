<?php

namespace App\Filament\Resources\Lists\SpokenLanguages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\CollectionTable;
use App\Models\SpokenLanguage;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Languages": used by sections on several pages; published as one list. */
class SpokenLanguageResource extends Resource
{
    protected static ?string $model = SpokenLanguage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?int $navigationSort = 14;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Language');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Languages');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ProjectResource::bilingual('name', 'Language', max: 40, required: true),
            ProjectResource::bilingual('level', 'Level', max: 40, required: true),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('name')->label(__('Language'))->formatStateUsing(fn ($record) => $record->tr('name'))->description(fn ($record) => $record->tr('level'))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSpokenLanguages::route('/')];
    }
}
