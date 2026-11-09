<?php

namespace App\Http\Controllers;

use App\Content\SeoBuilder;
use App\Content\Site;
use App\Models\SlugRedirect;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Shareable project pages: /{locale}/projects/{slug}. Renamed slugs answer with a 301 to the new address. */
class ProjectController extends Controller
{
    public function __construct(private readonly Site $site) {}

    public function show(string $locale, string $slug): View|RedirectResponse
    {
        $project = $this->site->project($slug);
        if ($project === null) {
            $redirect = SlugRedirect::query()->where('kind', 'project')->where('old_slug', $slug)->first();
            $target = $redirect ? $this->site->projectById($redirect->target_id) : null;
            abort_if($target === null, 404);

            return redirect()->route('projects.show', ['locale' => $locale, 'slug' => $target['slug']], 301);
        }

        $all = $this->site->projects();
        $index = (int) collect($all)->search(fn ($p) => $p['id'] === $project['id']);
        $media = $this->site->mediaResolver();
        $media->preload(array_merge([$project['cover_media_id']], array_column($project['gallery'] ?? [], 'media_id')));

        return view('pages.project', [
            'project' => $project,
            'next' => $this->site->nextProject($project),
            'position' => $index + 1,
            'total' => count($all),
            'labels' => $this->site->settings('project_labels'),
            'locale' => $locale,
            'site' => $this->site,
            'workPage' => $this->site->page('work'),
            'meta' => app(SeoBuilder::class)->forProject($project, $locale, route('projects.show', ['locale' => $locale, 'slug' => $project['slug']])),
            'alternates' => collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => route('projects.show', ['locale' => $l, 'slug' => $project['slug']])])->all(),
        ]);
    }
}
