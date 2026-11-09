<?php

namespace App\Content\Pages;

use App\Content\Sections\SectionRegistry;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SlugRedirect;
use App\Support\Locales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves the dashboard's page editor into the working copy (draft). Never touches the live version.
 * - optimistic locking: a save based on an outdated copy (someone else saved meanwhile) is refused;
 * - section data is cleaned against its type's schema; unknown types are rejected;
 * - order is the list order (sort_order 1..n); anchors are made URL-safe and unique within the page.
 */
final class PageDraftWriter
{
    public function __construct(private readonly SectionRegistry $registry) {}

    /**
     * @param  array{title?: array, slug?: string, seo?: array, is_visible?: bool}  $attributes
     * @param  array<int, array{uid?: ?string, type: string, preset?: ?string, anchor?: ?string, visible?: bool, data?: array}>  $sections
     */
    public function save(Page $page, array $attributes, array $sections, int $expectedLockVersion): Page
    {
        return DB::transaction(function () use ($page, $attributes, $sections, $expectedLockVersion) {
            $bumped = Page::query()->whereKey($page->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
            if ($bumped === 0) {
                throw ValidationException::withMessages(['lock' => __('Someone else saved this page while you were editing. Reload to see their version, then apply your changes again.')]);
            }

            $this->saveAttributes($page, $attributes);
            $this->saveSections($page, $sections);
            ActivityLog::record('draft_saved', __('Page').': '.$page->tr('title'), $page->subjectKey());

            return $page->refresh();
        });
    }

    private function saveAttributes(Page $page, array $attributes): void
    {
        if (isset($attributes['title'])) {
            $page->title = ['en' => trim((string) ($attributes['title']['en'] ?? '')), 'ar' => trim((string) ($attributes['title']['ar'] ?? ''))];
        }
        if (isset($attributes['seo'])) {
            $seo = $attributes['seo'];
            $page->seo = [
                'title' => ['en' => mb_substr(trim((string) ($seo['title']['en'] ?? '')), 0, 70), 'ar' => mb_substr(trim((string) ($seo['title']['ar'] ?? '')), 0, 70)],
                'description' => ['en' => mb_substr(trim((string) ($seo['description']['en'] ?? '')), 0, 170), 'ar' => mb_substr(trim((string) ($seo['description']['ar'] ?? '')), 0, 170)],
                'image' => is_numeric($seo['image'] ?? null) ? (int) $seo['image'] : null,
                'noindex' => (bool) ($seo['noindex'] ?? false),
            ];
        }
        if (array_key_exists('is_visible', $attributes) && ! $page->isHome()) {
            $page->is_visible = (bool) $attributes['is_visible'];
        }
        if (isset($attributes['slug']) && ! $page->isSystem()) {
            $slug = self::normaliseSlug((string) $attributes['slug']);
            self::assertSlugAvailable($slug, $page->id);
            if ($slug !== $page->slug) {
                SlugRedirect::remember('page', $page->slug, $page->id);
                SlugRedirect::query()->where('kind', 'page')->where('old_slug', $slug)->delete();
                $page->slug = $slug;
            }
        }
        $page->save();
    }

    private function saveSections(Page $page, array $sections): void
    {
        $keep = [];
        $anchors = [];
        foreach (array_values($sections) as $i => $input) {
            $type = $this->registry->get((string) ($input['type'] ?? ''));
            if (! $type) {
                throw ValidationException::withMessages(['sections' => __('Unknown section type: :type', ['type' => (string) ($input['type'] ?? '')])]);
            }

            $anchor = Str::slug((string) ($input['anchor'] ?? '')) ?: null;
            if ($anchor !== null) {
                $base = $anchor;
                $n = 2;
                while (in_array($anchor, $anchors, true)) {
                    $anchor = $base.'-'.$n++;
                }
                $anchors[] = $anchor;
            }

            $section = filled($input['uid'] ?? null)
                ? PageSection::query()->where('page_id', $page->id)->where('uid', $input['uid'])->first() ?? new PageSection
                : new PageSection;
            $section->fill([
                'page_id' => $page->id,
                'type' => $type->key(),
                'preset' => array_key_exists((string) ($input['preset'] ?? ''), $type->presets()) ? $input['preset'] : null,
                'anchor' => $anchor,
                'is_visible' => (bool) ($input['visible'] ?? true),
                'sort_order' => $i + 1,
                'schema_version' => 1,
                'data' => $type->clean((array) ($input['data'] ?? [])),
            ])->save();
            $keep[] = $section->id;
        }
        PageSection::query()->where('page_id', $page->id)->whereNotIn('id', $keep)->delete();
    }

    public static function normaliseSlug(string $slug): string
    {
        return Str::slug(Str::ascii($slug)) ?: 'page';
    }

    public static function assertSlugAvailable(string $slug, ?int $ignoreId = null): void
    {
        if (in_array($slug, Page::RESERVED_SLUGS, true) || Locales::isSupported($slug)) {
            throw ValidationException::withMessages(['slug' => __('This address is reserved by the site. Choose another one.')]);
        }
        if (Page::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            throw ValidationException::withMessages(['slug' => __('Another page already uses this address.')]);
        }
    }
}
