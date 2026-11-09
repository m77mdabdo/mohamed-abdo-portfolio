<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionType;

/** Call to action band. */
class CtaBannerSection extends SectionType
{
    public function key(): string
    {
        return 'cta_banner';
    }

    public function label(): string
    {
        return __('Call to action');
    }

    public function description(): string
    {
        return __('A bold band with a title and buttons.');
    }

    public function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public function presets(): array
    {
        return ['brick' => __('Brick'), 'dark' => __('Dark'), 'paper' => __('Paper (light)')];
    }

    public function fields(): array
    {
        return [
            Field::title('title', 'Title')->translatable()->required(),
            Field::textarea('text', 'Text')->translatable()->max(400),
            Field::buttons('buttons', 'Buttons', 2),
        ];
    }
}
