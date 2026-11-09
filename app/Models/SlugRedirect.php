<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    protected $guarded = ['id'];

    public static function remember(string $kind, string $oldSlug, int $targetId): void
    {
        static::query()->updateOrCreate(['kind' => $kind, 'old_slug' => $oldSlug], ['target_id' => $targetId]);
    }
}
