<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Common table behaviour for shared lists: drag to reorder, quick visibility switch, edit in a side panel. */
final class CollectionTable
{
    public static function make(Table $table, array $columns, bool $hasVisibility = true): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated([25, 50])
            ->columns(array_merge($columns, $hasVisibility ? [ToggleColumn::make('is_visible')->label(__('Visible'))] : []))
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()]);
    }
}
