<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Content\Contracts\ContentSubject;
use App\Content\Subjects\ProjectSubject;
use App\Filament\Concerns\HasPublishingActions;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** @property Project $record */
class EditProject extends EditRecord
{
    use HasPublishingActions;
    use SyncsProjectRelations;

    protected static string $resource = ProjectResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('Project').': '.$this->record->tr('title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->publishingStatus();
    }

    protected function subject(): ContentSubject
    {
        return new ProjectSubject($this->record->refresh());
    }

    protected function previewPath(): ?string
    {
        return 'projects/'.$this->record->slug;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillRelations($this->record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingRelations = $this->pullRelations($data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        if ($record instanceof Project) {
            $this->syncRelations($record, $this->pendingRelations);
        }

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('Draft saved. The live site has not changed.');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    public function saveDraft(bool $notify = true): bool
    {
        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: $notify);
        } catch (ValidationException $e) {
            throw $e;
        }

        return true;
    }

    protected function reloadForm(): void
    {
        $this->record->refresh();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->publishingActions(),
            DeleteAction::make()->visible(fn () => Gate::allows('publish')),
        ];
    }

    /** The draft is saved through the "Save draft" action at the top (and Ctrl/⌘+S). */
    protected function getFormActions(): array
    {
        return [];
    }
}
