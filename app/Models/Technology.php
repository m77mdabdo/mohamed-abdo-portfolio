<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A tool or framework: used by project stacks, the About tech cube and the moving skills band.
 *
 * @property array<string, string>|null $role
 */
class Technology extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['role' => 'array', 'in_cube' => 'boolean', 'band_row' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
