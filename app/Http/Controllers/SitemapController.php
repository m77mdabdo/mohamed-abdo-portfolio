<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Support\Locales;
use Illuminate\Http\Response;

/** sitemap.xml with hreflang alternates for every published page and project; robots.txt from SEO settings. */
class SitemapController extends Controller
{
    public function sitemap(Site $site): Response
    {
        $entries = [];
        foreach ($site->pages() as $page) {
            if (($page['seo']['noindex'] ?? false)) {
                continue;
            }
            $entries[] = collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => $site->pageUrl($page, $l)])->all();
        }
        foreach ($site->projects() as $project) {
            $entries[] = collect(Locales::ALL)->mapWithKeys(fn ($l) => [$l => route('projects.show', ['locale' => $l, 'slug' => $project['slug']])])->all();
        }

        return response()->view('seo.sitemap', ['entries' => $entries], 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(Site $site): Response
    {
        $indexable = ($site->settings('seo')['indexable'] ?? true) && app()->environment('production');
        $lines = $indexable
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /preview', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
