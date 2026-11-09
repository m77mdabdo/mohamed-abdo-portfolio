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
    /** Stateless: the request-scoped Site is injected per action, never stored on the controller. */
    public function show(string $locale, string $slug, Site $site): View|RedirectResponse
    {
        $project = $site->project($slug);
        if ($project === null) {
            $redirect = SlugRedirect::query()->where('kind', 'project')->where('old_slug', $slug)->first();
            $target = $redirect ? $site->projectById($redirect->target_id) : null;
            abort_if($target === null, 404);

            return redirect()->route('projects.show', ['locale' => $locale, 'slug' => $target['slug']], 301);
        }

        $all = $site->projects();
        $index = (int) collect($all)->search(fn ($p) => $p['id'] === $project['id']);
        $media = $site->mediaResolver();
        $media->preload(array_merge([$project['cover_media_id']], array_column($project['gallery'] ?? [], 'media_id')));

        return view('pages.project', [
            'project' => $project,
            'next' => $site->nextProject($project),
            'position' => $index + 1,
            'total' => count($all),
            'labels' => $site->settings('project_labels'),
            'locale' => $locale,
            'site' => $site,
            'workPage' => $site->page('work'),
            'meta' => (new SeoBuilder($site))->forProject($project, $locale, route('projects.show', ['locale' => $locale, 'slug' => $project['slug']])),
            'alternates' => collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => route('projects.show', ['locale' => $l, 'slug' => $project['slug']])])->all(),
        ]);
    }
}
