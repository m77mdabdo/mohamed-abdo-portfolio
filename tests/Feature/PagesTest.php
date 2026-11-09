<?php

namespace Tests\Feature;

use App\Content\Pages\PageDraftWriter;
use App\Content\Publisher;
use App\Content\Subjects\PageSubject;
use App\Models\ContentRevision;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** 3. Create a page and sections, reorder, hide, publish, restore a revision; addresses stay safe. */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function section(string $type, string $en, string $ar, bool $visible = true, ?string $uid = null): array
    {
        return ['uid' => $uid, 'type' => $type, 'anchor' => $type, 'visible' => $visible,
            'data' => $type === 'text_block' ? ['title' => ['en' => $en, 'ar' => $ar], 'body' => ['en' => "Para one\n\nPara two", 'ar' => 'فقرة']]
                : ['title' => ['en' => $en, 'ar' => $ar]]];
    }

    public function test_full_page_lifecycle(): void
    {
        $this->importReference();
        $page = Page::query()->create(['slug' => 'lab', 'title' => ['en' => 'Lab', 'ar' => 'المختبر'], 'seo' => []]);
        $writer = app(PageDraftWriter::class);
        $publisher = app(Publisher::class);

        $writer->save($page, [], [$this->section('page_header', 'Header EN', 'رأس'), $this->section('text_block', 'Notes EN', 'ملاحظات')], 0);
        $this->get('/en/lab')->assertNotFound(); // never published

        $publisher->publish(new PageSubject($page->refresh()));
        $html = $this->get('/en/lab')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Notes EN'), strpos($html, 'Header EN'));
        $this->get('/ar/lab')->assertOk()->assertSee('ملاحظات')->assertSee('dir="rtl"', false);

        // reorder + hide, publish
        $page->load('sections');
        [$header, $text] = $page->sections->all();
        $writer->save($page, [], [
            $this->section('text_block', 'Notes EN', 'ملاحظات', true, $text->uid),
            $this->section('page_header', 'Header EN', 'رأس', false, $header->uid),
        ], $page->lock_version);
        $publisher->publish(new PageSubject($page->refresh()));
        $this->get('/en/lab')->assertSee('Notes EN')->assertDontSee('Header EN');
        $this->assertSame([$text->uid, $header->uid], $page->sections()->pluck('uid')->all());

        // restore version 1 (header first and visible) and publish it
        $v1 = ContentRevision::query()->where('subject', 'page:'.$page->id)->where('version', 1)->firstOrFail();
        $publisher->restore(new PageSubject($page), $v1, null, true);
        $html = $this->get('/en/lab')->getContent();
        $this->assertLessThan(strpos($html, 'Notes EN'), strpos($html, 'Header EN'));
    }

    public function test_reserved_and_duplicate_addresses_are_refused_and_renames_redirect(): void
    {
        $this->importReference();
        $page = Page::query()->create(['slug' => 'lab', 'title' => ['en' => 'Lab', 'ar' => 'مختبر'], 'seo' => []]);
        foreach (['projects', 'admin', 'en', 'work'] as $slug) {
            try {
                app(PageDraftWriter::class)->save($page->refresh(), ['slug' => $slug], [], $page->lock_version);
                $this->fail("slug {$slug} accepted");
            } catch (ValidationException) {
                $this->assertSame('lab', $page->refresh()->slug);
            }
        }
        app(PageDraftWriter::class)->save($page->refresh(), ['slug' => 'studio'], [$this->section('page_header', 'S', 'س')], $page->lock_version);
        app(Publisher::class)->publish(new PageSubject($page->refresh()));
        $this->get('/en/lab')->assertRedirect('/en/studio')->assertStatus(301);
    }

    public function test_system_page_addresses_cannot_change(): void
    {
        $this->importReference();
        $work = Page::query()->where('key', 'work')->first();
        app(PageDraftWriter::class)->save($work, ['slug' => 'portfolio'], $work->sections->map(fn ($s) => ['uid' => $s->uid, 'type' => $s->type, 'anchor' => $s->anchor, 'preset' => $s->preset, 'visible' => true, 'data' => $s->data])->all(), $work->lock_version);
        $this->assertSame('work', $work->refresh()->slug);
    }

    public function test_unknown_section_types_are_rejected(): void
    {
        $this->importReference();
        $page = Page::query()->create(['slug' => 'x', 'title' => ['en' => 'X', 'ar' => 'X'], 'seo' => []]);
        $this->expectException(ValidationException::class);
        app(PageDraftWriter::class)->save($page, [], [['type' => 'php_eval', 'data' => []]], 0);
    }
}
