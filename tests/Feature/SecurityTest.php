<?php

namespace Tests\Feature;

use App\Content\Fields\FieldSchema;
use App\Content\Pages\PageDraftWriter;
use App\Content\Publisher;
use App\Content\Subjects\PageSubject;
use App\Support\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 8. Content can never inject markup or scripts; headers and previews protect drafts and visitors. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_text_is_escaped_everywhere(): void
    {
        $this->importReference();
        $page = $this->home()->load('sections');
        $sections = $page->sections->map(function ($s) {
            $data = $s->data;
            if ($s->type === 'hero') {
                $data['title'] = ['en' => '<script>alert(1)</script> *<img src=x onerror=alert(2)>*', 'ar' => 'عنوان'];
                $data['subtitle'] = ['en' => '"><svg onload=alert(3)>', 'ar' => 'x'];
                $data['buttons'] = [['label' => ['en' => 'Go', 'ar' => 'اذهب'], 'style' => 'solid', 'link' => ['type' => 'url', 'url' => 'javascript:alert(4)']]];
            }

            return ['uid' => $s->uid, 'type' => $s->type, 'anchor' => $s->anchor, 'preset' => $s->preset, 'visible' => true, 'data' => $data];
        })->all();
        app(PageDraftWriter::class)->save($page, [], $sections, $page->lock_version);
        app(Publisher::class)->publish(new PageSubject($page->refresh()));

        $html = $this->get('/en')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_url_allow_list(): void
    {
        foreach (['https://example.com/a?b=1', 'http://x.dev', 'mailto:a@b.co', 'tel:+966500000000', '/en/work', '#contact', ''] as $ok) {
            $this->assertTrue(SafeUrl::isAllowed($ok), $ok);
        }
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,<b>', '//evil.com', 'vbscript:x', "java\nscript:x", '/\\evil.com', 'https://'] as $bad) {
            $this->assertFalse(SafeUrl::isAllowed($bad), $bad);
        }
        $clean = FieldSchema::cleanLink(['type' => 'url', 'url' => 'javascript:alert(1)']);
        $this->assertSame('', $clean['url']);
    }

    public function test_security_headers_and_csp(): void
    {
        $this->importReference();
        $response = $this->get('/en');
        $response->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        // every inline script carries the nonce
        preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>/', $response->getContent(), $scripts);
        foreach ($scripts[0] as $tag) {
            $this->assertStringContainsString('nonce=', $tag);
        }
    }

    public function test_preview_requires_signature_and_staff(): void
    {
        $this->importReference();
        $this->get('/preview/en')->assertRedirect('/admin/login');
        $this->actingAs($this->editor())->get('/preview/en')->assertForbidden(); // no signature
    }
}
