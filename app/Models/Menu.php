<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    public const KEYS = ['header', 'drawer', 'footer'];

    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
