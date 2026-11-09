<?php

namespace App\Content\Sections\Types;

use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Skills grouped by area as chips (Lists › Skill groups). */
class SkillGroupsSection extends SectionType
{
    public function key(): string
    {
        return 'skill_groups';
    }

    public function label(): string
    {
        return __('Skill groups');
    }

    public function description(): string
    {
        return __('Skills grouped by area, from Lists › Skill groups.');
    }

    public function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public function fields(): array
    {
        return [$this->eyebrow()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['groups' => $context->site->collection('skills')];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['groups'] !== [];
    }
}
