<?php

namespace App\Filament\Resources\Lists\ProcessSteps;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\ProcessStep;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Process steps": used by sections on several pages; published as one list. */
class ProcessStepResource extends Resource
{
    protected static ?string $model = ProcessStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Process step');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Process steps');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Bilingual::field('title', 'Title', max: 60, required: true),
            Bilingual::field('description', 'Description', true, 300, true),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('title')->label(__('Title'))->formatStateUsing(fn ($record) => $record->tr('title'))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProcessSteps::route('/')];
    }
}
