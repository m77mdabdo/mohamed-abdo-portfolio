<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A page of the site and its ordered sections (the working copy). System pages (key set) are the
 * pages of the original design; owners can add their own pages built from the same section library.
 *
 * @property array<string, string> $title
 * @property array<string, mixed>|null $seo
 */
class Page extends Model
{
    use HasTranslatableFields;

    public const SYSTEM = ['home', 'work', 'about', 'services', 'cv', 'contact'];

    /** Slugs that would clash with fixed routes. */
    public const RESERVED_SLUGS = ['projects', 'preview', 'admin', 'contact-message', 'cv-download', 'storage', 'build', 'sitemap', 'robots', 'up', 'livewire', 'filament', 'media'];

    protected $guarded = ['id', 'lock_version'];

    protected function casts(): array
    {
        return ['title' => 'array', 'seo' => 'array', 'is_visible' => 'boolean'];
    }

    /** @return HasMany<PageSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isSystem(): bool
    {
        return $this->key !== null;
    }

    public function isHome(): bool
    {
        return $this->key === 'home';
    }

    public function subjectKey(): string
    {
        return 'page:'.$this->id;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
