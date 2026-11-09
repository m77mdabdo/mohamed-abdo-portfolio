<?php

namespace App\Content;

use App\Content\Fields\FieldSchema;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionRegistry;

/** Turns a page snapshot into the list of sections to render, in order, for one language. */
final class PageRenderer
{
    public function __construct(
        private readonly Site $site,
        private readonly SectionRegistry $registry,
    ) {}

    /** @return array<int, array{view: string, data: array}> */
    public function sections(array $page, string $locale): array
    {
        $context = new SectionContext($locale, $this->site, $page);
        $visible = array_values(array_filter($page['sections'] ?? [], fn ($s) => ($s['visible'] ?? true) && $this->registry->has($s['type'] ?? '')));

        // One query for every image used by the page's sections.
        $mediaIds = [];
        foreach ($visible as $s) {
            $mediaIds = array_merge($mediaIds, FieldSchema::mediaIds($this->registry->get($s['type'])->fields(), $s['data'] ?? []));
        }
        $this->site->mediaResolver()->preload($mediaIds);

        $out = [];
        foreach ($visible as $s) {
            $type = $this->registry->get($s['type']);
            $section = new SectionData(
                uid: $s['uid'],
                type: $s['type'],
                preset: $s['preset'] ?? null,
                anchor: $s['anchor'] ?? null,
                data: $type->clean($s['data'] ?? []),
                locale: $locale,
                links: $this->site->links(),
                media: $this->site->mediaResolver(),
            );
            $prepared = $type->prepare($section, $context);
            if (! $type->shouldRender($section, $context, $prepared)) {
                continue;
            }
            $out[] = ['view' => $type->view(), 'data' => ['s' => $section, 'site' => $this->site, 'locale' => $locale] + $prepared];
        }

        return $out;
    }
}
