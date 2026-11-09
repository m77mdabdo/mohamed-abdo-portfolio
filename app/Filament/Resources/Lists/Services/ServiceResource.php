<?php

namespace App\Filament\Resources\Lists\Services;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\Service;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Services": used by sections on several pages; published as one list. */
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Service');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('number')->label(__('Number (e.g. 01)'))->maxLength(6),
            Bilingual::field('title', 'Title', max: 80, required: true),
            Bilingual::field('description', 'Description', true, 400, true),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('number')->label('#'), TextColumn::make('title')->label(__('Title'))->formatStateUsing(fn ($record) => $record->tr('title'))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
