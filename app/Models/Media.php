<?php

namespace App\Models;

use App\Support\Tr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded file. Images get optimised WebP/AVIF copies in the background (variants);
 * the original stays as the fallback. Files live on the public disk under random names.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['alt' => 'array', 'variants' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

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
        return Storage::disk($this->disk)->url($this->path);
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

        $disk = Storage::disk($this->disk);

        return collect($list)->map(fn ($v) => $disk->url($v['path']).' '.$v['width'].'w')->implode(', ');
    }

    public function humanSize(): string
    {
        $kb = $this->size / 1024;

        return $kb > 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb).' KB';
    }
}
