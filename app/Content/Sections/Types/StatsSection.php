<?php

namespace App\Content\Sections\Types;

use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Figures (Lists › Figures). Add only figures you can stand behind. */
class StatsSection extends SectionType
{
    public function key(): string
    {
        return 'stats';
    }

    public function label(): string
    {
        return __('Figures');
    }

    public function description(): string
    {
        return __('Large numbers from Lists › Figures.');
    }

    public function icon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public function fields(): array
    {
        return [$this->eyebrow()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['stats' => $context->site->collection('stats')];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['stats'] !== [];
    }
}
