<?php

namespace App\Content\Sections;

use App\Content\Site;

/** What a section needs to know about where it is rendered. */
final class SectionContext
{
    public function __construct(
        public readonly string $locale,
        public readonly Site $site,
        public readonly array $page,
    ) {}
}
