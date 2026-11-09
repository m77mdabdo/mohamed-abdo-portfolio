<?php

namespace App\Content\Contracts;

/**
 * Where the site reads content from. Visitors get PublishedReader (immutable revisions, cached);
 * signed-in staff on a preview link get DraftReader (the working copy, never cached).
 */
interface ContentReader
{
    public function get(string $subject): ?array;

    /**
     * Snapshots of every subject with the prefix ("project", "page").
     *
     * @return array<string, array>
     */
    public function all(string $prefix): array;

    public function isPreview(): bool;
}
