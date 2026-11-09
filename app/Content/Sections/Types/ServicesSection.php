<?php

namespace App\Content\Sections\Types;

use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** The services list (Lists › Services) on the brick panel. */
class ServicesSection extends SectionType
{
    public function key(): string
    {
        return 'services';
    }

    public function label(): string
    {
        return __('Services');
    }

    public function description(): string
    {
        return __('Numbered services from Lists › Services.');
    }

    public function icon(): string
    {
        return 'heroicon-o-briefcase';
    }

    public function defaultAnchor(): ?string
    {
        return 'services';
    }

    public function fields(): array
    {
        return [$this->eyebrow()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['services' => $context->site->collection('services')];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['services'] !== [];
    }
}
