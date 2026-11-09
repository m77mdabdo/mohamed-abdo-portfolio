<?php

namespace App\Filament\Resources\Media;

use App\Content\Media\MediaLibrary;
use App\Content\Media\MediaUsage;
use App\Jobs\OptimizeImage;
use App\Models\ActivityLog;
use App\Models\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/** Media library: every image and PDF, its description (alt text), size, optimisation state and where it is used. */
class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Site content');
    }

    public static function getModelLabel(): string
    {
        return __('File');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Media library');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                TextInput::make('alt.ar')->label(__('Description (Arabic)'))->maxLength(200)->extraInputAttributes(['dir' => 'rtl'])
                    ->helperText(__('Read by screen readers and search engines. Describe what the image shows.')),
                TextInput::make('alt.en')->label(__('Description (English)'))->maxLength(200),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([24, 48, 96])
            ->columns([
                ImageColumn::make('preview')->label('')->state(fn (Media $m) => $m->isImage() ? $m->url() : null)->imageHeight(54)->imageWidth(86),
                TextColumn::make('original_name')->label(__('File'))->searchable()->limit(40)
                    ->description(fn (Media $m) => $m->altText() ?: __('No description')),
                TextColumn::make('kind')->label(__('Type'))->badge()->formatStateUsing(fn (string $state) => $state === 'image' ? __('Image') : __('PDF')),
                TextColumn::make('dimensions')->label(__('Size'))->state(fn (Media $m) => ($m->width ? $m->width.'×'.$m->height.' · ' : '').$m->humanSize()),
                TextColumn::make('status')->label(__('Optimised copies'))->badge()
                    ->formatStateUsing(fn (string $state, Media $m) => match ($state) {
                        'ready' => $m->isImage() ? __(':n copies', ['n' => count($m->variants ?? [])]) : '—',
                        'processing' => __('In progress'),
                        default => __('Failed: original is used'),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'ready' => 'success', 'processing' => 'warning', default => 'danger'
                    })
                    ->tooltip(fn (Media $m) => $m->error),
                TextColumn::make('created_at')->label(__('Uploaded'))->since()->sortable(),
            ])
            ->filters([SelectFilter::make('kind')->label(__('Type'))->options(['image' => __('Image'), 'document' => __('PDF')])])
            ->recordActions([
                EditAction::make()->label(__('Description'))->slideOver(),
                Action::make('open')->label(__('Open'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Media $m) => $m->url())->openUrlInNewTab(),
                Action::make('usage')->label(__('Where used'))->icon('heroicon-o-link')->color('gray')
                    ->modalSubmitAction(false)->modalCancelActionLabel(__('Close'))
                    ->modalContent(fn (Media $m) => new HtmlString(self::usageHtml(app(MediaUsage::class)->find($m->id)))),
                Action::make('reoptimise')->label(__('Re-create copies'))->icon('heroicon-o-arrow-path')->color('gray')
                    ->visible(fn (Media $m) => $m->isImage() && $m->status !== 'processing')
                    ->action(function (Media $m) {
                        $m->forceFill(['status' => 'processing'])->save();
                        OptimizeImage::dispatch($m->id);
                        Notification::make()->success()->title(__('Optimisation queued.'))->send();
                    }),
                Action::make('delete')->label(__('Delete'))->icon('heroicon-o-trash')->color('danger')
                    ->visible(fn (Media $m) => Gate::allows('delete', $m))
                    ->requiresConfirmation()
                    ->modalDescription(__('The file is checked first: if a page, project, setting or the live site still uses it, it is not deleted.'))
                    ->action(function (Media $m) {
                        $places = app(MediaUsage::class)->find($m->id);
                        if ($places) {
                            Notification::make()->danger()->title(__('This file is still in use and was not deleted.'))
                                ->body(implode("\n", array_slice($places, 0, 8)))->persistent()->send();

                            return;
                        }
                        app(MediaLibrary::class)->delete($m);
                        ActivityLog::record('media_deleted', $m->original_name, 'media:'.$m->id);
                        Notification::make()->success()->title(__('File deleted.'))->send();
                    }),
            ]);
    }

    private static function usageHtml(array $places): string
    {
        if (! $places) {
            return '<p>'.e(__('Not used anywhere. It can be deleted safely.')).'</p>';
        }

        return '<ul style="list-style:disc;padding-inline-start:1.2rem;display:grid;gap:.3rem">'.implode('', array_map(fn ($p) => '<li>'.e($p).'</li>', $places)).'</ul>';
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageMedia::route('/')];
    }
}
