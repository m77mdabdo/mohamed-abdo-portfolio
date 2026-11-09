<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Content\Publisher;
use App\Content\Subjects\ProjectSubject;
use App\Models\Category;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/** Project list: drag to reorder, quick featured/visible switches, publishing state per project. */
final class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('cover'))
            ->columns([
                ImageColumn::make('cover_thumb')->label('')->state(fn (Project $r) => $r->cover?->url())->imageHeight(40)->imageWidth(64),
                TextColumn::make('title')->label(__('Project'))->formatStateUsing(fn (Project $r) => $r->tr('title'))
                    ->description(fn (Project $r) => $r->tr('kind'))
                    ->searchable(query: fn ($q, string $s) => $q->where('title', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%")),
                TextColumn::make('status_label')->label(__('Status on site'))->formatStateUsing(fn (Project $r) => $r->tr('status_label'))->badge()
                    ->color(fn (Project $r) => $r->status_tone === 'live' ? 'info' : 'gray'),
                ToggleColumn::make('is_featured')->label(__('Featured')),
                ToggleColumn::make('is_visible')->label(__('Visible')),
                TextColumn::make('publish_state')->label(__('Publishing'))->badge()
                    ->state(fn (Project $r) => self::state($r))
                    ->color(fn (string $state) => $state === __('Live') ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('categories')->label(__('Category'))->relationship('categories', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $c) => $c->tr('name')),
            ])
            ->recordActions([
                Action::make('view')->label(__('View live'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Project $r) => route('projects.show', ['locale' => app()->getLocale(), 'slug' => $r->slug]))->openUrlInNewTab(),
                DeleteAction::make()->visible(fn () => Gate::allows('publish'))
                    ->modalDescription(__('The project disappears from the website immediately. Its images stay in the media library.')),
            ]);
    }

    public static function state(Project $project): string
    {
        $publisher = app(Publisher::class);
        $subject = new ProjectSubject($project);
        if ($publisher->live($subject->key()) === null) {
            return __('Not published');
        }

        return $publisher->hasUnpublishedChanges($subject) ? __('Unpublished changes') : __('Live');
    }
}
