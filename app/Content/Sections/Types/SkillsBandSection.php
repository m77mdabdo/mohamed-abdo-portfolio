<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Three rows of tool tiles drifting in opposite directions (Technologies › "Row in the skills band"). */
class SkillsBandSection extends SectionType
{
    public function key(): string
    {
        return 'skills_band';
    }

    public function label(): string
    {
        return __('Skills band (moving tools)');
    }

    public function description(): string
    {
        return __('Rows of technology tiles that drift while you scroll.');
    }

    public function icon(): string
    {
        return 'heroicon-o-cpu-chip';
    }

    public function defaultAnchor(): ?string
    {
        return 'skills';
    }

    public function fields(): array
    {
        return [$this->eyebrow(), Field::title('title', 'Title')->translatable()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['rows' => $context->site->bandRows()];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['rows'] !== [];
    }
}
