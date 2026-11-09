<?php

namespace App\Content\Subjects;

use App\Content\Contracts\ContentSubject;
use App\Content\Menus\MenuTree;
use App\Models\Menu;

final class MenuSubject implements ContentSubject
{
    public function __construct(private readonly Menu $menu) {}

    public function key(): string
    {
        return 'menu:'.$this->menu->key;
    }

    public function label(): string
    {
        return __('Menu').': '.__($this->menu->name);
    }

    public function model(): Menu
    {
        return $this->menu;
    }

    public function draft(): array
    {
        return ['key' => $this->menu->key, 'items' => MenuTree::fromModel($this->menu)];
    }

    public function restore(array $snapshot): void
    {
        MenuTree::save($this->menu, $snapshot['items'] ?? []);
    }
}
