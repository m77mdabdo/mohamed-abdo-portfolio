<?php

namespace App\Filament\Widgets;

use App\Content\Publisher;
use App\Content\SubjectRegistry;
use App\Models\ContentRevision;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

/**
 * Everything saved but not yet live, in one list: pages, projects, lists, menus and settings.
 * The owner can publish one item or all of them; editors see what is waiting.
 */
class PublishingCenter extends Widget
{
    protected string $view = 'filament.widgets.publishing-center';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /** @return array<int, array{key: string, label: string, never: bool}> */
    public function pending(): array
    {
        $publisher = app(Publisher::class);
        $published = ContentRevision::query()->distinct()->pluck('subject')->flip();
        $out = [];
        foreach (app(SubjectRegistry::class)->all() as $subject) {
            if ($publisher->hasUnpublishedChanges($subject)) {
                $out[] = ['key' => $subject->key(), 'label' => $subject->label(), 'never' => ! $published->has($subject->key())];
            }
        }

        return $out;
    }

    public function canPublish(): bool
    {
        return Gate::allows('publish');
    }

    public function publishOne(string $key): void
    {
        abort_unless($this->canPublish(), 403);
        if ($subject = app(SubjectRegistry::class)->resolve($key)) {
            app(Publisher::class)->publish($subject, auth()->user());
            Notification::make()->success()->title(__('Published: :name', ['name' => $subject->label()]))->send();
        }
    }

    public function publishAll(): void
    {
        abort_unless($this->canPublish(), 403);
        $n = 0;
        foreach ($this->pending() as $item) {
            if ($subject = app(SubjectRegistry::class)->resolve($item['key'])) {
                app(Publisher::class)->publish($subject, auth()->user());
                $n++;
            }
        }
        Notification::make()->success()->title(__(':n item(s) published.', ['n' => $n]))->send();
    }
}
