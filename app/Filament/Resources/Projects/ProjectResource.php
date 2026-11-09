<?php

namespace App\Filament\Resources\Projects;

use App\Content\Publisher;
use App\Content\Subjects\ProjectSubject;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Support\MediaPicker;
use App\Models\Category;
use App\Models\Project;
use App\Models\Technology;
use App\Rules\SafeLinkUrl;
use App\Support\Icons;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Projects: every field of a project page (story, facts, stack, cover, desktop and mobile galleries, links, SEO).
 * Saving keeps a draft; "Publish" on the project puts it live.
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): ?string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Project');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Projects');
    }

    /** Arabic + English inputs side by side for one translatable column. */
    public static function bilingual(string $name, string $label, bool $textarea = false, int $max = 200, bool $required = false): Fieldset
    {
        $make = fn (string $locale) => ($textarea ? Textarea::make("{$name}.{$locale}")->rows(3)->autosize() : TextInput::make("{$name}.{$locale}"))
            ->label($locale === 'ar' ? __('Arabic') : __('English'))
            ->maxLength($max)
            ->required($required && $locale === 'en')
            ->extraInputAttributes(['dir' => $locale === 'ar' ? 'rtl' : 'ltr']);

        return Fieldset::make(__($label))->schema([$make('ar'), $make('en')])->columns(['default' => 1, 'lg' => 2]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make(__('Story'))->icon('heroicon-o-document-text')->schema([
                    self::bilingual('title', 'Project name', max: 80, required: true),
                    self::bilingual('kind', 'Type of project (e.g. Corporate website & CMS)', max: 120, required: true),
                    self::bilingual('summary', 'Summary (cards, list, search results)', true, 500, true),
                    self::bilingual('brief', 'The brief (challenge)', true, 800),
                    self::bilingual('built', 'The solution', true, 1500),
                    Repeater::make('features')->label(__('Highlights'))
                        ->schema([Grid::make(['default' => 1, 'lg' => 2])->schema([
                            TextInput::make('ar')->label(__('Arabic'))->maxLength(160)->extraInputAttributes(['dir' => 'rtl']),
                            TextInput::make('en')->label(__('English'))->maxLength(160),
                        ])])
                        ->reorderableWithButtons()->collapsible()->maxItems(10)->defaultItems(0)
                        ->itemLabel(fn (array $state) => $state[app()->getLocale()] ?? $state['en'] ?? null)
                        ->addActionLabel(__('Add highlight')),
                ]),
                Tab::make(__('Facts'))->icon('heroicon-o-identification')->schema([
                    self::bilingual('status_label', 'Status shown on the site (e.g. Live)', max: 40, required: true),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Select::make('status_tone')->label(__('Status dot'))->native(false)->selectablePlaceholder(false)
                            ->options(['live' => __('Turquoise (live)'), 'neutral' => __('Grey')])->default('neutral'),
                        TextInput::make('year')->label(__('Year'))->maxLength(10),
                        ColorPicker::make('color')->label(__('Project colour (behind images)'))->regex('/^#[0-9A-Fa-f]{6}$/'),
                    ]),
                    self::bilingual('role', 'My role (empty = default from Project page labels)', max: 120),
                    self::bilingual('client', 'Client (optional)', max: 120),
                    self::bilingual('languages', 'Languages of the product (empty = default)', max: 120),
                    CheckboxList::make('categories')->label(__('Categories (project filter)'))
                        ->relationship('categories', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Category $c) => $c->tr('name'))
                        ->columns(['default' => 1, 'md' => 2]),
                    Repeater::make('stack_items')->label(__('Stack (technologies, in order)'))
                        ->schema([Select::make('technology_id')->label(__('Technology'))->native(false)->searchable()->required()
                            ->options(fn () => Technology::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->createOptionForm([TextInput::make('name')->label(__('Name'))->required()->maxLength(60)->unique('technologies', 'name')])
                            ->createOptionUsing(fn (array $data) => Technology::query()->create(['name' => $data['name'], 'icon' => Icons::guess($data['name'])])->id)])
                        ->reorderableWithButtons()->defaultItems(0)->maxItems(15)->grid(['default' => 1, 'md' => 3])
                        ->addActionLabel(__('Add technology')),
                ]),
                Tab::make(__('Images'))->icon('heroicon-o-photo')->schema([
                    MediaPicker::make('cover_media_id', 'Cover image (16:10, used in the reel, list and page)'),
                    Repeater::make('desktop_shots')->label(__('Computer screenshots'))
                        ->schema([
                            MediaPicker::make('media_id', 'Image')->required(),
                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                TextInput::make('caption.ar')->label(__('Description (Arabic)'))->maxLength(160)->extraInputAttributes(['dir' => 'rtl']),
                                TextInput::make('caption.en')->label(__('Description (English)'))->maxLength(160),
                            ]),
                        ])
                        ->reorderableWithButtons()->collapsible()->defaultItems(0)->maxItems(12)->addActionLabel(__('Add screenshot')),
                    Repeater::make('mobile_shots')->label(__('Phone screenshots'))
                        ->schema([
                            MediaPicker::make('media_id', 'Image')->required(),
                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                TextInput::make('caption.ar')->label(__('Description (Arabic)'))->maxLength(160)->extraInputAttributes(['dir' => 'rtl']),
                                TextInput::make('caption.en')->label(__('Description (English)'))->maxLength(160),
                            ]),
                        ])
                        ->reorderableWithButtons()->collapsible()->defaultItems(0)->maxItems(8)->addActionLabel(__('Add phone screenshot')),
                ]),
                Tab::make(__('Address, links & order'))->icon('heroicon-o-link')->schema([
                    TextInput::make('slug')->label(__('Address'))->prefix('/ar/projects/')->required()->maxLength(80)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->rule(fn (?Project $record) => Rule::unique('projects', 'slug')->ignore($record?->id))
                        ->validationMessages(['regex' => __('Use lowercase Latin letters, numbers and dashes.'), 'unique' => __('Another project already uses this address.')])
                        ->helperText(__('Changing it keeps the old address working (it redirects).')),
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('live_url')->label(__('Live site'))->maxLength(255)->rule(new SafeLinkUrl)->placeholder('https://')->extraInputAttributes(['dir' => 'ltr']),
                        TextInput::make('repo_url')->label(__('Source code (GitHub)'))->maxLength(255)->rule(new SafeLinkUrl)->placeholder('https://')->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                    Select::make('next_project_id')->label(__('Next project (empty = the next one in order)'))->native(false)
                        ->options(fn (?Project $record) => Project::query()->ordered()->when($record, fn ($q) => $q->whereKeyNot($record->id))->get()->mapWithKeys(fn ($p) => [$p->id => $p->tr('title')])->all()),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Toggle::make('is_visible')->label(__('Visible on the site'))->default(true),
                        Toggle::make('is_featured')->label(__('Featured (can be shown alone in the reel)')),
                        TextInput::make('sort_order')->label(__('Order'))->numeric()->integer()->minValue(0)->default(fn () => (int) Project::query()->max('sort_order') + 1),
                    ]),
                ]),
                Tab::make(__('SEO'))->icon('heroicon-o-magnifying-glass')->schema([
                    self::bilingual('seo.title', 'Title in search results (empty = name — type)', max: 70),
                    self::bilingual('seo.description', 'Description in search results (empty = summary)', true, 170),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
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

    public static function canDelete($record): bool
    {
        return Gate::allows('publish');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
