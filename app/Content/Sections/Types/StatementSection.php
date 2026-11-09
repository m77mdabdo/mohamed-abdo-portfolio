<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionType;

/** Three-line statement whose words light up while scrolling. */
class StatementSection extends SectionType
{
    public function key(): string
    {
        return 'statement';
    }

    public function label(): string
    {
        return __('Statement (philosophy)');
    }

    public function description(): string
    {
        return __('Three short lines that light up word by word.');
    }

    public function icon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public function defaultAnchor(): ?string
    {
        return 'intro';
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::text('line1', 'Line 1 (light)')->translatable()->max(120),
            Field::text('line2', 'Line 2 (brick colour)')->translatable()->max(120),
            Field::text('line3', 'Line 3 (turquoise)')->translatable()->max(120),
        ];
    }
}
