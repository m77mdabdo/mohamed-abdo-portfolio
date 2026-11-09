<?php

namespace App\Filament\Pages\Settings;

use App\Content\Settings\UiStrings;
use App\Content\Subjects\SettingsSubject;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Small interface texts (form messages, accessibility labels, error pages, preview bar). Each field shows the
 * shipped default as a placeholder: leave it empty to keep the default, type to override it.
 */
class EditInterfaceTexts extends EditSettings
{
    protected static string $group = 'ui';

    protected static string $menuGroup = 'Navigation & texts';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $slug = 'settings/interface-texts';

    public function form(Schema $schema): Schema
    {
        $groups = [];
        foreach (UiStrings::keys() as $key) {
            $groups[Str::before($key, '.')][] = $key;
        }
        $titles = ['a11y' => __('Accessibility labels'), 'form' => __('Contact form messages'), 'cv' => __('CV'), 'project' => __('Project images'), 'errors' => __('Error pages'), 'preview' => __('Preview bar')];

        $sections = [];
        foreach ($groups as $group => $keys) {
            $fields = [];
            foreach ($keys as $key) {
                $path = str_replace('.', '__', $key); // dots would nest the form state
                $fields[] = Grid::make(['default' => 1, 'lg' => 2])->schema([
                    TextInput::make($path.'.ar')->label($key.' · '.__('Arabic'))->placeholder(UiStrings::defaults('ar')[$key] ?? '')->maxLength(300)->extraInputAttributes(['dir' => 'rtl']),
                    TextInput::make($path.'.en')->label($key.' · '.__('English'))->placeholder(UiStrings::defaults('en')[$key] ?? '')->maxLength(300),
                ]);
            }
            $sections[] = Section::make($titles[$group] ?? $group)->collapsible()->collapsed($group !== 'form')->schema($fields);
        }

        return $schema->statePath('data')->components([
            Section::make()->description(__('Leave a field empty to keep the default text shown in grey. Words starting with ":" (like :name) are filled in automatically — keep them.'))->schema([]),
            ...$sections,
        ]);
    }

    protected function reloadForm(): void
    {
        $state = [];
        foreach ((new SettingsSubject('ui'))->draft() as $key => $value) {
            $state[str_replace('.', '__', $key)] = $value;
        }
        $this->form->fill($state);
    }

    public function saveDraft(bool $notify = true): bool
    {
        $data = [];
        foreach ($this->form->getState() as $path => $value) {
            $data[str_replace('__', '.', $path)] = $value;
        }
        $subject = new SettingsSubject('ui');
        $model = $subject->model();
        $model->data = UiStrings::cleanOverrides($data);
        $model->save();
        ActivityLog::record('draft_saved', $subject->label(), $subject->key());
        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. The live site has not changed.'))->send();
        }

        return true;
    }
}
