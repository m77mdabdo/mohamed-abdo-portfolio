<?php

namespace App\Content\Sections;

use App\Content\LinkResolver;
use App\Content\MediaResolver;
use App\Models\Media;
use App\Support\RichTitle;
use App\Support\Tr;
use Illuminate\Support\HtmlString;

/**
 * Read-only view of one section's cleaned data in one language. Views call $s->t('title'),
 * $s->title('title'), $s->media('portrait'), $s->buttons('buttons') — never raw arrays.
 */
final class SectionData
{
    public function __construct(
        public readonly string $uid,
        public readonly string $type,
        public readonly ?string $preset,
        public readonly ?string $anchor,
        private readonly array $data,
        private readonly string $locale,
        private readonly LinkResolver $links,
        private readonly MediaResolver $media,
    ) {}

    public function raw(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function t(string $name): string
    {
        return Tr::pick($this->data[$name] ?? null, $this->locale);
    }

    public function filled(string $name): bool
    {
        return trim($this->t($name)) !== '';
    }

    public function on(string $name): bool
    {
        return (bool) ($this->data[$name] ?? false);
    }

    public function title(string $name): HtmlString
    {
        return RichTitle::render($this->t($name));
    }

    public function plain(string $name): string
    {
        return RichTitle::plain($this->t($name));
    }

    public function words(string $name, bool $mask = false): HtmlString
    {
        return RichTitle::words(RichTitle::plain($this->t($name)), $mask);
    }

    public function media(string $name): ?Media
    {
        $id = $this->data[$name] ?? null;

        return is_numeric($id) ? $this->media->find((int) $id) : null;
    }

    /** @return array<int, array{label: string, href: string, style: string, external: bool, new_tab: bool, download: bool}> */
    public function buttons(string $name = 'buttons'): array
    {
        $out = [];
        foreach ((array) ($this->data[$name] ?? []) as $button) {
            $link = $this->links->resolve($button['link'] ?? [], $this->locale);
            $label = Tr::pick($button['label'] ?? null, $this->locale);
            if ($link === null || $label === '') {
                continue;
            }
            $out[] = ['label' => $label, 'style' => $button['style'] ?? 'solid'] + $link;
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public function items(string $name): array
    {
        return array_values((array) ($this->data[$name] ?? []));
    }

    public function itemText(array $item, string $name): string
    {
        return Tr::pick($item[$name] ?? null, $this->locale);
    }

    public function domId(): string
    {
        return $this->anchor ?: 's-'.strtolower(substr($this->uid, -8));
    }
}
