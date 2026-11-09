<?php

namespace App\Content;

use App\Content\Contracts\ContentReader;
use App\Content\Fields\FieldSchema;
use App\Content\Settings\SettingsGroups;
use App\Content\Settings\UiStrings;
use App\Models\Media;
use App\Support\Tr;

/**
 * Everything the public site reads, in one place. It does not know whether it serves live content or a
 * preview: that is decided by the ContentReader bound for the request (PublishedReader or DraftReader).
 */
final class Site
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(
        private readonly ContentReader $reader,
        private readonly MediaResolver $mediaResolver,
    ) {}

    public function isPreview(): bool
    {
        return $this->reader->isPreview();
    }

    public function settings(string $group): array
    {
        return $this->memo['settings:'.$group] ??= array_replace(
            FieldSchema::defaults(SettingsGroups::fields($group)),
            $this->reader->get('settings:'.$group) ?? [],
        );
    }

    public function ui(string $key, array $replace = [], ?string $locale = null): string
    {
        $overrides = $this->memo['ui'] ??= ($this->reader->get('settings:ui') ?? []);
        $text = UiStrings::resolve($key, $locale ?? app()->getLocale(), $overrides);
        foreach ($replace as $name => $value) {
            $text = str_replace(':'.$name, (string) $value, $text);
        }

        return $text;
    }

    // ---------------------------------------------------------------- pages

    /** @return array<int, array> visible pages keyed by id */
    public function pages(): array
    {
        return $this->memo['pages'] ??= collect($this->reader->all('page'))
            ->filter(fn ($p) => $p['visible'] ?? true)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->keyBy('id')
            ->all();
    }

    public function page(string $key): ?array
    {
        return collect($this->pages())->firstWhere('key', $key);
    }

    public function pageBySlug(string $slug): ?array
    {
        return collect($this->pages())->firstWhere('slug', $slug);
    }

    public function pageById(int $id): ?array
    {
        return $this->pages()[$id] ?? null;
    }

    public function pageUrl(array $page, string $locale): string
    {
        return ($page['key'] ?? null) === 'home'
            ? route('home', ['locale' => $locale])
            : route('page', ['locale' => $locale, 'slug' => $page['slug']]);
    }

    // ------------------------------------------------------------- projects

    /** @return array<int, array> visible projects in display order (sort_order, then id) */
    public function projects(): array
    {
        if (! isset($this->memo['projects'])) {
            $projects = collect($this->reader->all('project'))
                ->filter(fn ($p) => $p['is_visible'] ?? true)
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->values();
            $this->mediaResolver->preload($projects->pluck('cover_media_id'));
            $this->memo['projects'] = $projects->all();
        }

        return $this->memo['projects'];
    }

    public function project(string $slug): ?array
    {
        return collect($this->projects())->firstWhere('slug', $slug);
    }

    public function projectById(int $id): ?array
    {
        return collect($this->projects())->firstWhere('id', $id);
    }

    /** The project shown after $project: the chosen one, or the next in order (wrapping around). */
    public function nextProject(array $project): ?array
    {
        $all = $this->projects();
        if (count($all) < 2) {
            return null;
        }
        if (! empty($project['next_project_id']) && ($chosen = $this->projectById((int) $project['next_project_id'])) && $chosen['id'] !== $project['id']) {
            return $chosen;
        }
        $index = collect($all)->search(fn ($p) => $p['id'] === $project['id']);

        return $all[((int) $index + 1) % count($all)];
    }

    /** @return array<int, array{slug: string, name: array}> categories used by at least one visible project */
    public function categoriesInUse(array $projects): array
    {
        $used = collect($projects)->flatMap(fn ($p) => collect($p['categories'] ?? [])->pluck('slug'))->unique()->all();

        return collect($this->collection('categories'))
            ->filter(fn ($c) => in_array($c['slug'], $used, true))
            ->values()->all();
    }

    // ---------------------------------------------------------- shared lists

    /** @return array<int, array> visible rows of a shared list, in order */
    public function collection(string $type): array
    {
        return $this->memo['collection:'.$type] ??= collect($this->reader->get('collection:'.$type) ?? [])
            ->filter(fn ($row) => $row['is_visible'] ?? true)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values()->all();
    }

    /** @return array<int, array> */
    public function cubeTechnologies(): array
    {
        return array_values(array_filter($this->collection('technologies'), fn ($t) => $t['in_cube'] ?? false));
    }

    /** @return array<int, array<int, array>> rows 1-3 of the moving skills band */
    public function bandRows(): array
    {
        return collect($this->collection('technologies'))
            ->filter(fn ($t) => in_array($t['band_row'] ?? null, [1, 2, 3], true))
            ->groupBy('band_row')
            ->sortKeys()
            ->map(fn ($row) => $row->values()->all())
            ->values()->all();
    }

    // ---------------------------------------------------------------- menus

    /** @return array<int, array{label: string, href: string, new_tab: bool, external: bool, children: array}> */
    public function menu(string $key, string $locale): array
    {
        $tree = $this->reader->get('menu:'.$key)['items'] ?? [];
        $links = new LinkResolver($this);

        $build = function (array $items) use (&$build, $links, $locale): array {
            $out = [];
            foreach ($items as $item) {
                if (! ($item['visible'] ?? true)) {
                    continue;
                }
                $link = $links->resolve($item['link'] ?? [], $locale);
                $label = Tr::pick($item['label'] ?? null, $locale);
                if ($link === null || $label === '') {
                    continue;
                }
                $out[] = ['label' => $label, 'children' => $build($item['children'] ?? [])] + $link;
            }

            return $out;
        };

        return $build($tree);
    }

    // ---------------------------------------------------------------- media

    public function media(mixed $id): ?Media
    {
        return $this->mediaResolver->find($id);
    }

    public function mediaResolver(): MediaResolver
    {
        return $this->mediaResolver;
    }

    /** The uploaded CV, if there is a valid PDF. */
    public function cvFile(): ?Media
    {
        $file = $this->media($this->settings('cv')['file'] ?? null);

        return $file && $file->kind === 'document' && $file->mime === 'application/pdf' ? $file : null;
    }

    public function links(): LinkResolver
    {
        return $this->memo['links'] ??= new LinkResolver($this);
    }
}
