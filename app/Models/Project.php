<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasTranslatableFields;

    protected $guarded = ['id', 'lock_version'];

    protected function casts(): array
    {
        return [
            'title' => 'array', 'kind' => 'array', 'status_label' => 'array', 'summary' => 'array',
            'brief' => 'array', 'built' => 'array', 'features' => 'array', 'client' => 'array',
            'role' => 'array', 'languages' => 'array', 'seo' => 'array',
            'is_featured' => 'boolean', 'is_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Old links keep working after a slug change (301 to the new address).
        static::updating(function (self $project) {
            if ($project->isDirty('slug') && $project->getOriginal('slug')) {
                SlugRedirect::remember('project', $project->getOriginal('slug'), $project->id);
            }
        });
        static::saved(function (self $project) {
            SlugRedirect::query()->where('kind', 'project')->where('old_slug', $project->slug)->delete();
        });
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function nextProject(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_project_id');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function desktopShots(): HasMany
    {
        return $this->gallery()->where('kind', 'desktop');
    }

    public function mobileShots(): HasMany
    {
        return $this->gallery()->where('kind', 'mobile');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->orderBy('categories.sort_order');
    }

    public function stack(): HasMany
    {
        return $this->hasMany(ProjectTechnology::class)->orderBy('sort_order')->orderBy('id');
    }

    public function subjectKey(): string
    {
        return 'project:'.$this->id;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
