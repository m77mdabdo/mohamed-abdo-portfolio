<?php

namespace App\Http\Controllers;

use App\Content\PageRenderer;
use App\Content\Sections\SectionRegistry;
use App\Content\SeoBuilder;
use App\Content\Site;
use App\Models\SlugRedirect;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Stateless: the request-scoped Site (published or preview content) is injected into each action,
 * never stored on the controller, which the router may reuse between requests.
 */
class PageController extends Controller
{
    public function home(string $locale, Site $site): View
    {
        $page = $site->page('home');
        abort_if($page === null, 404);

        return $this->render($page, $locale, $site);
    }

    public function show(string $locale, string $slug, Site $site): View|RedirectResponse
    {
        $page = $site->pageBySlug($slug);
        if ($page === null) {
            $redirect = SlugRedirect::query()->where('kind', 'page')->where('old_slug', $slug)->first();
            $target = $redirect ? $site->pageById($redirect->target_id) : null;
            abort_if($target === null, 404);

            return redirect()->to($site->pageUrl($target, $locale), 301);
        }
        if (($page['key'] ?? null) === 'home') {
            return redirect()->route('home', ['locale' => $locale], 301);
        }

        return $this->render($page, $locale, $site);
    }

    public function render(array $page, string $locale, Site $site): View
    {
        return view('pages.page', [
            'page' => $page,
            'sections' => (new PageRenderer($site, app(SectionRegistry::class)))->sections($page, $locale),
            'locale' => $locale,
            'site' => $site,
            'alternates' => collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => $site->pageUrl($page, $l)])->all(),
            'isHome' => ($page['key'] ?? null) === 'home',
            'meta' => (new SeoBuilder($site))->forPage($page, $locale, $site->pageUrl($page, $locale)),
        ]);
    }
}
