<?php

namespace App\Filament\Resources\Activity;

use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Who saved, published, restored or deleted what, and when (owner only). */
class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'activity';

    public static function getNavigationGroup(): ?string
    {
        return __('Inbox & people');
    }

    public static function getModelLabel(): string
    {
        return __('Activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity log');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->paginated([50, 100])
            ->columns([
                TextColumn::make('created_at')->label(__('When'))->dateTime('Y-m-d H:i', 'Asia/Riyadh')->sortable(),
                TextColumn::make('user.name')->label(__('Who'))->placeholder(__('System')),
                TextColumn::make('action')->label(__('Action'))->badge()->formatStateUsing(fn (string $state) => __(str_replace('_', ' ', ucfirst($state)))),
                TextColumn::make('description')->label(__('What'))->searchable()->wrap(),
            ])
            ->filters([SelectFilter::make('action')->label(__('Action'))->options(fn () => ActivityLog::query()->distinct()->pluck('action', 'action')->all())]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivity::route('/')];
    }
}
