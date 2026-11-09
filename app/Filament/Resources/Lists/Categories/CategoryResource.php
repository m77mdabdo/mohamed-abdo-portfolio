<?php

namespace App\Filament\Resources\Lists\Categories;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\Category;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Categories": used by sections on several pages; published as one list. */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 19;

    public static function getNavigationGroup(): ?string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('slug')->label(__('Address key (filter)'))->required()->maxLength(60)->regex('/^[a-z0-9-]+$/')->unique(ignoreRecord: true),
            Bilingual::field('name', 'Name', max: 60, required: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('name')->label(__('Name'))->formatStateUsing(fn ($record) => $record->tr('name'))->description(fn ($record) => $record->slug), TextColumn::make('projects_count')->counts('projects')->label(__('Projects'))], false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
