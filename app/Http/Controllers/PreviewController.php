<?php

namespace App\Http\Controllers;

use App\Content\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Renders any public address with draft content: /preview/{locale}/{path}. The route is signed and
 * limited to signed-in staff; UseDraftContent switches the content source for this request only.
 */
class PreviewController extends Controller
{
    public function __invoke(string $locale, Site $site, ?string $path = null): View|RedirectResponse
    {
        $path = trim((string) $path, '/');
        if (str_starts_with($path, 'projects/')) {
            return app(ProjectController::class)->show($locale, substr($path, 9), $site);
        }
        $pages = app(PageController::class);
        if ($path === '') {
            return $pages->home($locale, $site);
        }

        $page = $site->pageBySlug($path);
        abort_if($page === null, 404);

        return $pages->render($page, $locale, $site);
    }
}
