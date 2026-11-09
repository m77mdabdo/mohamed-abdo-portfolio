<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;
use App\Support\Tr;

/** Cinematic opening: giant moving name, 3D scene, cut-out portrait, title and buttons. */
class HeroSection extends SectionType
{
    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return __('Hero (opening)');
    }

    public function description(): string
    {
        return __('Portrait, 3D scene, moving name, title and buttons.');
    }

    public function icon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public function defaultAnchor(): ?string
    {
        return 'top';
    }

    public function fields(): array
    {
        return [
            Field::text('badge', 'Badge')->translatable()->max(90),
            Field::title('title', 'Title')->translatable()->required(),
            Field::textarea('subtitle', 'Subtitle')->translatable()->max(400),
            Field::buttons('buttons', 'Buttons', 3),
            Field::media('portrait', 'Portrait (cut-out with transparent background)'),
            Field::text('marquee', 'Moving name (leave empty to use your name)')->translatable()->max(40),
            Field::text('scroll_label', 'Scroll hint')->translatable()->max(30),
            Field::toggle('show_scene', '3D scene behind the portrait'),
            Field::toggle('show_clock', 'Local time tag'),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        $identity = $context->site->settings('identity');
        $portrait = $section->media('portrait') ?? $context->site->media($identity['portrait'] ?? null);

        return [
            'marquee' => $section->t('marquee') ?: Tr::pick($identity['name'] ?? null, $context->locale),
            'portrait' => $portrait,
            'identity' => $identity,
        ];
    }
}
