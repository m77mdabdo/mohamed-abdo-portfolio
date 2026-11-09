<?php

namespace App\Filament\Resources\Lists\SkillGroups;

use App\Filament\Support\Bilingual;
use App\Filament\Support\CollectionTable;
use App\Models\SkillGroup;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Shared list "Skill groups": used by sections on several pages; published as one list. */
class SkillGroupResource extends Resource
{
    protected static ?string $model = SkillGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): ?string
    {
        return __('Lists');
    }

    public static function getModelLabel(): string
    {
        return __('Skill group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Skill groups');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Bilingual::field('name', 'Group name', max: 60, required: true),
            TagsInput::make('items')->label(__('Skills (press Enter after each)'))->required(),
            Toggle::make('is_visible')->label(__('Visible'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CollectionTable::make($table, [TextColumn::make('name')->label(__('Group'))->formatStateUsing(fn ($record) => $record->tr('name'))->description(fn ($record) => implode(' · ', $record->items ?? []))], true);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSkillGroups::route('/')];
    }
}
