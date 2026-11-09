<?php

namespace App\Http\Controllers;

use App\Content\PageRenderer;
use App\Content\SeoBuilder;
use App\Content\Site;
use App\Models\SlugRedirect;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function __construct(private readonly Site $site, private readonly PageRenderer $renderer) {}

    public function home(string $locale): View
    {
        $page = $this->site->page('home');
        abort_if($page === null, 404);

        return $this->render($page, $locale);
    }

    public function show(string $locale, string $slug): View|RedirectResponse
    {
        $page = $this->site->pageBySlug($slug);
        if ($page === null) {
            $redirect = SlugRedirect::query()->where('kind', 'page')->where('old_slug', $slug)->first();
            $target = $redirect ? $this->site->pageById($redirect->target_id) : null;
            abort_if($target === null, 404);

            return redirect()->to($this->site->pageUrl($target, $locale), 301);
        }
        if (($page['key'] ?? null) === 'home') {
            return redirect()->route('home', ['locale' => $locale], 301);
        }

        return $this->render($page, $locale);
    }

    public function render(array $page, string $locale): View
    {
        return view('pages.page', [
            'page' => $page,
            'sections' => $this->renderer->sections($page, $locale),
            'locale' => $locale,
            'site' => $this->site,
            'alternates' => $this->alternates($page),
            'isHome' => ($page['key'] ?? null) === 'home',
            'meta' => app(SeoBuilder::class)->forPage($page, $locale, $this->site->pageUrl($page, $locale)),
        ]);
    }

    /** @return array<string, string> locale => URL of the same page */
    private function alternates(array $page): array
    {
        return collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => $this->site->pageUrl($page, $l)])->all();
    }
}
