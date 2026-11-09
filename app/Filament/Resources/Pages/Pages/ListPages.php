<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Content\Pages\PageDraftWriter;
use App\Content\Sections\SectionRegistry;
use App\Filament\Resources\Pages\PageResource;
use App\Models\ActivityLog;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\DB;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getSubheading(): ?string
    {
        return __('Open a page to edit its sections in the order visitors see them. Drag rows to change the order of pages.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newPage')->label(__('New page'))->icon('heroicon-o-plus')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title_ar')->label(__('Title (Arabic)'))->required()->maxLength(80)->extraInputAttributes(['dir' => 'rtl']),
                        TextInput::make('title_en')->label(__('Title (English)'))->required()->maxLength(80),
                    ]),
                    TextInput::make('slug')->label(__('Address'))->prefix('/ar/')->required()->maxLength(80)
                        ->helperText(__('Latin letters, numbers and dashes. The same address is used in both languages.')),
                    Select::make('starter')->label(__('Start with'))->native(false)->selectablePlaceholder(false)->default('header_text')
                        ->options(['header_text' => __('A title and a text block'), 'blank' => __('An empty page')]),
                ])
                ->action(function (array $data) {
                    $slug = PageDraftWriter::normaliseSlug($data['slug']);
                    PageDraftWriter::assertSlugAvailable($slug);
                    $page = DB::transaction(function () use ($data, $slug) {
                        $page = Page::query()->create([
                            'slug' => $slug,
                            'title' => ['en' => $data['title_en'], 'ar' => $data['title_ar']],
                            'seo' => [],
                            'sort_order' => (int) Page::query()->max('sort_order') + 1,
                        ]);
                        if ($data['starter'] === 'header_text') {
                            $registry = app(SectionRegistry::class);
                            $header = $registry->get('page_header');
                            $page->sections()->create(['type' => 'page_header', 'preset' => 'glow', 'anchor' => 'top', 'sort_order' => 1,
                                'data' => $header->clean(['title' => ['en' => $data['title_en'], 'ar' => $data['title_ar']]])]);
                            $page->sections()->create(['type' => 'text_block', 'preset' => 'dark', 'anchor' => 'text', 'sort_order' => 2,
                                'data' => $registry->get('text_block')->defaults()]);
                        }
                        ActivityLog::record('page_created', __('Page').': '.$data['title_en'], $page->subjectKey());

                        return $page;
                    });

                    $this->redirect(PageResource::getUrl('edit', ['record' => $page]));
                }),
        ];
    }
}
