<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Support\Bilingual;
use App\Filament\Support\MediaPicker;
use App\Models\Category;
use App\Models\Project;
use App\Models\Technology;
use App\Rules\SafeLinkUrl;
use App\Support\Icons;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

/** The project editor form: story, facts, images, address and links, SEO (each in its own tab). */
final class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make(__('Story'))->icon('heroicon-o-document-text')->schema([
                    Bilingual::field('title', 'Project name', max: 80, required: true),
                    Bilingual::field('kind', 'Type of project (e.g. Corporate website & CMS)', max: 120, required: true),
                    Bilingual::field('summary', 'Summary (cards, list, search results)', true, 500, true),
                    Bilingual::field('brief', 'The brief (challenge)', true, 800),
                    Bilingual::field('built', 'The solution', true, 1500),
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
                    Bilingual::field('status_label', 'Status shown on the site (e.g. Live)', max: 40, required: true),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Select::make('status_tone')->label(__('Status dot'))->native(false)->selectablePlaceholder(false)
                            ->options(['live' => __('Turquoise (live)'), 'neutral' => __('Grey')])->default('neutral'),
                        TextInput::make('year')->label(__('Year'))->maxLength(10),
                        ColorPicker::make('color')->label(__('Project colour (behind images)'))->regex('/^#[0-9A-Fa-f]{6}$/'),
                    ]),
                    Bilingual::field('role', 'My role (empty = default from Project page labels)', max: 120),
                    Bilingual::field('client', 'Client (optional)', max: 120),
                    Bilingual::field('languages', 'Languages of the product (empty = default)', max: 120),
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
                    Bilingual::field('seo.title', 'Title in search results (empty = name — type)', max: 70),
                    Bilingual::field('seo.description', 'Description in search results (empty = summary)', true, 170),
                ]),
            ]),
        ]);
    }
}
