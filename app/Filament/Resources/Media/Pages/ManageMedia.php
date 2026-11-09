<?php

namespace App\Filament\Resources\Media\Pages;

use App\Content\Media\MediaLibrary;
use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use InvalidArgumentException;

class ManageMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    public function getSubheading(): ?string
    {
        return __('Images get WebP/AVIF copies in the background; the original is used until they are ready. SVG and HTML files are not accepted.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')->label(__('Upload files'))->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    Select::make('kind')->label(__('Type'))->native(false)->selectablePlaceholder(false)->default('image')->live()
                        ->options(['image' => __('Images (JPG, PNG, WebP, AVIF — up to 8 MB)'), 'document' => __('PDF documents (up to 10 MB)')]),
                    FileUpload::make('files')->label(__('Files'))->multiple()->required()->storeFiles(false)->maxFiles(20)
                        ->acceptedFileTypes(fn ($get) => $get('kind') === 'document' ? array_keys(MediaLibrary::DOCUMENT_TYPES) : array_keys(MediaLibrary::IMAGE_TYPES))
                        ->maxSize(fn ($get) => $get('kind') === 'document' ? MediaLibrary::MAX_DOCUMENT_KB : MediaLibrary::MAX_IMAGE_KB),
                ])
                ->action(function (array $data) {
                    $ok = 0;
                    $errors = [];
                    foreach ($data['files'] as $file) {
                        try {
                            app(MediaLibrary::class)->store($file, $data['kind'], auth()->user());
                            $ok++;
                        } catch (InvalidArgumentException $e) {
                            $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
                        }
                    }
                    $n = Notification::make()->title(__(':n file(s) uploaded.', ['n' => $ok]));
                    $errors ? $n->warning()->body(implode("\n", $errors))->persistent() : $n->success();
                    $n->send();
                }),
        ];
    }
}
