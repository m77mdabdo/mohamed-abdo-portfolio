<?php

namespace App\Enums;

/**
 * Owner: everything, including publishing, restoring, users, settings and deleting.
 * Editor: edits drafts, uploads media, previews and reads messages — cannot publish or manage people.
 */
enum Role: string
{
    case Owner = 'owner';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Editor => __('Editor'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
