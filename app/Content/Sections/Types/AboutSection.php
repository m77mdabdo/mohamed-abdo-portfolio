<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** About text on paper, with the rotating technology cube (Technologies › "Show on the cube"). */
class AboutSection extends SectionType
{
    public function key(): string
    {
        return 'about';
    }

    public function label(): string
    {
        return __('About + technology cube');
    }

    public function description(): string
    {
        return __('Lead sentence, paragraph, optional figures and the 3D cube of tools.');
    }

    public function icon(): string
    {
        return 'heroicon-o-user';
    }

    public function defaultAnchor(): ?string
    {
        return 'about';
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::textarea('lead', 'Lead sentence')->translatable()->max(400),
            Field::textarea('body', 'Paragraph')->translatable()->max(1500),
            Field::toggle('show_cube', 'Show the technology cube'),
            Field::toggle('show_stats', 'Show figures (Lists › Figures)', false),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return [
            'cube' => $section->on('show_cube') ? array_slice($context->site->cubeTechnologies(), 0, 6) : [],
            'stats' => $section->on('show_stats') ? $context->site->collection('stats') : [],
        ];
    }
}
