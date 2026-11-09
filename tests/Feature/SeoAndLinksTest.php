<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** 9. Every public link opens directly; language, direction, hreflang, canonical and sitemap are right. */
class SeoAndLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_crawl_every_internal_link_in_both_languages(): void
    {
        $this->importReference();
        $base = rtrim(config('app.url'), '/');
        $queue = ['/ar', '/en'];
        $seen = [];
        while ($queue && count($seen) < 80) {
            $path = array_shift($queue);
            if (isset($seen[$path])) {
                continue;
            }
            $response = $this->get($path);
            $seen[$path] = $response->getStatusCode();
            if (! $response->isOk()) {
                continue;
            }
            preg_match_all('/href="([^"#]+)/', $response->getContent(), $m);
            foreach ($m[1] as $href) {
                $href = str_replace($base, '', html_entity_decode($href));
                if (str_starts_with($href, '/') && ! Str::startsWith($href, ['/build', '/storage', '/favicon', '/admin', '/livewire']) && ! isset($seen[$href])) {
                    $queue[] = $href;
                }
            }
        }
        $broken = array_filter($seen, fn ($code, $path) => $code !== 200 && ! ($path === '/ar/cv/download' || $path === '/en/cv/download'), ARRAY_FILTER_USE_BOTH);
        $this->assertSame([], $broken);
        $this->assertGreaterThan(25, count($seen));
    }

    public function test_language_direction_and_alternates(): void
    {
        $this->importReference();
        $this->get('/ar/about')->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('hreflang="en" href="'.url('/en/about').'"', false)
            ->assertSee('rel="canonical" href="'.url('/ar/about').'"', false)
            ->assertHeader('Content-Language', 'ar');
        $this->get('/en/projects/dar-el-jamila')->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee('"@type":"CreativeWork"', false)->assertSee('og:image', false);
        $this->get('/en')->assertSee('"@type":"Person"', false);
    }

    public function test_root_redirects_by_browser_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertRedirect('/en');
        $this->get('/', ['Accept-Language' => 'ar-SA,ar;q=0.9,en;q=0.5'])->assertRedirect('/ar');
        $this->get('/', ['Accept-Language' => 'fr-FR'])->assertRedirect('/ar');
    }

    public function test_sitemap_and_robots(): void
    {
        $this->importReference();
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertSame(2 * (6 + 9), substr_count($xml, '<url>'));
        $this->assertStringContainsString('hreflang="ar"', $xml);
        $this->assertStringNotContainsString('/preview', $xml);
        // not production: search engines are kept out
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    }

    public function test_unknown_pages_show_the_branded_404_in_the_right_language(): void
    {
        $this->get('/ar/does-not-exist')->assertNotFound()->assertSee('الصفحة غير موجودة');
        $this->get('/en/projects/nope')->assertNotFound()->assertSee('Page not found');
    }
}
