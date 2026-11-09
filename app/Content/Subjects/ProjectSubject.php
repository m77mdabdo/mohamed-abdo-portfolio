<?php

namespace App\Content\Subjects;

use App\Content\Contracts\ContentSubject;
use App\Models\Category;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectMedia;
use App\Models\ProjectTechnology;
use App\Models\Technology;
use App\Support\Tr;

final class ProjectSubject implements ContentSubject
{
    private const FIELDS = ['slug', 'title', 'kind', 'status_label', 'status_tone', 'year', 'color', 'summary', 'brief', 'built',
        'features', 'client', 'role', 'languages', 'live_url', 'repo_url', 'cover_media_id', 'next_project_id', 'is_featured',
        'is_visible', 'sort_order', 'seo'];

    public function __construct(private readonly Project $project) {}

    public function key(): string
    {
        return $this->project->subjectKey();
    }

    public function label(): string
    {
        return __('Project').': '.$this->project->tr('title', app()->getLocale());
    }

    public function draft(): array
    {
        $p = $this->project->loadMissing(['gallery', 'categories', 'stack.technology']);
        $out = ['id' => $p->id];
        foreach (self::FIELDS as $field) {
            $out[$field] = $p->getAttribute($field);
        }
        foreach (['title', 'kind', 'status_label', 'summary', 'brief', 'built', 'client', 'role', 'languages'] as $field) {
            $out[$field] = Tr::normalize($out[$field]);
        }
        $out['features'] = array_values(array_filter(array_map([Tr::class, 'normalize'], $p->features ?? [])));
        $seo = array_filter(['title' => Tr::normalize($p->seo['title'] ?? null), 'description' => Tr::normalize($p->seo['description'] ?? null)]);
        $out['seo'] = $seo;
        foreach (['year', 'color', 'live_url', 'repo_url'] as $field) {
            $out[$field] = ($out[$field] ?? '') === '' ? null : (string) $out[$field];
        }
        $out['categories'] = $p->categories->map(fn (Category $c) => ['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name])->values()->all();
        $out['stack'] = $p->stack->filter(fn (ProjectTechnology $row) => $row->technology !== null)->map(fn ($row) => [
            'id' => $row->technology->id,
            'name' => $row->technology->name,
            'icon' => $row->technology->icon,
            'color' => $row->technology->color,
        ])->values()->all();
        $out['gallery'] = $p->gallery->map(fn (ProjectMedia $m) => [
            'media_id' => $m->media_id,
            'kind' => $m->kind,
            'caption' => Tr::normalize($m->caption),
        ])->values()->all();

        return $out;
    }

    public function restore(array $snapshot): void
    {
        $p = $this->project;
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $snapshot)) {
                $p->setAttribute($field, $snapshot[$field]);
            }
        }
        if (Project::query()->where('slug', $p->slug)->whereKeyNot($p->id)->exists()) {
            $p->slug = $p->getOriginal('slug');
        }
        if ($p->cover_media_id && ! Media::query()->whereKey($p->cover_media_id)->exists()) {
            $p->cover_media_id = null;
        }
        if ($p->next_project_id && ! Project::query()->whereKey($p->next_project_id)->exists()) {
            $p->next_project_id = null;
        }
        $p->save();

        $p->categories()->sync(Category::query()->whereIn('id', collect($snapshot['categories'] ?? [])->pluck('id'))->pluck('id'));

        $p->stack()->getQuery()->delete();
        $existingTech = Technology::query()->whereIn('id', collect($snapshot['stack'] ?? [])->pluck('id'))->pluck('id')->all();
        foreach (array_values($snapshot['stack'] ?? []) as $i => $tech) {
            if (in_array($tech['id'], $existingTech, true)) {
                $p->stack()->create(['technology_id' => $tech['id'], 'sort_order' => $i + 1]);
            }
        }

        $p->gallery()->getQuery()->delete();
        $existingMedia = Media::query()->whereIn('id', collect($snapshot['gallery'] ?? [])->pluck('media_id'))->pluck('id')->all();
        foreach (array_values($snapshot['gallery'] ?? []) as $i => $shot) {
            if (in_array($shot['media_id'], $existingMedia, true)) {
                $p->gallery()->create(['media_id' => $shot['media_id'], 'kind' => $shot['kind'], 'caption' => $shot['caption'] ?? null, 'sort_order' => $i + 1]);
            }
        }
        $p->unsetRelations();
    }
}
