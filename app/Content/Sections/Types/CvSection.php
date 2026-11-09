<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Résumé: portrait card, CV download, drawn experience timeline and languages. */
class CvSection extends SectionType
{
    public function key(): string
    {
        return 'cv';
    }

    public function label(): string
    {
        return __('Résumé (experience & languages)');
    }

    public function description(): string
    {
        return __('Portrait card, CV download, experience timeline and spoken languages.');
    }

    public function icon(): string
    {
        return 'heroicon-o-identification';
    }

    public function defaultAnchor(): ?string
    {
        return 'cv';
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable(),
            Field::text('view_label', 'View button (opens the PDF)')->translatable()->max(60),
            Field::text('download_label', 'Download button')->translatable()->max(60),
            Field::text('pending_label', 'Text when no CV file is uploaded')->translatable()->max(80),
            Field::text('pending_note', 'Note when no CV file is uploaded')->translatable()->max(160),
            Field::text('experience_title', 'Experience heading')->translatable()->max(60),
            Field::text('languages_title', 'Languages heading')->translatable()->max(60),
            Field::text('now_label', 'Sticker on the current job')->translatable()->max(20),
            Field::toggle('show_card', 'Show the portrait card'),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return [
            'experience' => $context->site->collection('experience'),
            'languages' => $context->site->collection('languages'),
            'cvFile' => $context->site->cvFile(),
            'cvUrl' => route('cv.download', ['locale' => $context->locale]),
            'identity' => $context->site->settings('identity'),
            'portrait' => $context->site->media($context->site->settings('identity')['portrait'] ?? null),
        ];
    }
}
