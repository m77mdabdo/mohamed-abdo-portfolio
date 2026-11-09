<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Content\Contracts\ContentSubject;
use App\Content\Pages\PageDraftWriter;
use App\Content\Sections\SectionRegistry;
use App\Content\Sections\SectionType;
use App\Content\Subjects\PageSubject;
use App\Filament\Concerns\HasPublishingActions;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\FieldFormBuilder;
use App\Filament\Support\MediaPicker;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

/**
 * The page editor: the page's sections in the order visitors see them. Each section can be opened,
 * edited (texts in both languages, images, buttons, links), shown or hidden, moved, duplicated or removed;
 * new sections are added from the library. Saving keeps a draft; publishing puts it live.
 *
 * @property Page $record
 * @property-read Schema $form
 */
class EditPageContent extends ResourcePage
{
    use HasPublishingActions;
    use InteractsWithRecord;

    protected static string $resource = PageResource::class;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public int $lockVersion = 0;

    public function mount(int|string $record): void
    {
        $resolved = $this->resolveRecord($record);
        abort_unless($resolved instanceof Page, 404);
        $this->record = $resolved;
        $this->reloadForm();
    }

    protected function reloadForm(): void
    {
        $page = $this->record->refresh()->load('sections');
        $this->lockVersion = (int) $page->lock_version;
        $seo = $page->seo ?? [];
        $this->form->fill([
            'title' => $page->title,
            'slug' => $page->slug,
            'is_visible' => $page->is_visible,
            'seo' => [
                'title' => $seo['title'] ?? ['en' => '', 'ar' => ''],
                'description' => $seo['description'] ?? ['en' => '', 'ar' => ''],
                'image' => $seo['image'] ?? null,
                'noindex' => $seo['noindex'] ?? false,
            ],
            'sections' => $page->sections->map(fn ($s) => [
                'type' => $s->type,
                'data' => ['uid' => $s->uid, 'visible' => $s->is_visible, 'anchor' => $s->anchor, 'preset' => $s->preset, 'data' => $s->data],
            ])->values()->all(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('Page').': '.$this->record->tr('title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->publishingStatus();
    }

    public function getBreadcrumbs(): array
    {
        return [PageResource::getUrl() => __('Pages'), '#' => $this->record->tr('title')];
    }

    protected function subject(): ContentSubject
    {
        return new PageSubject($this->record);
    }

    protected function previewPath(): ?string
    {
        return $this->record->isHome() ? '' : $this->record->slug;
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->publishingActions(),
            Action::make('live')->label(__('View live page'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->link()
                ->url(fn () => $this->record->isHome() ? route('home', ['locale' => app()->getLocale()]) : route('page', ['locale' => app()->getLocale(), 'slug' => $this->record->slug]))
                ->openUrlInNewTab(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make(__('Sections'))->icon('heroicon-o-rectangle-stack')->schema([self::sectionsBuilder()]),
                Tab::make(__('Page settings & SEO'))->icon('heroicon-o-cog-6-tooth')->schema($this->settingsSchema()),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('saveDraft')]);
    }

    public static function sectionsBuilder(): Builder
    {
        $blocks = array_map(fn (SectionType $type) => Block::make($type->key())
            ->label(function (?array $state) use ($type) {
                $label = $type->label();
                if (! $state) {
                    return $label;
                }
                $anchor = ($state['anchor'] ?? null) ? '  #'.$state['anchor'] : '';
                $hidden = ($state['visible'] ?? true) ? '' : '  · '.__('hidden');

                return $label.$anchor.$hidden;
            })
            ->icon($type->icon())
            ->schema([
                Hidden::make('uid'),
                Grid::make(['default' => 1, 'md' => 3])->schema(array_filter([
                    Toggle::make('visible')->label(__('Visible on the site'))->default(true)->inline(false),
                    TextInput::make('anchor')->label(__('Link name (#anchor)'))->prefix('#')->maxLength(60)->default($type->defaultAnchor())
                        ->helperText(__('Menus and buttons can jump to this section.'))->extraInputAttributes(['dir' => 'ltr']),
                    $type->presets() ? Select::make('preset')->label(__('Design'))->native(false)->options($type->presets())->default(array_key_first($type->presets())) : null,
                ])),
                Group::make(FieldFormBuilder::components($type->fields(), 'data')),
            ]), array_values(app(SectionRegistry::class)->all()));

        return Builder::make('sections')->hiddenLabel()
            ->blocks($blocks)
            ->collapsible()->collapsed()
            ->cloneable()
            ->reorderableWithButtons()
            ->blockNumbers(false)
            ->blockPickerColumns(['default' => 1, 'md' => 2])
            ->blockPickerWidth('2xl')
            ->addActionLabel(__('Add a section from the library'));
    }

    /** @return array<int, Component> */
    private function settingsSchema(): array
    {
        $system = $this->record->isSystem();

        return [
            Section::make(__('Page'))->schema([
                Fieldset::make(__('Title (menus, browser tab)'))->schema([
                    TextInput::make('title.ar')->label(__('Arabic'))->required()->maxLength(80)->extraInputAttributes(['dir' => 'rtl']),
                    TextInput::make('title.en')->label(__('English'))->required()->maxLength(80),
                ])->columns(['default' => 1, 'lg' => 2]),
                TextInput::make('slug')->label(__('Address'))->prefix('/'.app()->getLocale().'/')->maxLength(80)->required()
                    ->disabled($system)->dehydrated(! $system)
                    ->helperText($system ? __('Main pages keep a fixed address so links never break.') : __('Changing it keeps the old address working (it redirects).')),
                Toggle::make('is_visible')->label(__('Page is visible on the site'))->disabled($this->record->isHome()),
            ]),
            Section::make(__('Search engines & sharing'))->schema([
                Fieldset::make(__('Title in search results (leave empty to use the page title)'))->schema([
                    TextInput::make('seo.title.ar')->label(__('Arabic'))->maxLength(70)->extraInputAttributes(['dir' => 'rtl']),
                    TextInput::make('seo.title.en')->label(__('English'))->maxLength(70),
                ])->columns(['default' => 1, 'lg' => 2]),
                Fieldset::make(__('Description in search results'))->schema([
                    Textarea::make('seo.description.ar')->label(__('Arabic'))->maxLength(170)->rows(2)->extraInputAttributes(['dir' => 'rtl']),
                    Textarea::make('seo.description.en')->label(__('English'))->maxLength(170)->rows(2),
                ])->columns(['default' => 1, 'lg' => 2]),
                MediaPicker::make('seo.image', 'Sharing image (leave empty to use the site image)'),
                Toggle::make('seo.noindex')->label(__('Hide this page from search engines')),
            ]),
        ];
    }

    public function saveDraft(bool $notify = true): bool
    {
        try {
            $state = $this->form->getState();
            $sections = array_map(fn (array $item) => [
                'type' => $item['type'],
                'uid' => $item['data']['uid'] ?? null,
                'visible' => $item['data']['visible'] ?? true,
                'anchor' => $item['data']['anchor'] ?? null,
                'preset' => $item['data']['preset'] ?? null,
                'data' => $item['data']['data'] ?? [],
            ], array_values($state['sections'] ?? []));

            app(PageDraftWriter::class)->save($this->record, [
                'title' => $state['title'] ?? $this->record->title,
                'slug' => $state['slug'] ?? $this->record->slug,
                'is_visible' => $state['is_visible'] ?? true,
                'seo' => $state['seo'] ?? [],
            ], $sections, $this->lockVersion);
        } catch (ValidationException $e) {
            Notification::make()->danger()->title(__('Not saved'))->body(collect($e->errors())->flatten()->first())->persistent()->send();
            if (! array_key_exists('lock', $e->errors()) && ! array_key_exists('slug', $e->errors()) && ! array_key_exists('sections', $e->errors())) {
                throw $e;
            }

            return false;
        }

        $this->reloadForm();
        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. The live site has not changed.'))->send();
        }

        return true;
    }
}
