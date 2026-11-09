<?php

namespace App\Content\Sections;

/**
 * Keyed registry of section types (type key → handler). Lookups are O(1); the order of TYPES is the
 * order of the "add section" library in the dashboard. Unknown types are skipped on the site.
 */
final class SectionRegistry
{
    /** @var array<int, class-string<SectionType>> */
    private const TYPES = [
        Types\HeroSection::class,
        Types\StatementSection::class,
        Types\AboutSection::class,
        Types\WorkReelSection::class,
        Types\ProjectIndexSection::class,
        Types\ServicesSection::class,
        Types\CvSection::class,
        Types\SkillsBandSection::class,
        Types\SkillGroupsSection::class,
        Types\TestimonialsSection::class,
        Types\ProcessSection::class,
        Types\ContactSection::class,
        Types\PageHeaderSection::class,
        Types\TextBlockSection::class,
        Types\ImageTextSection::class,
        Types\CtaBannerSection::class,
        Types\StatsSection::class,
    ];

    /** @var array<string, SectionType>|null */
    private ?array $types = null;

    /** @return array<string, SectionType> */
    public function all(): array
    {
        if ($this->types === null) {
            $this->types = [];
            foreach (self::TYPES as $class) {
                $type = app($class);
                $this->types[$type->key()] = $type;
            }
        }

        return $this->types;
    }

    public function get(string $key): ?SectionType
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }
}
