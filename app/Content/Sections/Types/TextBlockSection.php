<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionType;

/** Title and paragraphs (plain text, blank line = new paragraph). */
class TextBlockSection extends SectionType
{
    public function key(): string
    {
        return 'text_block';
    }

    public function label(): string
    {
        return __('Text block');
    }

    public function description(): string
    {
        return __('Title and paragraphs. Leave an empty line between paragraphs.');
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function presets(): array
    {
        return ['dark' => __('Dark'), 'paper' => __('Paper (light)'), 'brick' => __('Brick')];
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable(),
            Field::textarea('body', 'Text')->translatable()->max(6000),
        ];
    }
}
