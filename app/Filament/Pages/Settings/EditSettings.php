<?php

namespace App\Filament\Pages\Settings;

use App\Content\Contracts\ContentSubject;
use App\Content\Fields\FieldSchema;
use App\Content\Settings\SettingsGroups;
use App\Content\Subjects\SettingsSubject;
use App\Filament\Concerns\HasPublishingActions;
use App\Filament\Support\FieldFormBuilder;
use App\Models\ActivityLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

/**
 * One settings group (identity, footer, SEO, appearance…) edited as a typed form, saved as a draft and
 * published like a page. Subclasses only name their group.
 */
abstract class EditSettings extends Page
{
    use HasPublishingActions;

    protected static string $group = '';

    /** Only the owner may change these groups (they affect delivery, not just wording). */
    protected static bool $ownerOnly = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string $menuGroup = 'Settings';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$menuGroup);
    }

    public static function getNavigationLabel(): string
    {
        return SettingsGroups::labels()[static::$group] ?? static::$group;
    }

    public static function canAccess(): bool
    {
        return static::$ownerOnly ? Gate::allows('manage-settings') : Gate::allows('edit-content');
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->publishingStatus();
    }

    public function mount(): void
    {
        $this->reloadForm();
    }

    protected function reloadForm(): void
    {
        $this->form->fill($this->subject()->draft());
    }

    protected function subject(): ContentSubject
    {
        return new SettingsSubject(static::$group);
    }

    protected function previewPath(): ?string
    {
        return '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make()->schema(FieldFormBuilder::components(SettingsGroups::fields(static::$group))),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('saveDraft')]);
    }

    protected function getHeaderActions(): array
    {
        return $this->publishingActions();
    }

    public function saveDraft(bool $notify = true): bool
    {
        $state = $this->form->getState();
        $subject = new SettingsSubject(static::$group);
        $model = $subject->model();
        $model->data = FieldSchema::clean(SettingsGroups::fields(static::$group), $state);
        $model->save();
        ActivityLog::record('draft_saved', $subject->label(), $subject->key());
        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. The live site has not changed.'))->send();
        }

        return true;
    }
}
