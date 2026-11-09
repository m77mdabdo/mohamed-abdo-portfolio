<?php

namespace App\Content\Subjects;

use App\Content\Contracts\ContentSubject;
use App\Content\Sections\SectionRegistry;
use App\Models\Page;
use App\Models\PageSection;

final class PageSubject implements ContentSubject
{
    public function __construct(private readonly Page $page) {}

    public function key(): string
    {
        return $this->page->subjectKey();
    }

    public function label(): string
    {
        return __('Page').': '.$this->page->tr('title', app()->getLocale());
    }

    public function model(): Page
    {
        return $this->page;
    }

    public function draft(): array
    {
        $registry = app(SectionRegistry::class);
        $sections = $this->page->sections()->get()->map(function (PageSection $s) use ($registry) {
            $type = $registry->get($s->type);

            return [
                'uid' => $s->uid,
                'type' => $s->type,
                'preset' => $s->preset,
                'anchor' => $s->anchor,
                'visible' => (bool) $s->is_visible,
                'schema_version' => $s->schema_version,
                'data' => $type ? $type->clean($s->data ?? []) : ($s->data ?? []),
            ];
        })->values()->all();

        return [
            'id' => $this->page->id,
            'key' => $this->page->key,
            'slug' => $this->page->slug,
            'title' => $this->page->title,
            'seo' => $this->page->seo ?? [],
            'visible' => (bool) $this->page->is_visible,
            'sort_order' => $this->page->sort_order,
            'sections' => $sections,
        ];
    }

    public function restore(array $snapshot): void
    {
        $page = $this->page;
        $page->title = $snapshot['title'] ?? $page->title;
        $page->seo = $snapshot['seo'] ?? $page->seo;
        $page->is_visible = (bool) ($snapshot['visible'] ?? true);
        if (! $page->isSystem() && isset($snapshot['slug']) && ! Page::query()->where('slug', $snapshot['slug'])->whereKeyNot($page->id)->exists()) {
            $page->slug = $snapshot['slug'];
        }
        $page->save();

        $keep = [];
        foreach (array_values($snapshot['sections'] ?? []) as $i => $section) {
            $model = PageSection::query()->firstOrNew(['uid' => $section['uid']]);
            if ($model->exists && $model->page_id !== $page->id) {
                $model = new PageSection; // the uid belongs to another page: restore as a copy
            }
            $model->fill([
                'page_id' => $page->id,
                'type' => $section['type'],
                'preset' => $section['preset'] ?? null,
                'anchor' => $section['anchor'] ?? null,
                'is_visible' => (bool) ($section['visible'] ?? true),
                'schema_version' => $section['schema_version'] ?? 1,
                'data' => $section['data'] ?? [],
                'sort_order' => $i + 1,
            ])->save();
            $keep[] = $model->id;
        }
        $page->sections()->getQuery()->whereNotIn('id', $keep)->delete();
    }
}
