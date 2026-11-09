<?php

namespace App\Content\Sections\Types;

use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Numbered working steps (Lists › Process steps). */
class ProcessSection extends SectionType
{
    public function key(): string
    {
        return 'process';
    }

    public function label(): string
    {
        return __('Process (how I work)');
    }

    public function description(): string
    {
        return __('Numbered steps from Lists › Process steps.');
    }

    public function icon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    public function defaultAnchor(): ?string
    {
        return 'process';
    }

    public function fields(): array
    {
        return [$this->eyebrow()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['steps' => $context->site->collection('process')];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['steps'] !== [];
    }
}
