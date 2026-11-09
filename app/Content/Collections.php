<?php

namespace App\Content;

use App\Models\Category;
use App\Models\Experience;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\SkillGroup;
use App\Models\SpokenLanguage;
use App\Models\Stat;
use App\Models\Technology;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared lists used by several sections and pages. Each list is published as one subject
 * ("collection:services"), so reordering or editing several rows goes live together.
 */
final class Collections
{
    /**
     * type => [model class, columns kept in the snapshot, whether restore may delete rows].
     * Technologies and categories are referenced by projects, so a restore never deletes them.
     */
    private const TYPES = [
        'services' => [Service::class, ['number', 'title', 'description', 'is_visible', 'sort_order'], true],
        'experience' => [Experience::class, ['period', 'role', 'organization', 'description', 'is_current', 'is_visible', 'sort_order'], true],
        'skills' => [SkillGroup::class, ['name', 'items', 'is_visible', 'sort_order'], true],
        'languages' => [SpokenLanguage::class, ['name', 'level', 'is_visible', 'sort_order'], true],
        'process' => [ProcessStep::class, ['title', 'description', 'is_visible', 'sort_order'], true],
        'testimonials' => [Testimonial::class, ['name', 'role', 'quote', 'has_consent', 'is_visible', 'sort_order'], true],
        'stats' => [Stat::class, ['value', 'label', 'is_visible', 'sort_order'], true],
        'technologies' => [Technology::class, ['name', 'icon', 'color', 'role', 'in_cube', 'band_row', 'sort_order'], false],
        'categories' => [Category::class, ['slug', 'name', 'sort_order'], false],
    ];

    /** @return array<int, string> */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /** @return class-string<Model> */
    public static function model(string $type): string
    {
        return self::TYPES[$type][0];
    }

    /** @return array<int, string> */
    public static function columns(string $type): array
    {
        return self::TYPES[$type][1];
    }

    public static function prunes(string $type): bool
    {
        return self::TYPES[$type][2];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            'services' => __('Services'),
            'experience' => __('Experience'),
            'skills' => __('Skill groups'),
            'languages' => __('Languages'),
            'process' => __('Process steps'),
            'testimonials' => __('Testimonials'),
            'stats' => __('Figures'),
            'technologies' => __('Technologies'),
            'categories' => __('Categories'),
            default => $type,
        };
    }

    /** Collection type for a model class (used by dashboard resources). */
    public static function typeFor(string $modelClass): ?string
    {
        foreach (self::TYPES as $type => [$class]) {
            if ($class === $modelClass) {
                return $type;
            }
        }

        return null;
    }
}
