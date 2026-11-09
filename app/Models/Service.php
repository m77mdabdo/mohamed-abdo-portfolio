<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared list row (published as one "collection:services" subject).
 *
 * @property array<string, string> $title
 * @property array<string, string> $description
 */
class Service extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'is_visible' => 'boolean'];
    }

    /** Stable order: sort_order, then id as tie-breaker. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
