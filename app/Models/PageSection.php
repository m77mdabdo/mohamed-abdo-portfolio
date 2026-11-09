<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property array<string, mixed> $data
 */
class PageSection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_visible' => 'boolean', 'schema_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $section) {
            $section->uid ??= (string) Str::ulid();
        });
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
