<?php

namespace App\Content\Import;

use App\Content\Collections;
use App\Content\Fields\FieldSchema;
use App\Content\Media\MediaLibrary;
use App\Content\Menus\MenuTree;
use App\Content\Publisher;
use App\Content\Sections\SectionRegistry;
use App\Content\Settings\SettingsGroups;
use App\Content\SubjectRegistry;
use App\Models\Category;
use App\Models\ContentRevision;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Technology;
use App\Support\Icons;
use Illuminate\Support\Facades\DB;

/**
 * Imports the original design's content and images. Safe to run again at any time:
 * - everything is matched by a stable key (page key, project slug, settings group, media source key);
 * - something that already exists is left untouched, so the owner's edits are never overwritten;
 * - shared lists are filled only while empty;
 * - only subjects that were never published get a first published version.
 */
final class ReferenceImporter
{
    private array $data;

    private string $assets;

    /** @var array<string, int> */
    private array $stats = [];

    public function __construct(
        private readonly MediaLibrary $media,
        private readonly SectionRegistry $sections,
        private readonly Publisher $publisher,
        private readonly SubjectRegistry $subjects,
    ) {
        $this->data = require database_path('seeders/data/reference.php');
        $this->assets = database_path('seeders/assets');
    }

    /** @return array<string, int> counts of what was created this run */
    public function run(): array
    {
        $this->stats = [];
        DB::transaction(function () {
            $portrait = $this->image('me/portrait-cut.webp', ['en' => 'Mohamed Abdo', 'ar' => 'محمد عبده']);
            $this->settings($portrait);
            $this->collections();
            $technologies = $this->technologies();
            $categories = $this->categories();
            $this->projects($technologies, $categories);
            $pages = $this->pages($portrait);
            $this->menus($pages);
        });
        $this->publishNeverPublished();

        return $this->stats;
    }

    private function count(string $what): void
    {
        $this->stats[$what] = ($this->stats[$what] ?? 0) + 1;
    }

    private function image(string $relative, array $alt = []): ?int
    {
        $path = $this->assets.'/'.$relative;
        if (! is_file($path)) {
            return null;
        }
        $key = 'reference:'.$relative;
        $existing = Media::query()->where('source_key', $key)->value('id');
        if ($existing) {
            return $existing;
        }
        $this->count('media');

        return $this->media->storePath($path, basename($relative), 'image', null, $alt, $key)->id;
    }

    private function settings(?int $portrait): void
    {
        $values = $this->data;
        $values['identity']['portrait'] = $portrait;
        foreach (SettingsGroups::keys() as $group) {
            if (SiteSetting::query()->where('group', $group)->exists()) {
                continue;
            }
            $data = $group === 'ui' ? [] : FieldSchema::clean(SettingsGroups::fields($group), $values[$group] ?? []);
            SiteSetting::query()->create(['group' => $group, 'data' => $data]);
            $this->count('settings');
        }
    }

    private function collections(): void
    {
        foreach (['services', 'process', 'experience', 'skills', 'languages', 'stats'] as $type) {
            $class = Collections::model($type);
            if ($class::query()->exists()) {
                continue;
            }
            foreach ($this->data[$type] as $i => $row) {
                $class::query()->create($row + ['sort_order' => $i + 1]);
                $this->count($type);
            }
        }
    }

    /** @return array<string, int> name => id */
    private function technologies(): array
    {
        $names = [];
        foreach ($this->data['cube'] as $t) {
            $names[$t['name']] = true;
        }
        foreach ($this->data['band'] as $row) {
            foreach ($row as $n) {
                $names[$n] = true;
            }
        }
        foreach ($this->data['projects'] as $p) {
            foreach ($p['stack'] as $n) {
                $names[$n] = true;
            }
        }

        $cube = collect($this->data['cube'])->keyBy('name');
        $band = [];
        foreach ($this->data['band'] as $r => $row) {
            foreach ($row as $i => $n) {
                $band[$n] = [$r + 1, $i];
            }
        }

        $ids = [];
        $order = 0;
        foreach (array_keys($names) as $name) {
            $order++;
            $tech = Technology::query()->where('name', $name)->first();
            if (! $tech) {
                $icon = Icons::guess($name);
                $tech = Technology::query()->create([
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $icon ? Icons::color($icon) : null,
                    'role' => $cube[$name]['role'] ?? null,
                    'in_cube' => $cube->has($name),
                    'band_row' => $band[$name][0] ?? null,
                    // cube faces first (in their order), then band order, then the rest
                    'sort_order' => $cube->has($name) ? array_search($name, $cube->keys()->all(), true) + 1 : 100 + ($band[$name][1] ?? $order) + 10 * ($band[$name][0] ?? 9),
                ]);
                $this->count('technologies');
            }
            $ids[$name] = $tech->id;
        }

        return $ids;
    }

