<?php

namespace App\Content;

use App\Models\Media;

/**
 * Request-scoped media lookup. Ids are collected and loaded in one query (no N+1 across sections);
 * later lookups hit the in-memory map.
 */
final class MediaResolver
{
    /** @var array<int, Media|null> */
    private array $loaded = [];

    /** @param iterable<int|null> $ids */
    public function preload(iterable $ids): void
    {
        $missing = [];
        foreach ($ids as $id) {
            if (is_numeric($id) && ! array_key_exists((int) $id, $this->loaded)) {
                $missing[] = (int) $id;
            }
        }
        if ($missing === []) {
            return;
        }
        $found = Media::query()->whereIn('id', array_unique($missing))->get()->keyBy('id');
        foreach ($missing as $id) {
            $this->loaded[$id] = $found->get($id);
        }
    }

    public function find(mixed $id): ?Media
    {
        if (! is_numeric($id)) {
            return null;
        }
        $id = (int) $id;
        if (! array_key_exists($id, $this->loaded)) {
            $this->preload([$id]);
        }

        return $this->loaded[$id];
    }
}
