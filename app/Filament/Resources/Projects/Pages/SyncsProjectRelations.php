<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

/** Stack (ordered technologies) and galleries are edited as simple lists and saved here in one transaction. */
trait SyncsProjectRelations
{
    protected array $pendingRelations = [];

    protected function pullRelations(array &$data): array
    {
        $relations = [
            'stack' => $data['stack_items'] ?? [],
            'desktop' => $data['desktop_shots'] ?? [],
            'mobile' => $data['mobile_shots'] ?? [],
        ];
        unset($data['stack_items'], $data['desktop_shots'], $data['mobile_shots']);
        $data['features'] = array_values(array_filter($data['features'] ?? [], fn ($f) => trim(($f['en'] ?? '').($f['ar'] ?? '')) !== ''));

        return $relations;
    }

    protected function fillRelations(Project $project, array $data): array
    {
        $project->loadMissing(['stack', 'gallery']);
        $data['stack_items'] = $project->stack->map(fn ($s) => ['technology_id' => $s->technology_id])->values()->all();
        foreach (['desktop' => 'desktop_shots', 'mobile' => 'mobile_shots'] as $kind => $key) {
            $data[$key] = $project->gallery->where('kind', $kind)->map(fn ($g) => ['media_id' => $g->media_id, 'caption' => $g->caption ?? ['en' => '', 'ar' => '']])->values()->all();
        }

        return $data;
    }

    protected function syncRelations(Project $project, array $relations): void
    {
        DB::transaction(function () use ($project, $relations) {
            $project->stack()->getQuery()->delete();
            $seen = [];
            foreach (array_values($relations['stack']) as $i => $row) {
                $id = (int) ($row['technology_id'] ?? 0);
                if ($id && ! isset($seen[$id])) {
                    $project->stack()->create(['technology_id' => $id, 'sort_order' => $i + 1]);
                    $seen[$id] = true;
                }
            }
            $project->gallery()->getQuery()->delete();
            $order = 0;
            foreach (['desktop', 'mobile'] as $kind) {
                foreach (array_values($relations[$kind]) as $row) {
                    if (! empty($row['media_id'])) {
                        $project->gallery()->create(['media_id' => (int) $row['media_id'], 'kind' => $kind, 'caption' => $row['caption'] ?? null, 'sort_order' => ++$order]);
                    }
                }
            }
        });
        $project->unsetRelations();
    }
}
