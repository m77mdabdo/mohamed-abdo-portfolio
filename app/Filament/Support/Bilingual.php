<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

/** Arabic and English inputs side by side for one translatable column ({"en","ar"}). */
final class Bilingual
{
    public static function field(string $name, string $label, bool $textarea = false, int $max = 200, bool $required = false): Fieldset
    {
        $make = fn (string $locale) => ($textarea ? Textarea::make("{$name}.{$locale}")->rows(3)->autosize() : TextInput::make("{$name}.{$locale}"))
            ->label($locale === 'ar' ? __('Arabic') : __('English'))
            ->maxLength($max)
            ->required($required && $locale === 'en')
            ->extraInputAttributes(['dir' => $locale === 'ar' ? 'rtl' : 'ltr']);

        return Fieldset::make(__($label))->schema([$make('ar'), $make('en')])->columns(['default' => 1, 'lg' => 2]);
    }
}
