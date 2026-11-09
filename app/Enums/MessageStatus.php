<?php

namespace App\Enums;

enum MessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case InProgress = 'in_progress';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Read => __('Read'),
            self::InProgress => __('In progress'),
            self::Archived => __('Archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::Read => 'gray',
            self::InProgress => 'warning',
            self::Archived => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
