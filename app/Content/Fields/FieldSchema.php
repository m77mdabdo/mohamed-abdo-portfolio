<?php

namespace App\Content\Fields;

use App\Rules\SafeLinkUrl;
use App\Support\Locales;
use App\Support\SafeUrl;

/**
 * Server-side contract for typed content. clean() keeps only declared fields with the right shape
 * (unknown keys are dropped, strings trimmed and cut to length, links checked against the allow-list);
 * rules() gives Laravel validation rules so bad input is reported instead of silently fixed.
 */
final class FieldSchema
{
    public const LINK_TYPES = ['none', 'page', 'section', 'project', 'url', 'email', 'cv'];

    /**
     * @param  array<int, Field>  $fields
     * @return array<string, mixed>
     */
    public static function defaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field->name] = match (true) {
                $field->translatable => is_array($field->default) ? $field->default : ['en' => (string) ($field->default ?? ''), 'ar' => (string) ($field->default ?? '')],
                $field->type === Field::LINK => self::emptyLink(),
                default => $field->default,
            };
        }

        return $out;
    }

    /**
     * @param  array<int, Field>  $fields
     * @return array<string, mixed>
     */
    public static function clean(array $fields, array $data): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field->name] = self::cleanValue($field, $data[$field->name] ?? null);
        }

        return $out;
    }

    private static function cleanValue(Field $field, mixed $value): mixed
    {
        if ($field->translatable && ! in_array($field->type, [Field::ITEMS, Field::BUTTONS], true)) {
            $value = is_array($value) ? $value : [];
            $clean = [];
            foreach (Locales::ALL as $locale) {
                $clean[$locale] = self::cleanScalar($field, $value[$locale] ?? '');
            }

            return $clean;
        }

        return match ($field->type) {
            Field::TOGGLE => (bool) ($value ?? $field->default),
            Field::NUMBER => $value === null || $value === '' ? null : max($field->min ?? 0, min($field->max ?? PHP_INT_MAX, (int) $value)),
            Field::MEDIA => is_numeric($value) ? (int) $value : null,
            Field::SELECT => array_key_exists((string) $value, $field->options) ? (string) $value : $field->default,
            Field::COLOR => in_array(strtoupper((string) $value), $field->allowedValues, true) ? strtoupper((string) $value) : $field->default,
            Field::LINK => self::cleanLink(is_array($value) ? $value : []),
            Field::BUTTONS => self::cleanButtons(is_array($value) ? $value : [], $field->maxItems),
            Field::ITEMS => self::cleanItems($field, is_array($value) ? $value : []),
            default => self::cleanScalar($field, $value),
        };
    }

    private static function cleanScalar(Field $field, mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        $value = str_replace("\r\n", "\n", $value);
        if (in_array($field->type, [Field::TEXT, Field::EMAIL, Field::URL], true)) {
            $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        }
        if ($field->type === Field::URL && ! SafeUrl::isAllowed($value)) {
            return '';
        }

        return $field->max ? mb_substr($value, 0, $field->max) : $value;
    }

    /** @return array{type: string, page: ?int, anchor: string, project: ?int, url: string, new_tab: bool} */
    public static function emptyLink(): array
    {
        return ['type' => 'none', 'page' => null, 'anchor' => '', 'project' => null, 'url' => '', 'new_tab' => false];
    }

    public static function cleanLink(array $link): array
    {
        $type = in_array($link['type'] ?? null, self::LINK_TYPES, true) ? $link['type'] : 'none';
        $url = trim((string) ($link['url'] ?? ''));
        $anchor = preg_replace('/[^A-Za-z0-9\-_]/', '', ltrim((string) ($link['anchor'] ?? ''), '#')) ?? '';

        return [
            'type' => $type,
            'page' => is_numeric($link['page'] ?? null) ? (int) $link['page'] : null,
            'anchor' => mb_substr($anchor, 0, 60),
            'project' => is_numeric($link['project'] ?? null) ? (int) $link['project'] : null,
            'url' => SafeUrl::isAllowed($url) ? mb_substr($url, 0, 500) : '',
            'new_tab' => (bool) ($link['new_tab'] ?? false),
        ];
    }

    private static function cleanButtons(array $buttons, int $max): array
    {
        $out = [];
        foreach (array_slice(array_values($buttons), 0, $max) as $button) {
            if (! is_array($button)) {
                continue;
            }
            $label = is_array($button['label'] ?? null) ? $button['label'] : [];
            $out[] = [
                'label' => ['en' => mb_substr(trim((string) ($label['en'] ?? '')), 0, 60), 'ar' => mb_substr(trim((string) ($label['ar'] ?? '')), 0, 60)],
                'style' => in_array($button['style'] ?? null, ['solid', 'ghost', 'outline'], true) ? $button['style'] : 'solid',
                'link' => self::cleanLink(is_array($button['link'] ?? null) ? $button['link'] : []),
            ];
        }

        return $out;
    }

    private static function cleanItems(Field $field, array $items): array
    {
        $out = [];
        foreach (array_slice(array_values($items), 0, $field->maxItems) as $item) {
            if (is_array($item)) {
                $out[] = self::clean($field->children, $item);
            }
        }

        return $out;
    }

    /**
     * Validation rules for the given fields, nested under $prefix (e.g. "data").
     *
     * @param  array<int, Field>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function rules(array $fields, string $prefix = ''): array
    {
        $rules = [];
        $p = $prefix === '' ? '' : $prefix.'.';
        foreach ($fields as $field) {
            $key = $p.$field->name;
            if ($field->translatable && ! in_array($field->type, [Field::ITEMS, Field::BUTTONS], true)) {
                foreach (Locales::ALL as $i => $locale) {
                    $base = ['nullable', 'string'];
                    if ($field->max) {
                        $base[] = 'max:'.$field->max;
                    }
                    // A required translatable field needs at least one language (the other falls back to it).
                    if ($field->required && $i === 0) {
                        $base[0] = 'required_without:'.$key.'.'.Locales::other($locale);
                    }
                    $rules[$key.'.'.$locale] = $base;
                }

                continue;
            }
            $rules = array_merge($rules, match ($field->type) {
                Field::TOGGLE => [$key => ['nullable', 'boolean']],
                Field::NUMBER => [$key => ['nullable', 'integer', 'min:'.($field->min ?? 0), 'max:'.($field->max ?? 100000)]],
                Field::MEDIA => [$key => [$field->required ? 'required' : 'nullable', 'integer', 'exists:media,id']],
                Field::SELECT => [$key => ['nullable', 'in:'.implode(',', array_keys($field->options))]],
                Field::COLOR => [$key => ['nullable', 'in:'.implode(',', $field->allowedValues)]],
                Field::EMAIL => [$key => [$field->required ? 'required' : 'nullable', 'email:rfc', 'max:190']],
                Field::URL => [$key => ['nullable', 'string', 'max:500', new SafeLinkUrl]],
                Field::LINK => self::linkRules($key),
                Field::BUTTONS => array_merge(
                    [$key => ['nullable', 'array', 'max:'.$field->maxItems]],
                    [$key.'.*.label.en' => ['nullable', 'string', 'max:60'], $key.'.*.label.ar' => ['nullable', 'string', 'max:60'], $key.'.*.style' => ['nullable', 'in:solid,ghost,outline']],
                    self::linkRules($key.'.*.link'),
                ),
                Field::ITEMS => array_merge([$key => ['nullable', 'array', 'max:'.$field->maxItems]], self::rules($field->children, $key.'.*')),
                default => [$key => $field->max ? [$field->required ? 'required' : 'nullable', 'string', 'max:'.$field->max] : [$field->required ? 'required' : 'nullable', 'string']],
            });
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    private static function linkRules(string $key): array
    {
        return [
            $key.'.type' => ['nullable', 'in:'.implode(',', self::LINK_TYPES)],
            $key.'.page' => ['nullable', 'integer', 'exists:pages,id'],
            $key.'.project' => ['nullable', 'integer', 'exists:projects,id'],
            $key.'.anchor' => ['nullable', 'string', 'max:60', 'regex:/^#?[A-Za-z0-9\-_]*$/'],
            $key.'.url' => ['nullable', 'string', 'max:500', new SafeLinkUrl],
            $key.'.new_tab' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Media ids referenced anywhere in cleaned data (used before deleting a file).
     *
     * @param  array<int, Field>  $fields
     * @return array<int, int>
     */
    public static function mediaIds(array $fields, array $data): array
    {
        $ids = [];
        foreach ($fields as $field) {
            $value = $data[$field->name] ?? null;
            if ($field->type === Field::MEDIA && is_numeric($value)) {
                $ids[] = (int) $value;
            }
            if ($field->type === Field::ITEMS && is_array($value)) {
                foreach ($value as $item) {
                    $ids = array_merge($ids, self::mediaIds($field->children, is_array($item) ? $item : []));
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
