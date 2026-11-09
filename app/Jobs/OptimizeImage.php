<?php

namespace App\Jobs;

use App\Models\Media;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creates resized WebP (and AVIF when the server supports it) copies of an uploaded image.
 * The original always stays as the fallback, so a failure here never breaks a page: the media item is
 * marked "failed" with the reason and the site keeps serving the original file.
 */
class OptimizeImage implements ShouldQueue
{
    use Queueable;

    public const WIDTHS = [640, 1280, 1920];

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = Media::query()->find($this->mediaId);
        if (! $media || ! $media->isImage()) {
            return;
        }

        try {
            $variants = $this->makeVariants($media);
            $media->forceFill(['variants' => $variants, 'status' => 'ready', 'error' => null])->save();
        } catch (Throwable $e) {
            $media->forceFill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)])->save();
        }
    }

    /** @return array<int, array{format: string, width: int, path: string}> */
    private function makeVariants(Media $media): array
    {
        $disk = Storage::disk($media->disk);
        $source = $this->load($disk->path($media->path), $media->mime);
        $origW = imagesx($source);
        $origH = imagesy($source);

        // Old copies are replaced, never left behind.
        foreach ($media->variants ?? [] as $old) {
            $disk->delete($old['path']);
        }

        $formats = ['webp'];
        if (function_exists('imageavif') && (gd_info()['AVIF Support'] ?? false)) {
            $formats[] = 'avif';
        }

        $widths = array_values(array_filter(self::WIDTHS, fn ($w) => $w < $origW));
        $widths[] = min($origW, 2400); // a copy at (capped) original size too
        $widths = array_values(array_unique($widths));

        $out = [];
        foreach ($widths as $width) {
            $height = (int) round($origH * $width / $origW);
            // imagecopyresampled works on every GD build (imagescale with bicubic is missing on some).
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $origW, $origH);
            foreach ($formats as $format) {
                $path = sprintf('media/variants/%d/%d.%s', $media->id, $width, $format);
                $abs = $disk->path($path);
                if (! is_dir(dirname($abs))) {
                    mkdir(dirname($abs), 0755, true);
                }
                $ok = $format === 'webp' ? imagewebp($resized, $abs, 80) : imageavif($resized, $abs, 52, 8);
                if ($ok) {
                    $out[] = ['format' => $format, 'width' => $width, 'path' => $path];
                }
            }
        }

        return $out;
    }

    private function load(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/avif' => imagecreatefromavif($path),
            default => false,
        };
        if (! $image instanceof GdImage) {
            throw new \RuntimeException('The image could not be decoded.');
        }
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }
}
