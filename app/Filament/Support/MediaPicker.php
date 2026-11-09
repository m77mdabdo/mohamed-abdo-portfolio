<?php

namespace App\Filament\Support;

use App\Content\Media\MediaLibrary;
use App\Models\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Choose an image (or PDF) from the media library, with thumbnails and search, or upload a new one right here.
 * Uploads go through MediaLibrary (real type check, size limit, random name, background optimisation).
 */
final class MediaPicker
{
    public static function make(string $name, string $label, string $kind = 'image'): Select
    {
        return Select::make($name)
            ->label(__($label))
            ->searchable()
            ->allowHtml()
            ->native(false)
            ->placeholder($kind === 'document' ? __('No file') : __('No image'))
            ->getSearchResultsUsing(fn (string $search) => self::options(
                Media::query()->where('kind', $kind)
                    ->where(fn ($q) => $q->where('original_name', 'like', "%{$search}%")->orWhere('alt', 'like', "%{$search}%"))
                    ->latest('id')->limit(30)->get()
            ))
            ->options(fn () => self::options(Media::query()->where('kind', $kind)->latest('id')->limit(40)->get()))
            ->getOptionLabelUsing(fn ($value) => ($m = Media::query()->find($value)) ? self::label($m) : null)
            ->createOptionForm([
                FileUpload::make('file')->label(__('File'))
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes($kind === 'document' ? array_keys(MediaLibrary::DOCUMENT_TYPES) : array_keys(MediaLibrary::IMAGE_TYPES))
                    ->maxSize($kind === 'document' ? MediaLibrary::MAX_DOCUMENT_KB : MediaLibrary::MAX_IMAGE_KB)
                    ->helperText($kind === 'document' ? __('PDF only, up to 10 MB.') : __('JPG, PNG, WebP or AVIF, up to 8 MB. SVG is not accepted.')),
                Grid::make(2)->schema([
                    TextInput::make('alt_ar')->label(__('Description (Arabic)'))->maxLength(200),
                    TextInput::make('alt_en')->label(__('Description (English)'))->maxLength(200),
                ])->visible($kind === 'image'),
            ])
            ->createOptionUsing(function (array $data) use ($kind) {
                /** @var TemporaryUploadedFile|UploadedFile $file */
                $file = $data['file'];
                try {
                    return app(MediaLibrary::class)->store($file, $kind, auth()->user(), ['en' => $data['alt_en'] ?? '', 'ar' => $data['alt_ar'] ?? ''])->id;
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['file' => $e->getMessage()]);
                }
            })
            ->createOptionModalHeading($kind === 'document' ? __('Upload a PDF') : __('Upload an image'));
    }

    /** @param iterable<Media> $items @return array<int, string> */
    private static function options(iterable $items): array
    {
        $out = [];
        foreach ($items as $m) {
            $out[$m->id] = self::label($m);
        }

        return $out;
    }

    public static function label(Media $m): string
    {
        $name = e(mb_strimwidth($m->altText() ?: $m->original_name, 0, 60, '…'));
        if (! $m->isImage()) {
            return '📄 '.$name.' <small style="opacity:.6">('.$m->humanSize().')</small>';
        }

        return '<span style="display:inline-flex;align-items:center;gap:.6rem"><img src="'.e($m->url()).'" alt="" style="width:44px;height:30px;object-fit:cover;border-radius:4px;background:#222" loading="lazy">'
            .$name.' <small style="opacity:.6">'.(int) $m->width.'×'.(int) $m->height.'</small></span>';
    }
}
