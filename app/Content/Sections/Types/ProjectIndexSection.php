<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;

/** List of every project with hover preview; optional category filter (works without JavaScript). */
class ProjectIndexSection extends SectionType
{
    public function key(): string
    {
        return 'project_index';
    }

    public function label(): string
    {
        return __('Project list (index)');
    }

    public function description(): string
    {
        return __('All projects as large rows, with a preview that follows the cursor.');
    }

    public function icon(): string
    {
        return 'heroicon-o-queue-list';
    }

    public function defaultAnchor(): ?string
    {
        return 'projects';
    }

    public function fields(): array
    {
        return [
            Field::text('title', 'Column title: projects')->translatable()->max(60),
            Field::text('type_label', 'Column title: type')->translatable()->max(40),
            Field::text('status_label', 'Column title: status')->translatable()->max(40),
            Field::toggle('show_filters', 'Show category filter', false),
            Field::text('all_label', 'Filter: "all" label')->translatable()->max(40),
            Field::text('cursor_label', 'Cursor label on hover')->translatable()->max(20),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        $projects = $context->site->projects();
        $categories = $section->on('show_filters') ? $context->site->categoriesInUse($projects) : [];
        $active = request()->query('category');
        $active = is_string($active) && collect($categories)->contains('slug', $active) ? $active : null;

        return ['projects' => $projects, 'categories' => $categories, 'activeCategory' => $active];
    }
}
