<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionType;

/** Image beside text, with optional buttons. */
class ImageTextSection extends SectionType
{
    public function key(): string
    {
        return 'image_text';
    }

    public function label(): string
    {
        return __('Image + text');
    }

    public function description(): string
    {
        return __('An image beside a title, text and buttons.');
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function presets(): array
    {
        return ['dark' => __('Dark'), 'paper' => __('Paper (light)')];
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable(),
            Field::textarea('body', 'Text')->translatable()->max(3000),
            Field::media('image', 'Image'),
            Field::select('image_side', 'Image position', ['start' => 'Before the text', 'end' => 'After the text'], 'end'),
            Field::buttons('buttons', 'Buttons', 2),
        ];
    }
}
