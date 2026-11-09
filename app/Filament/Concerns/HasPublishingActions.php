<?php

namespace App\Filament\Concerns;

use App\Content\Contracts\ContentSubject;
use App\Content\Publisher;
use App\Filament\Support\PreviewLink;
use App\Models\ContentRevision;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Save draft / Preview / Publish / History / Discard for any editor screen.
 * The using class provides: subject(), saveDraft(): bool, previewPath(): ?string and reloadForm().
 * Saving never changes the live site; only Publish (owner) does.
 */
trait HasPublishingActions
{
    abstract protected function subject(): ContentSubject;

    abstract public function saveDraft(bool $notify = true): bool;

    abstract protected function reloadForm(): void;

    protected function previewPath(): ?string
    {
        return null;
    }

    /** @return array<int, Action|ActionGroup> */
    protected function publishingActions(): array
    {
        $previews = [];
        if ($this->previewPath() !== null) {
            foreach (['ar' => __('Preview in Arabic'), 'en' => __('Preview in English')] as $locale => $label) {
                $previews[] = Action::make('preview_'.$locale)->label($label)->icon('heroicon-o-eye')
                    ->action(function () use ($locale) {
                        if (! $this->saveDraft(notify: false)) {
                            return;
                        }
                        $this->js('window.open('.json_encode(PreviewLink::to((string) $this->previewPath(), $locale)).', "_blank", "noopener")');
                    });
            }
        }

        return array_values(array_filter([
            Action::make('saveDraft')->label(__('Save draft'))->icon('heroicon-o-check')->color('gray')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->saveDraft()),
            $previews ? ActionGroup::make($previews)->label(__('Preview'))->icon('heroicon-o-eye')->button()->color('gray') : null,
            Action::make('publish')->label(__('Publish'))->icon('heroicon-o-rocket-launch')->color('primary')
                ->visible(fn () => Gate::allows('publish'))
                ->requiresConfirmation()
                ->modalHeading(__('Publish these changes?'))
                ->modalDescription(__('Visitors will see this version straight away. The current live version stays in the history and can be restored.'))
                ->action(function () {
                    if (! $this->saveDraft(notify: false)) {
                        return;
                    }
                    $revision = app(Publisher::class)->publish($this->subject(), auth()->user());
                    Notification::make()->success()->title(__('Published (version :v). The website now shows these changes.', ['v' => $revision->version]))->send();
                }),
            Action::make('history')->label(__('History'))->icon('heroicon-o-clock')->color('gray')
                ->modalHeading(__('Published versions'))
                ->modalDescription(__('Restoring copies a version into the draft; publish it afterwards to put it live (or use "Restore and publish").'))
                ->modalSubmitAction(false)->modalCancelActionLabel(__('Close'))->modalWidth('3xl')
                ->modalContent(fn () => view('filament.revision-history', [
                    'revisions' => ContentRevision::query()->where('subject', $this->subject()->key())->with('publisher')->orderByDesc('version')->limit(50)->get(['id', 'subject', 'version', 'note', 'published_by', 'created_at']),
                    'canPublish' => Gate::allows('publish'),
                ])),
            Action::make('discard')->label(__('Discard changes'))->icon('heroicon-o-arrow-uturn-left')->color('danger')->link()
                ->visible(fn () => Gate::allows('publish') && app(Publisher::class)->live($this->subject()->key()) !== null)
                ->requiresConfirmation()
                ->modalDescription(__('The draft goes back to exactly what the website shows now. Unpublished edits are lost.'))
                ->action(function () {
                    app(Publisher::class)->discard($this->subject());
                    $this->reloadForm();
                    Notification::make()->success()->title(__('Draft reset to the live version.'))->send();
                }),
        ]));
    }

    /** Called from the history modal. */
    public function restoreRevision(int $revisionId, bool $publish = false): void
    {
        abort_unless(Gate::allows('publish'), 403);
        $revision = ContentRevision::query()->findOrFail($revisionId);
        app(Publisher::class)->restore($this->subject(), $revision, auth()->user(), $publish);
        $this->reloadForm();
        $this->unmountAction();
        Notification::make()->success()->title($publish
            ? __('Version :v restored and published.', ['v' => $revision->version])
            : __('Version :v copied into the draft. Review it, then publish.', ['v' => $revision->version]))->send();
    }

    /** Status line under the page title: is the draft live or not? */
    protected function publishingStatus(): string|Htmlable
    {
        $publisher = app(Publisher::class);
        $live = $publisher->live($this->subject()->key());
        if ($live === null) {
            return new HtmlString('<span style="color:#d97706">● '.e(__('Not published yet — visitors do not see this.')).'</span>');
        }
        $dirty = $publisher->hasUnpublishedChanges($this->subject());

        return new HtmlString(($dirty
            ? '<span style="color:#d97706">● '.e(__('Saved changes are waiting to be published.')).'</span>'
            : '<span style="color:#16a34a">● '.e(__('The website shows this version.')).'</span>')
            .' <span style="opacity:.7">· '.e(__('Live version :v, published :time', ['v' => $live->version, 'time' => $live->created_at->locale(app()->getLocale())->diffForHumans()])).'</span>');
    }
}
