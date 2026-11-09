<?php

namespace Tests\Feature;

use App\Content\Pages\PageDraftWriter;
use App\Content\Publisher;
use App\Content\Subjects\PageSubject;
use App\Filament\Support\PreviewLink;
use App\Models\ContentRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** 2. Editing the home hero in both languages: drafts stay private, preview shows them, publishing updates the site. */
class PublishingTest extends TestCase
{
    use RefreshDatabase;

    private function editHero(string $ar, string $en): void
    {
        $page = $this->home()->load('sections');
        $sections = $page->sections->map(function ($s) use ($ar, $en) {
            $data = $s->data;
            if ($s->type === 'hero') {
                $data['title'] = ['ar' => $ar, 'en' => $en];
            }

            return ['uid' => $s->uid, 'type' => $s->type, 'preset' => $s->preset, 'anchor' => $s->anchor, 'visible' => $s->is_visible, 'data' => $data];
        })->all();
        app(PageDraftWriter::class)->save($page, [], $sections, $page->lock_version);
    }

    public function test_draft_is_private_until_published_and_cache_follows(): void
    {
        $this->importReference();
        $this->get('/en')->assertOk()->assertSee('elevate brands');

        $this->editHero('عنوان *تجريبي* جديد', 'A *fresh* test title');

        // live site unchanged (and served from cache)
        $this->get('/en')->assertSee('elevate brands')->assertDontSee('fresh');
        $this->get('/ar')->assertDontSee('تجريبي');

        // preview: signed + signed-in staff only, never cached or indexed
        $url = PreviewLink::to('', 'en');
        $this->get($url)->assertRedirect('/admin/login');
        $editor = $this->editor();
        $this->actingAs($editor)->get($url)->assertOk()
            ->assertSee('<mark class="hl">fresh</mark>', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->actingAs($editor)->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
        auth()->logout();
        $this->get('/en')->assertDontSee('fresh'); // the preview never leaks into the live site

        $this->publishPage($this->home());
        $this->get('/en')->assertSee('<mark class="hl">fresh</mark>', false);
        $this->get('/ar')->assertSee('<mark class="hl">تجريبي</mark>', false);
    }

    public function test_saving_an_outdated_copy_is_refused(): void
    {
        $this->importReference();
        $page = $this->home();
        $stale = $page->lock_version;
        app(PageDraftWriter::class)->save($page, [], [], $stale); // first save wins
        $this->expectException(ValidationException::class);
        app(PageDraftWriter::class)->save($page->refresh(), [], [], $stale);
    }

    public function test_publishing_unchanged_content_adds_no_revision(): void
    {
        $this->importReference();
        $subject = new PageSubject($this->home());
        $before = ContentRevision::query()->where('subject', $subject->key())->count();
        app(Publisher::class)->publish($subject);
        $this->assertSame($before, ContentRevision::query()->where('subject', $subject->key())->count());
        $this->assertFalse(app(Publisher::class)->hasUnpublishedChanges($subject));
    }

    public function test_discard_returns_the_draft_to_the_live_version(): void
    {
        $this->importReference();
        $this->editHero('مسودة', 'Draft only');
        $subject = new PageSubject($this->home());
        $this->assertTrue(app(Publisher::class)->hasUnpublishedChanges($subject));
        app(Publisher::class)->discard($subject);
        $this->assertFalse(app(Publisher::class)->hasUnpublishedChanges(new PageSubject($this->home())));
    }
}
