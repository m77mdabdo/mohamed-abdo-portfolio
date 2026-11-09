<?php

namespace App\Content;

use App\Content\Contracts\ContentSubject;
use App\Content\Settings\SettingsGroups;
use App\Content\Subjects\CollectionSubject;
use App\Content\Subjects\MenuSubject;
use App\Content\Subjects\PageSubject;
use App\Content\Subjects\ProjectSubject;
use App\Content\Subjects\SettingsSubject;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Project;

/** Resolves subject keys ("page:3") to handlers and lists everything that can be published. */
final class SubjectRegistry
{
    public function resolve(string $key): ?ContentSubject
    {
        [$prefix, $id] = array_pad(explode(':', $key, 2), 2, '');

        return match ($prefix) {
            'page' => ($page = Page::query()->find((int) $id)) ? new PageSubject($page) : null,
            'project' => ($project = Project::query()->find((int) $id)) ? new ProjectSubject($project) : null,
            'settings' => in_array($id, SettingsGroups::keys(), true) ? new SettingsSubject($id) : null,
            'menu' => ($menu = Menu::query()->where('key', $id)->first()) ? new MenuSubject($menu) : null,
            'collection' => Collections::exists($id) ? new CollectionSubject($id) : null,
            default => null,
        };
    }

    /** @return array<int, ContentSubject> */
    public function forPrefix(string $prefix): array
    {
        return match ($prefix) {
            'page' => Page::query()->ordered()->get()->map(fn (Page $p) => new PageSubject($p))->all(),
            'project' => Project::query()->ordered()->get()->map(fn (Project $p) => new ProjectSubject($p))->all(),
            'settings' => array_map(fn ($g) => new SettingsSubject($g), SettingsGroups::keys()),
            'menu' => Menu::query()->orderBy('id')->get()->map(fn (Menu $m) => new MenuSubject($m))->all(),
            'collection' => array_map(fn ($t) => new CollectionSubject($t), Collections::types()),
            default => [],
        };
    }

    /** @return array<int, ContentSubject> */
    public function all(): array
    {
        return array_merge(...array_map(fn ($p) => $this->forPrefix($p), ['page', 'project', 'collection', 'menu', 'settings']));
    }
}
