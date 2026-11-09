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

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Technology, $this> */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}
