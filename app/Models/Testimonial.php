<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared list row (published as one "collection:testimonials" subject).
 *
 * @property array<string, string>|null $role
 * @property array<string, string> $quote
 */
class Testimonial extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['role' => 'array', 'quote' => 'array', 'is_visible' => 'boolean', 'has_consent' => 'boolean'];
    }

    /** Stable order: sort_order, then id as tie-breaker. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
