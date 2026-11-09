<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ordered link between a project and a technology of its stack. */
class ProjectTechnology extends Model
{
    protected $table = 'project_technology';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}
