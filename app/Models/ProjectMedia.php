<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One screenshot in a project's desktop or mobile gallery. */
class ProjectMedia extends Model
{
    protected $table = 'project_media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['caption' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
