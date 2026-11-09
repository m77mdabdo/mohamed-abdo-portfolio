<?php

namespace App\Content\Readers;

use App\Content\ContentCache;
use App\Content\Contracts\ContentReader;
use App\Models\ContentRevision;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;

/** Live content for visitors: the latest revision of each subject, cached until the next publish. */
final class PublishedReader implements ContentReader
{
    public function get(string $subject): ?array
    {
        return Cache::rememberForever(ContentCache::subjectKey($subject), function () use ($subject) {
            return ContentRevision::query()->where('subject', $subject)->orderByDesc('version')->first()?->snapshot ?? false;
        }) ?: null;
    }

    public function all(string $prefix): array
    {
        $subjects = Cache::rememberForever(ContentCache::indexKey($prefix), function () use ($prefix) {
            $published = ContentRevision::query()->where('subject', 'like', $prefix.':%')->distinct()->pluck('subject')->all();
            // Deleted pages/projects disappear immediately even though their history is kept.
            $existing = match ($prefix) {
                'page' => Page::query()->pluck('id')->map(fn ($id) => 'page:'.$id)->all(),
                'project' => Project::query()->pluck('id')->map(fn ($id) => 'project:'.$id)->all(),
                default => $published,
            };

            return array_values(array_intersect($published, $existing));
        });

        $out = [];
        foreach ($subjects as $subject) {
            if ($snapshot = $this->get($subject)) {
                $out[$subject] = $snapshot;
            }
        }

        return $out;
    }

    public function isPreview(): bool
    {
        return false;
    }
}
