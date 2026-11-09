<?php

namespace App\Filament\Support;

use App\Content\Collections;
use App\Content\Contracts\ContentSubject;
use App\Content\Subjects\CollectionSubject;
use App\Filament\Concerns\HasPublishingActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

/**
 * List screen for a shared list (services, experience, …): add, edit, reorder and hide rows; the whole list
 * is published together. Rows are saved straight into the draft; Publish puts the list live.
 */
abstract class ManagesCollection extends ManageRecords
{
    use HasPublishingActions;

    protected function collectionType(): string
    {
        return (string) Collections::typeFor(static::getResource()::getModel());
    }

    protected function subject(): ContentSubject
    {
        return new CollectionSubject($this->collectionType());
    }

    public function saveDraft(bool $notify = true): bool
    {
        return true; // every row is saved as soon as it is edited
    }

    protected function reloadForm(): void
    {
        $this->resetTable();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->publishingStatus();
    }

    protected function previewPath(): ?string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        $actions = array_values(array_filter($this->publishingActions(), fn ($a) => $a->getName() !== 'saveDraft'));

        return [CreateAction::make()->label(__('Add')), ...$actions];
    }
}
