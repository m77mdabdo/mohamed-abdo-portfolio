<?php

namespace App\Content\Menus;

use App\Content\Fields\FieldSchema;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Menus are a tree of limited depth (top level + one level of children). Saving rebuilds the tree in one
 * transaction from nested arrays, so an item can never become its own ancestor; wouldCreateCycle() guards
 * any code path that moves an existing item under another one.
 */
final class MenuTree
{
    public const MAX_DEPTH = 2;

    /** @return array<int, array<string, mixed>> */
    public static function fromModel(Menu $menu): array
    {
        $items = $menu->items()->get();
        $byParent = $items->groupBy(fn (MenuItem $i) => $i->parent_id ?? 0);

        $build = function (int $parentId, int $depth) use (&$build, $byParent): array {
            if ($depth > self::MAX_DEPTH) {
                return [];
            }

            return ($byParent->get($parentId) ?? collect())->map(fn (MenuItem $i) => [
                'label' => $i->label,
                'link' => FieldSchema::cleanLink([
                    'type' => $i->link_type, 'page' => $i->page_id, 'anchor' => $i->section_anchor,
                    'project' => $i->project_id, 'url' => $i->url, 'new_tab' => $i->new_tab,
                ]),
                'visible' => (bool) $i->is_visible,
                'children' => $build($i->id, $depth + 1),
            ])->values()->all();
        };

        return $build(0, 1);
    }

    /** @param array<int, array<string, mixed>> $items */
    public static function save(Menu $menu, array $items): void
    {
        self::assertDepth($items);

        DB::transaction(function () use ($menu, $items) {
            $menu->items()->getQuery()->delete();
            self::insert($menu, $items, null);
        });
    }

    /** @param array<int, array<string, mixed>> $items */
    public static function assertDepth(array $items, int $depth = 1): void
    {
        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            if ($children !== [] && $depth >= self::MAX_DEPTH) {
                throw new InvalidArgumentException(__('Menus can have only one level of sub-items.'));
            }
            self::assertDepth($children, $depth + 1);
        }
    }

    /** True if putting $item under $newParentId would make it its own ancestor. */
    public static function wouldCreateCycle(MenuItem $item, ?int $newParentId): bool
    {
        $seen = [];
        $current = $newParentId;
        while ($current !== null) {
            if ($current === $item->id || isset($seen[$current])) {
                return true;
            }
            $seen[$current] = true;
            $current = MenuItem::query()->whereKey($current)->value('parent_id');
        }

        return false;
    }

    private static function insert(Menu $menu, array $items, ?int $parentId): void
    {
        foreach (array_values($items) as $i => $item) {
            $link = FieldSchema::cleanLink(is_array($item['link'] ?? null) ? $item['link'] : []);
            // Targets deleted since an old snapshot was taken are dropped instead of breaking the restore.
            if ($link['page'] && ! Page::query()->whereKey($link['page'])->exists()) {
                $link['page'] = null;
            }
            if ($link['project'] && ! Project::query()->whereKey($link['project'])->exists()) {
                $link['project'] = null;
            }
            $label = is_array($item['label'] ?? null) ? $item['label'] : [];
            $row = $menu->items()->create([
                'parent_id' => $parentId,
                'label' => ['en' => mb_substr(trim((string) ($label['en'] ?? '')), 0, 60), 'ar' => mb_substr(trim((string) ($label['ar'] ?? '')), 0, 60)],
                'link_type' => $link['type'],
                'page_id' => $link['page'],
                'section_anchor' => $link['anchor'] ?: null,
                'project_id' => $link['project'],
                'url' => $link['url'] ?: null,
                'new_tab' => $link['new_tab'],
                'is_visible' => (bool) ($item['visible'] ?? true),
                'sort_order' => $i + 1,
            ]);
            self::insert($menu, $item['children'] ?? [], $row->id);
        }
    }
}
