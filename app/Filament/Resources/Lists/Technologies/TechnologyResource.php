<?php

namespace App\Filament\Resources\Lists\Technologies;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\CollectionTable;
use App\Models\Technology;
use App\Support\Icons;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Technologies": used by sections on several pages; published as one list. */
class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 18;

    public static function getNavigationGroup(): ?string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Technology');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Technologies');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(60)->unique(ignoreRecord: true),
            Select::make('icon')->label(__('Icon'))->native(false)->searchable()->options(Icons::options()),
            ColorPicker::make('color')->label(__('Colour'))->regex('/^#[0-9A-Fa-f]{6}$/'),
            Toggle::make('in_cube')->label(__('Show on the About cube (first 6 in order)')),
            ProjectResource::bilingual('role', 'Caption under the cube', max: 60),
            Select::make('band_row')->label(__('Row in the moving skills band'))->native(false)->options([1 => '1', 2 => '2', 3 => '3'])->placeholder(__('Not in the band')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('name')->label(__('Name'))->searchable(), IconColumn::make('in_cube')->label(__('Cube'))->boolean(), TextColumn::make('band_row')->label(__('Band row'))->placeholder('—')], false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTechnologies::route('/')];
    }
}
