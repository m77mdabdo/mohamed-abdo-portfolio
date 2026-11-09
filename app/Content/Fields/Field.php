<?php

namespace App\Content\Fields;

/**
 * A typed field of a section or settings group. One description drives three things:
 * the dashboard form (FieldFormBuilder), server-side validation and cleaning (FieldSchema),
 * and the defaults a new section starts with. Editors never type HTML, JSON or code.
 */
final class Field
{
    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const TITLE = 'title';       // textarea that understands *highlight* and line breaks

    public const MEDIA = 'media';

    public const LINK = 'link';

    public const BUTTONS = 'buttons';

    public const TOGGLE = 'toggle';

    public const SELECT = 'select';

    public const NUMBER = 'number';

    public const ITEMS = 'items';       // a repeatable list of sub-fields

    public const EMAIL = 'email';

    public const URL = 'url';

    public const COLOR = 'color';

    public bool $translatable = false;

    public bool $required = false;

    public ?int $max = null;

    public ?int $min = null;

    public ?string $help = null;

    public mixed $default = null;

    /** @var array<string, string> */
    public array $options = [];

    /** @var array<int, Field> */
    public array $children = [];

    public string $mediaKind = 'image';

    public int $maxItems = 20;

    /** @var array<int, string> */
    public array $allowedValues = [];

    private function __construct(public string $type, public string $name, public string $label) {}

    public static function text(string $name, string $label): self
    {
        $f = new self(self::TEXT, $name, $label);
        $f->max = 200;

        return $f;
    }

    public static function textarea(string $name, string $label): self
    {
        $f = new self(self::TEXTAREA, $name, $label);
        $f->max = 2000;

        return $f;
    }

    public static function title(string $name, string $label): self
    {
        $f = new self(self::TITLE, $name, $label);
        $f->max = 300;
        $f->help = 'Wrap words in *stars* to highlight them; press Enter for a new line.';

        return $f;
    }

    public static function media(string $name, string $label, string $kind = 'image'): self
    {
        $f = new self(self::MEDIA, $name, $label);
        $f->mediaKind = $kind;

        return $f;
    }

    public static function link(string $name, string $label): self
    {
        return new self(self::LINK, $name, $label);
    }

    public static function buttons(string $name, string $label, int $max = 3): self
    {
        $f = new self(self::BUTTONS, $name, $label);
        $f->maxItems = $max;
        $f->default = [];

        return $f;
    }

    public static function toggle(string $name, string $label, bool $default = true): self
    {
        $f = new self(self::TOGGLE, $name, $label);
        $f->default = $default;

        return $f;
    }

    /** @param array<string, string> $options value => label */
    public static function select(string $name, string $label, array $options, ?string $default = null): self
    {
        $f = new self(self::SELECT, $name, $label);
        $f->options = $options;
        $f->default = $default ?? array_key_first($options);

        return $f;
    }

    public static function number(string $name, string $label, int $min = 0, int $max = 100): self
    {
        $f = new self(self::NUMBER, $name, $label);
        $f->min = $min;
        $f->max = $max;

        return $f;
    }

    /** @param array<int, Field> $children */
    public static function items(string $name, string $label, array $children, int $maxItems = 20): self
    {
        $f = new self(self::ITEMS, $name, $label);
        $f->children = $children;
        $f->maxItems = $maxItems;
        $f->default = [];

        return $f;
    }

    public static function email(string $name, string $label): self
    {
        $f = new self(self::EMAIL, $name, $label);
        $f->max = 190;

        return $f;
    }

    public static function url(string $name, string $label): self
    {
        $f = new self(self::URL, $name, $label);
        $f->max = 500;

        return $f;
    }

    /** Colour limited to an approved palette (keeps contrast and identity intact). */
    public static function color(string $name, string $label, array $allowed, ?string $default = null): self
    {
        $f = new self(self::COLOR, $name, $label);
        $f->allowedValues = array_map('strtoupper', $allowed);
        $f->default = $default ?? ($f->allowedValues[0] ?? null);

        return $f;
    }

    public function translatable(bool $on = true): self
    {
        $this->translatable = $on;

        return $this;
    }

    public function required(bool $on = true): self
    {
        $this->required = $on;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }
}
