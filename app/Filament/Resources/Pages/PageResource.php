<?php

namespace App\Filament\Resources\Pages;

use App\Content\Publisher;
use App\Content\Subjects\PageSubject;
use App\Filament\Resources\Pages\Pages\EditPageContent;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * "Pages": every page of the website. Opening one shows its sections in the order visitors see them.
 * Below the list in the menu, each page has its own shortcut (Home, Work, About…).
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Site content');
    }

    public static function getModelLabel(): string
    {
        return __('Page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pages');
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();
        try {
            foreach (Page::query()->ordered()->get(['id', 'title', 'key']) as $i => $page) {
                $items[] = NavigationItem::make($page->tr('title'))
                    ->key('page-'.$page->id)
                    ->group(static::getNavigationGroup())
                    ->parentItem(static::getNavigationLabel())
                    ->sort(static::getNavigationSort() + $i + 1)
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName().'.edit') && (int) request()->route('record') === $page->id)
                    ->url(static::getUrl('edit', ['record' => $page->id]));
            }
        } catch (Throwable) {
            // database not ready (fresh install): only the list entry is shown
        }

        return $items;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('Page'))
                    ->formatStateUsing(fn (Page $record) => $record->tr('title'))
                    ->description(fn (Page $record) => $record->isHome() ? '/' : '/'.$record->slug)
                    ->searchable(query: fn ($query, string $search) => $query->where('title', 'like', "%{$search}%")),
                TextColumn::make('sections_count')->counts('sections')->label(__('Sections'))->badge(),
                TextColumn::make('state')->label(__('Status'))->badge()
                    ->state(fn (Page $record) => self::state($record))
                    ->color(fn (string $state) => $state === __('Live') ? 'success' : 'warning'),
                IconColumn::make('is_visible')->label(__('Visible'))->boolean(),
                TextColumn::make('key')->label(__('Type'))->formatStateUsing(fn (?string $state) => $state ? __('Main page') : __('Added page'))->placeholder(__('Added page')),
            ])
            ->recordActions([
                Action::make('edit')->label(__('Open editor'))->icon('heroicon-o-pencil-square')
                    ->url(fn (Page $record) => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->visible(fn (Page $record) => ! $record->isSystem() && Gate::allows('publish'))
                    ->modalDescription(__('The page disappears from the website immediately. Its published history is kept.')),
            ])
            ->recordUrl(fn (Page $record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function state(Page $page): string
    {
        $publisher = app(Publisher::class);
        $subject = new PageSubject($page);
        if ($publisher->live($subject->key()) === null) {
            return __('Not published');
        }

        return $publisher->hasUnpublishedChanges($subject) ? __('Unpublished changes') : __('Live');
    }

    public static function canDelete($record): bool
    {
        return $record instanceof Page && ! $record->isSystem() && Gate::allows('publish');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPageContent::route('/{record}/edit'),
        ];
    }
}
