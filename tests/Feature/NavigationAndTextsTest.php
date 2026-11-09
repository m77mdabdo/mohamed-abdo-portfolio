<?php

namespace Tests\Feature;

use App\Content\Menus\MenuTree;
use App\Content\Publisher;
use App\Content\Subjects\MenuSubject;
use App\Content\Subjects\SettingsSubject;
use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** 5. Menus, header, footer and interface texts edited in the dashboard reach the site after publishing. */
class NavigationAndTextsTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_header_footer_and_ui_texts(): void
    {
        $this->importReference();
        $menu = Menu::query()->where('key', 'header')->first();
        $items = MenuTree::fromModel($menu);
        $items[0]['label'] = ['en' => 'Case files', 'ar' => 'ملفات الأعمال'];
        $items[] = ['label' => ['en' => 'GitHub', 'ar' => 'جيت هب'], 'link' => ['type' => 'url', 'url' => 'https://github.com/m77mdabdo'], 'visible' => true, 'children' => []];
        $items[1]['visible'] = false; // hide "About"
        MenuTree::save($menu, $items);

        $footer = SiteSetting::query()->where('group', 'footer')->first();
        $footer->data = array_merge($footer->data, ['rights' => ['en' => 'Built in Riyadh', 'ar' => 'صُنع في الرياض']]);
        $footer->save();
        $header = SiteSetting::query()->where('group', 'header')->first();
        $header->data = array_merge($header->data, ['logo_tagline' => ['en' => 'Laravel engineer', 'ar' => 'مهندس Laravel']]);
        $header->save();
        SiteSetting::query()->where('group', 'ui')->update(['data' => json_encode(['form.sending' => ['en' => 'Sending now', 'ar' => 'يرسل الآن']])]);

        $this->get('/en')->assertDontSee('Case files')->assertDontSee('Built in Riyadh'); // drafts only

        $publisher = app(Publisher::class);
        $publisher->publish(new MenuSubject($menu));
        foreach (['footer', 'header', 'ui'] as $group) {
            $publisher->publish(new SettingsSubject($group));
        }

        $html = $this->get('/en')->getContent();
        $nav = Str::betweenFirst($html, '<nav class="links"', '</nav>');
        $this->assertStringContainsString('Case files', $nav);
        $this->assertStringContainsString('https://github.com/m77mdabdo', $nav);
        $this->assertStringNotContainsString('>About<', $nav);
        $this->assertStringContainsString('Built in Riyadh', $html);
        $this->assertStringContainsString('Laravel engineer', $html);
        $this->assertStringContainsString('Sending now', $html);
        $this->get('/ar')->assertSee('صُنع في الرياض')->assertSee('يرسل الآن');
    }

    public function test_menu_depth_is_limited_and_cycles_are_detected(): void
    {
        $this->importReference();
        $menu = Menu::query()->where('key', 'footer')->first();
        $grandchild = ['label' => ['en' => 'c', 'ar' => 'c'], 'link' => ['type' => 'url', 'url' => '/x'], 'children' => []];
        $child = ['label' => ['en' => 'b', 'ar' => 'b'], 'link' => ['type' => 'url', 'url' => '/y'], 'children' => [$grandchild]];
        try {
            MenuTree::save($menu, [['label' => ['en' => 'a', 'ar' => 'a'], 'link' => ['type' => 'url', 'url' => '/z'], 'children' => [$child]]]);
            $this->fail('three levels accepted');
        } catch (\InvalidArgumentException) {
            $this->assertGreaterThan(0, $menu->items()->count()); // previous menu untouched
        }

        MenuTree::save($menu, [['label' => ['en' => 'a', 'ar' => 'a'], 'link' => ['type' => 'url', 'url' => '/z'], 'children' => [['label' => ['en' => 'b', 'ar' => 'b'], 'link' => ['type' => 'url', 'url' => '/y'], 'children' => []]]]]);
        [$parent, $kid] = $menu->items()->get()->all();
        $this->assertTrue(MenuTree::wouldCreateCycle($parent, $kid->id));
        $this->assertTrue(MenuTree::wouldCreateCycle($parent, $parent->id));
        $this->assertFalse(MenuTree::wouldCreateCycle($kid, null));
    }

    public function test_switching_language_keeps_the_same_page_and_no_raw_keys_leak(): void
    {
        $this->importReference();
        $html = $this->get('/en/projects/masar')->getContent();
        $this->assertStringContainsString('href="http://127.0.0.1:8020/ar/projects/masar"', str_replace(config('app.url'), 'http://127.0.0.1:8020', $html));
        foreach (['/en', '/ar', '/en/resume', '/ar/contact', '/ar/projects/masar'] as $url) {
            $body = $this->get($url)->getContent();
            $this->assertDoesNotMatchRegularExpression('/\b(form|a11y|errors|project|preview|cv)\.[a-z_]+\b/', strip_tags($body), "raw key on {$url}");
        }
    }
}
