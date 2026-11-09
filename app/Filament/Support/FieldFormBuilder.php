<?php

namespace App\Filament\Support;

use App\Content\Fields\Field;
use App\Content\Sections\SectionRegistry;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Project;
use App\Rules\SafeLinkUrl;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Builds dashboard form fields from typed Field descriptions. Translatable fields show Arabic and English
 * side by side; links are chosen from pages, sections and projects (or a checked web address);
 * images come from the media library. Nobody types HTML, JSON or code.
 */
final class FieldFormBuilder
{
    /**
     * @param  array<int, Field>  $fields
     * @return array<int, Component>
     */
    public static function components(array $fields, string $prefix = ''): array
    {
        return array_map(fn (Field $f) => self::component($f, $prefix), $fields);
    }

    public static function component(Field $field, string $prefix = ''): Component
    {
        $path = ($prefix === '' ? '' : $prefix.'.').$field->name;
        $label = __($field->label);
        $help = $field->help ? __($field->help) : null;

        if ($field->translatable && ! in_array($field->type, [Field::ITEMS, Field::BUTTONS], true)) {
            return Fieldset::make($label)->schema([
                self::scalar($field, $path.'.ar', __('Arabic'))->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
                self::scalar($field, $path.'.en', __('English'))->extraInputAttributes(['dir' => 'ltr', 'lang' => 'en']),
            ])->columns(['default' => 1, 'lg' => 2])->extraAttributes(['data-field' => $field->name]);
        }

        return match ($field->type) {
            Field::TOGGLE => Toggle::make($path)->label($label)->helperText($help),
            Field::NUMBER => TextInput::make($path)->label($label)->numeric()->integer()->minValue($field->min)->maxValue($field->max)->helperText($help),
            Field::SELECT => Select::make($path)->label($label)->native(false)->selectablePlaceholder(false)
                ->options(array_map(fn ($o) => __($o), $field->options))->default($field->default),
            Field::COLOR => ToggleButtons::make($path)->label($label)->inline()->default($field->default)
                ->options(array_combine($field->allowedValues, array_map(fn ($c) => $c, $field->allowedValues)))
                ->extraAttributes(['class' => 'ma-swatches']),
            Field::MEDIA => MediaPicker::make($path, $field->label, $field->mediaKind)->helperText($help),
            Field::LINK => Fieldset::make($label)->schema(self::linkFields($path)),
            Field::BUTTONS => self::buttons($path, $label, $field->maxItems),
            Field::ITEMS => Repeater::make($path)->label($label)
                ->schema(self::components($field->children))
                ->maxItems($field->maxItems)
                ->reorderableWithButtons()->collapsible()->cloneable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state) => self::itemLabel($state, $field))
                ->addActionLabel(__('Add item')),
            default => self::scalar($field, $path, $label),
        };
    }

    private static function scalar(Field $field, string $path, string $label): TextInput|Textarea
    {
        $component = in_array($field->type, [Field::TEXTAREA, Field::TITLE], true)
            ? Textarea::make($path)->rows($field->type === Field::TITLE ? 2 : 4)->autosize()
            : TextInput::make($path);
        $component->label($label);
        if ($field->max) {
            $component->maxLength($field->max);
        }
        if ($field->help) {
            $component->helperText(__($field->help));
        }
        if ($field->type === Field::EMAIL && $component instanceof TextInput) {
            $component->email()->extraInputAttributes(['dir' => 'ltr']);
        }
        if ($field->type === Field::URL && $component instanceof TextInput) {
            $component->rule(new SafeLinkUrl)->placeholder('https://')->extraInputAttributes(['dir' => 'ltr']);
        }
        if ($field->required && ! $field->translatable) {
            $component->required();
        }

        return $component;
    }

    /** Link chooser: page, section of a page, project, CV, e-mail or a checked web address. */
    public static function linkFields(string $path): array
    {
        $type = fn (Get $get) => $get($path.'.type');

        return [
            Select::make($path.'.type')->label(__('Link to'))->native(false)->selectablePlaceholder(false)->default('none')->live()
                ->options([
                    'none' => __('Nothing'),
                    'page' => __('A page'),
                    'section' => __('A section of a page'),
                    'project' => __('A project'),
                    'cv' => __('CV download'),
                    'email' => __('My e-mail'),
                    'url' => __('Web address'),
                ]),
            Select::make($path.'.page')->label(__('Page'))->native(false)
                ->options(fn () => Page::query()->ordered()->get()->mapWithKeys(fn (Page $p) => [$p->id => $p->tr('title')])->all())
                ->visible(fn (Get $get) => in_array($type($get), ['page', 'section'], true))
                ->required(fn (Get $get) => $type($get) === 'page')
                ->live(),
            Select::make($path.'.anchor')->label(__('Section'))->native(false)
                ->options(fn (Get $get) => self::anchorsOf($get($path.'.page')))
                ->visible(fn (Get $get) => $type($get) === 'section')
                ->required(fn (Get $get) => $type($get) === 'section'),
            Select::make($path.'.project')->label(__('Project'))->native(false)->searchable()
                ->options(fn () => Project::query()->ordered()->get()->mapWithKeys(fn (Project $p) => [$p->id => $p->tr('title')])->all())
                ->visible(fn (Get $get) => $type($get) === 'project')
                ->required(fn (Get $get) => $type($get) === 'project'),
            TextInput::make($path.'.url')->label(__('Address'))->placeholder('https://')->maxLength(500)
                ->rule(new SafeLinkUrl)->extraInputAttributes(['dir' => 'ltr'])
                ->visible(fn (Get $get) => $type($get) === 'url')
                ->required(fn (Get $get) => $type($get) === 'url'),
            Toggle::make($path.'.new_tab')->label(__('Open in a new tab'))
                ->visible(fn (Get $get) => in_array($type($get), ['url', 'page', 'project'], true)),
        ];
    }

    /** @return array<string, string> anchor => "Section name (#anchor)" */
    public static function anchorsOf(mixed $pageId): array
    {
        if (! is_numeric($pageId)) {
            return [];
        }
        $registry = app(SectionRegistry::class);

        return PageSection::query()->where('page_id', $pageId)->whereNotNull('anchor')->orderBy('sort_order')->get()
            ->mapWithKeys(fn ($s) => [$s->anchor => ($registry->get($s->type)?->label() ?? $s->type).' (#'.$s->anchor.')'])->all();
    }

    private static function buttons(string $path, string $label, int $max): Repeater
    {
        return Repeater::make($path)->label($label)
            ->schema([
                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    TextInput::make('label.ar')->label(__('Text (Arabic)'))->maxLength(60)->extraInputAttributes(['dir' => 'rtl']),
                    TextInput::make('label.en')->label(__('Text (English)'))->maxLength(60)->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('style')->label(__('Style'))->native(false)->selectablePlaceholder(false)->default('solid')
                        ->options(['solid' => __('Filled'), 'ghost' => __('Outlined (turquoise)'), 'outline' => __('Outlined (light)')]),
                ]),
                Group::make(self::linkFields('link'))->columns(['default' => 1, 'lg' => 2]),
            ])
            ->maxItems($max)
            ->defaultItems(0)
            ->reorderableWithButtons()->collapsible()->cloneable()
            ->itemLabel(fn (array $state) => ($state['label'][app()->getLocale()] ?? '') ?: ($state['label']['en'] ?? '') ?: __('Button'))
            ->addActionLabel(__('Add button'));
    }

    private static function itemLabel(array $state, Field $field): ?string
    {
        foreach ($field->children as $child) {
            $value = $state[$child->name] ?? null;
            $text = is_array($value) ? (($value[app()->getLocale()] ?? '') ?: ($value['en'] ?? '')) : (is_string($value) ? $value : '');
            if ($text !== '') {
                return mb_strimwidth($text, 0, 60, '…');
            }
        }

        return null;
    }
}
