<?php

namespace App\Content\Media;

use App\Content\Fields\Field;
use App\Content\Sections\SectionRegistry;
use App\Content\Settings\SettingsGroups;
use App\Models\ContentRevision;
use App\Models\PageSection;
use App\Models\Project;
use App\Models\ProjectMedia;
use App\Models\SiteSetting;

/**
 * Where a file is used — in drafts and in the live version. A file that is still used cannot be deleted
 * (the dashboard shows the list instead). Computed on demand: it only runs when someone tries to delete.
 */
final class MediaUsage
{
    public function __construct(private readonly SectionRegistry $sections) {}

    /** @return array<int, string> human-readable places */
    public function find(int $mediaId): array
    {
        $places = [];
        $keys = $this->mediaKeys();

        foreach (Project::query()->where('cover_media_id', $mediaId)->get(['id', 'title']) as $p) {
            $places[] = __('Project cover').': '.$p->tr('title');
        }
        foreach (ProjectMedia::query()->where('media_id', $mediaId)->with('project:id,title')->get() as $row) {
            $places[] = __('Project gallery').': '.$row->project?->tr('title');
        }
        foreach (PageSection::query()->with('page:id,title')->get(['id', 'page_id', 'type', 'data']) as $section) {
            if ($this->contains($section->data ?? [], $mediaId, $keys)) {
                $places[] = __('Page').': '.$section->page?->tr('title').' › '.($this->sections->get($section->type)?->label() ?? $section->type);
            }
        }
        foreach (SiteSetting::query()->get(['group', 'data']) as $setting) {
            if ($this->contains($setting->data ?? [], $mediaId, $keys)) {
                $places[] = __('Settings').': '.(SettingsGroups::labels()[$setting->group] ?? $setting->group);
            }
        }

        // The live version of each subject (deleting would break what visitors currently see).
        $latest = ContentRevision::query()->selectRaw('subject, MAX(version) as v')->groupBy('subject');
        $live = ContentRevision::query()->joinSub($latest, 'l', fn ($j) => $j->on('content_revisions.subject', '=', 'l.subject')->on('content_revisions.version', '=', 'l.v'))
            ->get(['content_revisions.subject', 'content_revisions.snapshot']);
        foreach ($live as $revision) {
            if ($this->contains($revision->snapshot ?? [], $mediaId, $keys)) {
                $places[] = __('Published version').': '.$revision->subject;
            }
        }

        return array_values(array_unique($places));
    }

    /** @return array<string, true> every field name that holds a media id */
    private function mediaKeys(): array
    {
        $keys = ['cover_media_id' => true, 'media_id' => true];
        $collect = function (array $fields) use (&$collect, &$keys) {
            foreach ($fields as $f) {
                if ($f->type === Field::MEDIA) {
                    $keys[$f->name] = true;
                }
                if ($f->children) {
                    $collect($f->children);
                }
            }
        };
        foreach ($this->sections->all() as $type) {
            $collect($type->fields());
        }
        foreach (SettingsGroups::keys() as $group) {
            $collect(SettingsGroups::fields($group));
        }

        return $keys;
    }

    private function contains(array $data, int $id, array $keys): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->contains($value, $id, $keys)) {
                    return true;
                }
            } elseif (is_string($key) && isset($keys[$key]) && is_numeric($value) && (int) $value === $id) {
                return true;
            }
        }

        return false;
    }
}
