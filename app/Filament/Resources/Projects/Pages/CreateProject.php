<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use SyncsProjectRelations;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRelations = $this->pullRelations($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncRelations($this->record, $this->pendingRelations);
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('edit', ['record' => $this->record]);
    }
}
