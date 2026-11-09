<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Content\Publisher;
use App\Content\Subjects\ProjectSubject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    public function getSubheading(): ?string
    {
        return __('Drag rows to change the order on the site. Order, featured and visible switches are saved as a draft: publish the project (or "Publish all changes") to apply them.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('New project')),
            Action::make('publishAll')->label(__('Publish all project changes'))->icon('heroicon-o-rocket-launch')
                ->visible(fn () => Gate::allows('publish'))
                ->requiresConfirmation()
                ->action(function () {
                    $publisher = app(Publisher::class);
                    $n = 0;
                    foreach (Project::query()->get() as $project) {
                        $subject = new ProjectSubject($project);
                        if ($publisher->hasUnpublishedChanges($subject)) {
                            $publisher->publish($subject, auth()->user());
                            $n++;
                        }
                    }
                    Notification::make()->success()->title(__(':n project(s) published.', ['n' => $n]))->send();
                }),
        ];
    }
}
