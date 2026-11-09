<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared list row (published as one "collection:skill_groups" subject).
 *
 * @property array<string, string> $name
 * @property array<int, string> $items
 */
class SkillGroup extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['name' => 'array', 'items' => 'array', 'is_visible' => 'boolean'];
    }

    /** Stable order: sort_order, then id as tie-breaker. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
