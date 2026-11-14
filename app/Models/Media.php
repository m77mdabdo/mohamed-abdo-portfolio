<?php

namespace App\Models;

use App\Support\Tr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded file. Images get optimised WebP/AVIF copies in the background (variants);
 * the original stays as the fallback. Files live on the public disk under random names.
 *
 * @property array<string, string>|null $alt
 * @property array<int, array{format: string, width: int, path: string}>|null $variants
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['alt' => 'array', 'variants' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    public function url(): string
    {
        return self::publicUrl($this->disk, $this->path);
    }

    /**
     * Address of a stored file. Public files use the current site address (whatever port or domain the site
     * is opened on locally; APP_URL in production, where links are pinned), so images never break when the
     * local server runs on a different port than APP_URL.
     */
    public static function publicUrl(string $disk, string $path): string
    {
        return $disk === 'public' ? url('storage/'.ltrim($path, '/')) : Storage::disk($disk)->url($path);
    }

    public function altText(?string $locale = null): string
    {
        return Tr::pick($this->alt, $locale);
    }

    /** @return array<int, array{format: string, width: int, path: string}> */
    public function variantList(string $format): array
    {
        return collect($this->variants ?? [])
            ->where('format', $format)
            ->sortBy('width')
            ->values()
            ->all();
    }

    /** "url 640w, url 1280w" for a <source> element, or null when no copies exist yet. */
    public function srcset(string $format): ?string
    {
        $list = $this->variantList($format);
        if ($list === []) {
            return null;
        }

        return collect($list)->map(fn ($v) => self::publicUrl($this->disk, $v['path']).' '.$v['width'].'w')->implode(', ');
    }

    public function humanSize(): string
    {
        $kb = $this->size / 1024;

        return $kb > 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb).' KB';
    }
}
