<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionType;

/** Large title at the top of an inner page. */
class PageHeaderSection extends SectionType
{
    public function key(): string
    {
        return 'page_header';
    }

    public function label(): string
    {
        return __('Page header');
    }

    public function description(): string
    {
        return __('Big title and introduction at the top of a page.');
    }

    public function icon(): string
    {
        return 'heroicon-o-h1';
    }

    public function presets(): array
    {
        return ['dark' => __('Dark'), 'glow' => __('Dark with light leaks')];
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable()->required(),
            Field::textarea('intro', 'Introduction')->translatable()->max(600),
            Field::buttons('buttons', 'Buttons', 2),
        ];
    }
}
