<?php

namespace App\Content\Sections\Types;

use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Client quotes. Stays hidden until at least one quote is visible and marked with the client's consent. */
class TestimonialsSection extends SectionType
{
    public function key(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return __('Client testimonials');
    }

    public function description(): string
    {
        return __('Shown only when real quotes with consent exist (Lists › Testimonials).');
    }

    public function icon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public function defaultAnchor(): ?string
    {
        return 'testimonials';
    }

    public function fields(): array
    {
        return [$this->eyebrow()];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return ['quotes' => array_values(array_filter($context->site->collection('testimonials'), fn ($q) => $q['has_consent'] ?? false))];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['quotes'] !== [];
    }
}
