<?php

namespace App\Content\Media;

use App\Jobs\OptimizeImage;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The only way files enter the site. Checks the real file type (not the extension), refuses SVG, HTML,
 * scripts and anything executable, stores under a random name and queues image optimisation.
 */
final class MediaLibrary
{
    /** Allowed real MIME types → stored extension. */
    public const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];

    public const DOCUMENT_TYPES = ['application/pdf' => 'pdf'];

    public const MAX_IMAGE_KB = 8192;

    public const MAX_DOCUMENT_KB = 10240;

    public function store(UploadedFile $file, string $kind = 'image', ?User $by = null, array $alt = []): Media
    {
        return $this->storePath($file->getRealPath(), $file->getClientOriginalName(), $kind, $by, $alt);
    }

    /** Stores a local file (uploads, imports). $sourceKey makes repeated imports return the same record. */
    public function storePath(string $path, string $originalName, string $kind = 'image', ?User $by = null, array $alt = [], ?string $sourceKey = null): Media
    {
        if ($sourceKey && ($existing = Media::query()->where('source_key', $sourceKey)->first())) {
            return $existing;
        }

        $mime = self::detectMime($path);
        $allowed = $kind === 'document' ? self::DOCUMENT_TYPES : self::IMAGE_TYPES;
        if (! isset($allowed[$mime])) {
            throw new InvalidArgumentException(__('This file type is not allowed.'));
        }
        $maxKb = $kind === 'document' ? self::MAX_DOCUMENT_KB : self::MAX_IMAGE_KB;
        if (filesize($path) > $maxKb * 1024) {
            throw new InvalidArgumentException(__('The file is too large (maximum :size MB).', ['size' => round($maxKb / 1024)]));
        }
        if ($kind === 'document' && ! self::looksLikePdf($path)) {
            throw new InvalidArgumentException(__('The file is not a valid PDF.'));
        }

        $width = $height = null;
        if ($kind === 'image') {
            $info = @getimagesize($path);
            if (! $info) {
                throw new InvalidArgumentException(__('The image could not be read.'));
            }
            [$width, $height] = $info;
        }

        $stored = sprintf('media/%s/%s.%s', now()->format('Y/m'), Str::lower((string) Str::ulid()), $allowed[$mime]);
        $stream = fopen($path, 'rb');
        Storage::disk('public')->put($stored, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $stored,
            'kind' => $kind,
            'original_name' => mb_substr(basename($originalName), 0, 200),
            'mime' => $mime,
            'size' => filesize($path),
            'width' => $width,
            'height' => $height,
            'sha1' => sha1_file($path),
            'alt' => ['en' => (string) ($alt['en'] ?? ''), 'ar' => (string) ($alt['ar'] ?? '')],
            'status' => $kind === 'image' ? 'processing' : 'ready',
            'source_key' => $sourceKey,
            'uploaded_by' => $by?->id,
        ]);

        if ($kind === 'image') {
            // After the response (no background worker needed); a running queue worker is not required.
            OptimizeImage::dispatchAfterResponse($media->id);
        }

        return $media;
    }

    public static function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) $finfo->file($path);
    }

    private static function looksLikePdf(string $path): bool
    {
        $handle = fopen($path, 'rb');
        $head = $handle ? (string) fread($handle, 1024) : '';
        if ($handle) {
            fclose($handle);
        }

        // A PDF starts with %PDF-; refuse files that embed HTML or script tags near the start.
        return str_starts_with($head, '%PDF-') && ! preg_match('/<\s*(script|html|svg)/i', $head);
    }

    /** Removes the file and its optimised copies. Callers must check usage first (MediaUsage). */
    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete($media->path);
        foreach ($media->variants ?? [] as $variant) {
            $disk->delete($variant['path']);
        }
        $media->delete();
    }
}
