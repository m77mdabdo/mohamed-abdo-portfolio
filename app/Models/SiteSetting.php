<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One settings group (identity, seo, appearance, footer, contact, cv, ui) — working copy. */
class SiteSetting extends Model
{
    protected $guarded = ['id', 'lock_version'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
