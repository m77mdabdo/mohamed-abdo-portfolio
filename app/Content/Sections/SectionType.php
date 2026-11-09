<?php

namespace App\Content\Sections;

use App\Content\Fields\Field;
use App\Content\Fields\FieldSchema;

/**
 * One kind of section in the library. A type declares its fields (typed, validated) and its Blade view;
 * adding a new design means adding one subclass, one view and registering it in SectionRegistry.
 */
abstract class SectionType
{
    abstract public function key(): string;

    abstract public function label(): string;

    /** Short help shown when choosing a section to add. */
    public function description(): string
    {
        return '';
    }

    public function icon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    /** @return array<int, Field> */
    abstract public function fields(): array;

    /** Design variants the editor can choose without code. @return array<string, string> */
    public function presets(): array
    {
        return [];
    }

    public function defaultAnchor(): ?string
    {
        return null;
    }

    public function view(): string
    {
        return 'sections.'.str_replace('_', '-', $this->key());
    }

    /** Extra variables for the view (lists from shared collections etc.). */
    public function prepare(SectionData $section, SectionContext $context): array
    {
        return [];
    }

    /** False hides the section on the site even if it is switched on (e.g. testimonials without any quote). */
    public function shouldRender(SectionData $section, SectionContext $context, array $prepared): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return FieldSchema::defaults($this->fields());
    }

    /** @return array<string, mixed> */
    public function clean(array $data): array
    {
        return FieldSchema::clean($this->fields(), $data);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(string $prefix = 'data'): array
    {
        return FieldSchema::rules($this->fields(), $prefix);
    }

    /** Field shortcuts shared by most types. */
    protected function eyebrow(string $default = ''): Field
    {
        return Field::text('eyebrow', 'Small label above the title')->translatable()->max(80);
    }
}
