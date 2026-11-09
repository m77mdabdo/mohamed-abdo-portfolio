<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared list row (published as one "collection:experiences" subject).
 *
 * @property array<string, string> $period
 * @property array<string, string> $role
 * @property array<string, string> $organization
 * @property array<string, string>|null $description
 */
class Experience extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['period' => 'array', 'role' => 'array', 'organization' => 'array', 'description' => 'array', 'is_visible' => 'boolean', 'is_current' => 'boolean'];
    }

    /** Stable order: sort_order, then id as tie-breaker. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
