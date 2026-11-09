<?php

namespace App\Filament\Pages;

use App\Content\Menus\MenuTree;
use App\Content\Publisher;
use App\Content\Subjects\MenuSubject;
use App\Filament\Support\FieldFormBuilder;
use App\Filament\Support\PreviewLink;
use App\Models\ActivityLog;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Header, floating/mobile and footer menus. Each item links to a page, a section of a page, a project,
 * the CV, the e-mail or a checked web address; items can be hidden, reordered and have one level of sub-items.
 */
class EditMenus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars4;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'menus';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Navigation & texts');
    }

    public static function getNavigationLabel(): string
    {
        return __('Menus');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('edit-content');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Menus');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $publisher = app(Publisher::class);
        $dirty = Menu::query()->get()->filter(fn (Menu $m) => $publisher->hasUnpublishedChanges(new MenuSubject($m)))->count();

        return new HtmlString($dirty
            ? '<span style="color:#d97706">● '.e(__('Saved changes are waiting to be published.')).'</span>'
            : '<span style="color:#16a34a">● '.e(__('The website shows this version.')).'</span>');
    }

    public function mount(): void
    {
        $this->reloadForm();
    }

    protected function reloadForm(): void
    {
        $state = [];
        foreach (Menu::query()->get() as $menu) {
            $state[$menu->key] = MenuTree::fromModel($menu);
        }
        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (Menu::query()->orderBy('id')->get() as $menu) {
            $sections[] = Section::make(__($menu->name))->collapsible()->schema([self::itemsRepeater($menu->key, true)]);
        }

        return $schema->statePath('data')->components($sections);
    }

    private static function itemsRepeater(string $name, bool $allowChildren): Repeater
    {
        $schema = [
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                TextInput::make('label.ar')->label(__('Text (Arabic)'))->required()->maxLength(60)->extraInputAttributes(['dir' => 'rtl']),
                TextInput::make('label.en')->label(__('Text (English)'))->required()->maxLength(60),
                Toggle::make('visible')->label(__('Visible'))->default(true)->inline(false),
            ]),
            Group::make(FieldFormBuilder::linkFields('link'))->columns(['default' => 1, 'lg' => 2]),
        ];
        if ($allowChildren) {
            $schema[] = self::itemsRepeater('children', false)->label(__('Sub-items'))->addActionLabel(__('Add sub-item'));
        }

        return Repeater::make($name)->hiddenLabel($allowChildren)
            ->schema($schema)
            ->reorderableWithButtons()->collapsible()->collapsed()->cloneable()
            ->defaultItems(0)->maxItems($allowChildren ? 12 : 8)
            ->itemLabel(fn (array $state) => ($state['label'][app()->getLocale()] ?? '') ?: ($state['label']['en'] ?? null))
            ->addActionLabel(__('Add menu item'));
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('saveDraft')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')->label(__('Save draft'))->icon('heroicon-o-check')->color('gray')->keyBindings(['mod+s'])->action(fn () => $this->saveDraft()),
            Action::make('preview')->label(__('Preview'))->icon('heroicon-o-eye')->color('gray')
                ->action(function () {
                    if ($this->saveDraft(false)) {
                        $this->js('window.open('.json_encode(PreviewLink::to('', app()->getLocale())).', "_blank", "noopener")');
                    }
                }),
            Action::make('publish')->label(__('Publish menus'))->icon('heroicon-o-rocket-launch')
                ->visible(fn () => Gate::allows('publish'))->requiresConfirmation()
                ->action(function () {
                    if (! $this->saveDraft(false)) {
                        return;
                    }
                    foreach (Menu::query()->get() as $menu) {
                        app(Publisher::class)->publish(new MenuSubject($menu), auth()->user());
                    }
                    Notification::make()->success()->title(__('Menus published.'))->send();
                }),
            Action::make('discard')->label(__('Discard changes'))->icon('heroicon-o-arrow-uturn-left')->color('danger')->link()
                ->visible(fn () => Gate::allows('publish'))->requiresConfirmation()
                ->action(function () {
                    foreach (Menu::query()->get() as $menu) {
                        app(Publisher::class)->discard(new MenuSubject($menu));
                    }
                    $this->reloadForm();
                    Notification::make()->success()->title(__('Draft reset to the live version.'))->send();
                }),
        ];
    }

    public function saveDraft(bool $notify = true): bool
    {
        $state = $this->form->getState();
        try {
            DB::transaction(function () use ($state) {
                foreach (Menu::query()->get() as $menu) {
                    MenuTree::save($menu, array_values($state[$menu->key] ?? []));
                }
            });
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return false;
        }
        ActivityLog::record('draft_saved', __('Menus'), 'menu:*');
        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. The live site has not changed.'))->send();
        }

        return true;
    }
}
