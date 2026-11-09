<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** Pinned horizontal reel of project covers. */
class WorkReelSection extends SectionType
{
    public function key(): string
    {
        return 'work_reel';
    }

    public function label(): string
    {
        return __('Work reel (moving project cards)');
    }

    public function description(): string
    {
        return __('Project covers that slide sideways while scrolling.');
    }

    public function icon(): string
    {
        return 'heroicon-o-film';
    }

    public function defaultAnchor(): ?string
    {
        return 'work';
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable(),
            Field::select('selection', 'Projects shown', ['all' => 'All visible projects', 'featured' => 'Featured projects only'], 'all'),
            Field::number('limit', 'Maximum number (empty = no limit)', 1, 30),
            Field::text('cursor_label', 'Cursor label on hover')->translatable()->max(20),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        $projects = $context->site->projects();
        if ($section->raw('selection') === 'featured') {
            $projects = array_values(array_filter($projects, fn ($p) => $p['is_featured']));
        }
        if ($limit = $section->raw('limit')) {
            $projects = array_slice($projects, 0, (int) $limit);
        }

        return ['projects' => $projects];
    }

    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return $prepared['projects'] !== [];
    }
}
