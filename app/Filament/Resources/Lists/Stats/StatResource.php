<?php

namespace App\Filament\Resources\Lists\Stats;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\CollectionTable;
use App\Models\Stat;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Figures": used by sections on several pages; published as one list. */
class StatResource extends Resource
{
    protected static ?string $model = Stat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 17;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Figure');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Figures');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('value')->label(__('Number (e.g. 27)'))->required()->maxLength(20),
            ProjectResource::bilingual('label', 'What it counts', max: 120, required: true),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('value')->label(__('Number')), TextColumn::make('label')->label(__('Label'))->formatStateUsing(fn ($record) => $record->tr('label'))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStats::route('/')];
    }
}