    /** @return array<string, int> slug => id */
    private function categories(): array
    {
        $ids = [];
        foreach ($this->data['categories'] as $i => $c) {
            $category = Category::query()->firstOrCreate(['slug' => $c['slug']], ['name' => $c['name'], 'sort_order' => $i + 1]);
            if ($category->wasRecentlyCreated) {
                $this->count('categories');
            }
            $ids[$c['slug']] = $category->id;
        }

        return $ids;
    }

    private function projects(array $technologies, array $categories): void
    {
        foreach ($this->data['projects'] as $i => $p) {
            if (Project::query()->where('slug', $p['slug'])->exists()) {
                continue;
            }
            $altCover = ['en' => $p['title']['en'].' — '.$p['kind']['en'], 'ar' => $p['title']['ar'].' — '.$p['kind']['ar']];
            $project = Project::query()->create([
                'slug' => $p['slug'],
                'title' => $p['title'],
                'kind' => $p['kind'],
                'status_label' => $p['status'],
                'status_tone' => $p['status']['en'] === 'Live' ? 'live' : 'neutral',
                'year' => $p['year'],
                'color' => $p['color'],
                'summary' => $p['summary'],
                'brief' => $p['brief'],
                'built' => $p['built'],
                'features' => $p['features'],
                'client' => $p['client'] ?? null,
                'live_url' => $p['live'] ?? null,
                'repo_url' => $p['repo'] ?? null,
                'cover_media_id' => $this->image('work/'.$p['slug'].'.jpg', $altCover),
                'is_featured' => $p['featured'] ?? false,
                'sort_order' => $i + 1,
            ]);
            $this->count('projects');

            $project->categories()->sync(array_values(array_intersect_key($categories, array_flip($p['categories']))));
            foreach ($p['stack'] as $k => $name) {
                $project->stack()->create(['technology_id' => $technologies[$name], 'sort_order' => $k + 1]);
            }
            $order = 0;
            foreach (['d' => 'desktop', 'm' => 'mobile'] as $prefix => $kind) {
                for ($n = 1; $n <= ($p['gallery'][$prefix] ?? 0); $n++) {
                    $alt = $kind === 'desktop'
                        ? ['en' => $p['title']['en'].' — screen '.$n, 'ar' => $p['title']['ar'].' — شاشة '.$n]
                        : ['en' => $p['title']['en'].' — mobile screen '.$n, 'ar' => $p['title']['ar'].' — شاشة الجوال '.$n];
                    if ($id = $this->image("work/{$p['slug']}/{$prefix}{$n}.jpg", $alt)) {
                        $project->gallery()->create(['media_id' => $id, 'kind' => $kind, 'sort_order' => ++$order]);
                    }
                }
            }
        }
    }

    /** @return array<string, Page> key => page */
    private function pages(?int $portrait): array
    {
        $pages = [];
        foreach (ReferencePages::definitions() as $i => $def) {
            $page = Page::query()->where('key', $def['key'])->first();
            if (! $page) {
                $page = Page::query()->create([
                    'key' => $def['key'],
                    'slug' => $def['slug'],
                    'title' => $def['title'],
                    'seo' => $def['seo'] ?? [],
                    'sort_order' => $i + 1,
                    'source_key' => 'reference:page:'.$def['key'],
                ]);
                $this->count('pages');
                $pages[$def['key']] = $page; // needed below for links to other pages
            }
            $pages[$def['key']] = $page;
        }

        // Sections are added only to pages created in this run (an existing page keeps the owner's sections).
        foreach (ReferencePages::definitions() as $def) {
            $page = $pages[$def['key']];
            if (! $page->wasRecentlyCreated) {
                continue;
            }
            foreach ($def['sections'] as $k => $section) {
                $type = $this->sections->get($section['type']);
                $data = $type->clean(array_replace($type->defaults(), ReferencePages::resolveLinks($section['data'] ?? [], $pages)));
                $page->sections()->create([
                    'type' => $section['type'],
                    'preset' => $section['preset'] ?? null,
                    'anchor' => $section['anchor'] ?? $type->defaultAnchor(),
                    'is_visible' => $section['visible'] ?? true,
                    'sort_order' => $k + 1,
                    'data' => $data,
                ]);
                $this->count('sections');
            }
        }

        return $pages;
    }

    private function menus(array $pages): void
    {
        foreach (ReferencePages::menus() as $key => $menu) {
            if (Menu::query()->where('key', $key)->exists()) {
                continue;
            }
            $model = Menu::query()->create(['key' => $key, 'name' => $menu['name']]);
            MenuTree::save($model, ReferencePages::resolveLinks($menu['items'], $pages));
            $this->count('menus');
        }
    }

    private function publishNeverPublished(): void
    {
        $published = ContentRevision::query()->distinct()->pluck('subject')->flip();
        foreach ($this->subjects->all() as $subject) {
            if (! $published->has($subject->key())) {
                $this->publisher->publish($subject, null, 'Initial import from the original design');
                $this->count('published');
            }
        }
    }
}
