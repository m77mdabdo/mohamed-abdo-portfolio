<?php

namespace App\Content\Import;

use App\Models\Page;

/**
 * Pages, sections and menus of the original design. Texts are the original ones; the inner pages
 * (work, about, services, résumé, contact) reuse the same sections and shared lists as the home page.
 * Links refer to pages as "@key" and are turned into ids on import.
 */
final class ReferencePages
{
    private static function b(string $en, string $ar): array
    {
        return ['en' => $en, 'ar' => $ar];
    }

    private static function to(string $page, string $anchor = ''): array
    {
        return ['type' => $anchor === '' ? 'page' : 'section', 'page' => '@'.$page, 'anchor' => $anchor, 'project' => null, 'url' => '', 'new_tab' => false];
    }

    /** @return array<int, array<string, mixed>> */
    public static function definitions(): array
    {
        $b = self::b(...);

        $hero = ['type' => 'hero', 'data' => [
            'badge' => $b('Software Engineer · Laravel · Riyadh', 'مهندس برمجيات · Laravel · الرياض'),
            'title' => $b('I design and build web products that *elevate brands*.', "أصمّم وأطوّر منتجات ويب\n*ترتقي بعلامتك*"),
            'subtitle' => $b('Full-stack Laravel engineer based in Riyadh. Corporate websites, e-commerce and custom platforms — from data architecture to launch, with dashboards your team can run on its own.', 'مهندس Full-Stack متخصص في Laravel من الرياض. مواقع شركات، ومتاجر إلكترونية، ومنصات مخصصة — من هندسة البيانات حتى الإطلاق، مع لوحات تحكم يديرها فريقك باستقلالية.'),
            'buttons' => [
                ['label' => $b('View my work', 'استعرض أعمالي'), 'style' => 'solid', 'link' => self::to('work')],
                ['label' => $b('Résumé', 'السيرة الذاتية'), 'style' => 'ghost', 'link' => self::to('cv')],
            ],
            'scroll_label' => $b('Scroll', 'مرّر'),
            'show_scene' => true,
            'show_clock' => true,
        ]];
        $statement = ['type' => 'statement', 'data' => [
            'eyebrow' => $b('Philosophy', 'الفلسفة'),
            'line1' => $b('Good software is invisible.', 'البرمجة الجيدة لا تُرى،'),
            'line2' => $b('You feel it in the speed,', 'بل تُلمَس في السرعة،'),
            'line3' => $b('the clarity and the control.', 'والوضوح، وسهولة الإدارة.'),
        ]];
        $about = ['type' => 'about', 'data' => [
            'eyebrow' => $b('About', 'عنّي'),
            'lead' => $b("I'm Mohamed Abdo, a software engineer focused on Laravel. I own the whole product: the architecture underneath, the interface on top, and how it performs long after launch.", 'أنا محمد عبده، مهندس برمجيات متخصص في Laravel. أتولّى المنتج كاملاً: البنية التي يقوم عليها، والواجهة التي يراها المستخدم، وأداءه بعد الإطلاق بوقت طويل.'),
            'body' => $b('I work as a software engineer at Creative Mark in Riyadh, a consultancy that helps investors establish and grow companies in Saudi Arabia. Beyond that role I have delivered platforms for fashion retail, clinical nutrition, business media, photography, natural stone and professional services — each with automated tests, a production deployment and a dashboard the client runs without me.', 'أعمل مهندس برمجيات في كرييتف مارك بالرياض، الشركة الاستشارية التي تساعد المستثمرين على تأسيس شركاتهم وتنميتها في المملكة. وإلى جانب ذلك نفّذت منصات في تجارة الأزياء، والتغذية العلاجية، والإعلام الاقتصادي، والتصوير، والأحجار الطبيعية، والخدمات المهنية — لكلٍّ منها اختبارات آلية، ونشر فعلي على الخوادم، ولوحة تحكم يديرها العميل باستقلالية.'),
            'show_cube' => true,
            'show_stats' => false,
        ]];
        $reel = ['type' => 'work_reel', 'data' => [
            'eyebrow' => $b('Selected work', 'أعمال مختارة'),
            'title' => $b('Built end to end, from idea to launch', 'من الفكرة إلى الإطلاق'),
            'selection' => 'all',
            'cursor_label' => $b('View', 'عرض'),
        ]];
        $index = fn (bool $filters) => ['type' => 'project_index', 'data' => [
            'title' => $b('All projects', 'كل المشاريع'),
            'type_label' => $b('Type', 'النوع'),
            'status_label' => $b('Status', 'الحالة'),
            'show_filters' => $filters,
            'all_label' => $b('All', 'الكل'),
            'cursor_label' => $b('View', 'عرض'),
        ]];
        $services = ['type' => 'services', 'data' => ['eyebrow' => $b('Services', 'الخدمات')]];
        $cv = ['type' => 'cv', 'data' => [
            'eyebrow' => $b('Résumé', 'السيرة الذاتية'),
            'title' => $b('Experience & skills', 'الخبرة والمهارات'),
            'view_label' => $b('View CV', 'عرض السيرة الذاتية'),
            'download_label' => $b('Download CV (PDF)', 'تحميل السيرة الذاتية (PDF)'),
            'pending_label' => $b('CV available on request', 'السيرة الذاتية متاحة عند الطلب'),
            'pending_note' => $b('Email me and I will send the latest version.', 'راسلني وسأرسل لك أحدث نسخة.'),
            'experience_title' => $b('Experience', 'الخبرة'),
            'languages_title' => $b('Languages', 'اللغات'),
            'now_label' => $b('Now', 'الآن'),
            'show_card' => true,
        ]];
        $band = ['type' => 'skills_band', 'data' => [
            'eyebrow' => $b('Skills', 'المهارات'),
            'title' => $b('The tools behind the work', 'الأدوات التي أبني بها'),
        ]];
        $quotes = ['type' => 'testimonials', 'data' => ['eyebrow' => $b('What clients say', 'آراء العملاء')]];
        $process = ['type' => 'process', 'data' => ['eyebrow' => $b('Process', 'منهجية العمل')]];
        $contact = ['type' => 'contact', 'data' => [
            'eyebrow' => $b('Start a project', 'ابدأ مشروعك'),
            'title' => $b("Let's build something that lasts.", 'لنبنِ شيئاً يدوم.'),
            'circle_label' => $b("Let's talk", 'لنتحدث'),
            'copy_label' => $b('Copy email', 'نسخ البريد'),
            'copied_label' => $b('Copied', 'تم النسخ'),
            'show_form' => true,
            'name_label' => $b('Your name', 'الاسم'),
            'email_label' => $b('Email', 'البريد الإلكتروني'),
            'type_label' => $b('Project type', 'نوع المشروع'),
            'message_label' => $b('Project details', 'تفاصيل المشروع'),
            'project_types' => [
                ['label' => $b('Corporate website', 'موقع شركة')],
                ['label' => $b('E-commerce store', 'متجر إلكتروني')],
                ['label' => $b('Dashboard / CMS', 'لوحة تحكم / إدارة محتوى')],
                ['label' => $b('Custom platform', 'منصة مخصصة')],
            ],
            'submit_label' => $b('Send message', 'أرسل الرسالة'),
            'note' => $b('Your message is saved securely and I reply by e-mail.', 'تُحفظ رسالتك بأمان وأرد عليك عبر البريد.'),
            'success_message' => $b('Thank you — your message has been received. I will reply soon.', 'شكراً لك — وصلت رسالتك وسأرد عليك قريباً.'),
            'error_message' => $b('Something went wrong and the message was not sent. Please try again or e-mail me directly.', 'حدث خطأ ولم تُرسل الرسالة. حاول مرة أخرى أو راسلني مباشرة عبر البريد.'),
        ]];
        $cta = ['type' => 'cta_banner', 'preset' => 'brick', 'anchor' => 'start', 'data' => [
            'title' => $b('Have a project in mind?', 'عندك مشروع في بالك؟'),
            'buttons' => [['label' => $b("Let's talk", 'لنتحدث'), 'style' => 'solid', 'link' => self::to('contact')]],
        ]];
        $header = fn (array $eyebrow, array $title) => ['type' => 'page_header', 'preset' => 'glow', 'anchor' => 'top', 'data' => ['eyebrow' => $eyebrow, 'title' => $title]];

        return [
            ['key' => 'home', 'slug' => 'home', 'title' => $b('Home', 'الرئيسية'), 'sections' => [$hero, $statement, $about, $reel, $index(false), $services, $cv, $band, $quotes, $process, $contact]],
            ['key' => 'work', 'slug' => 'work', 'title' => $b('Work', 'الأعمال'), 'sections' => [
                $header($b('Selected work', 'أعمال مختارة'), $b('Built end to end, from idea to launch', 'من الفكرة إلى الإطلاق')),
                ['type' => 'work_reel', 'data' => ['selection' => 'all', 'cursor_label' => $b('View', 'عرض')]], $index(true), $cta,
            ]],
            ['key' => 'about', 'slug' => 'about', 'title' => $b('About', 'عنّي'), 'sections' => [
                $header($b('About', 'عنّي'), $b('Mohamed Abdo', 'محمد عبده')),
                $about, $statement, $process, $cta,
            ]],
            ['key' => 'services', 'slug' => 'services', 'title' => $b('Services', 'الخدمات'), 'sections' => [
                $header($b('Services', 'الخدمات'), $b('From idea to launch', 'من الفكرة إلى الإطلاق')),
                $services, $process, $cta,
            ]],
            ['key' => 'cv', 'slug' => 'resume', 'title' => $b('Résumé', 'السيرة الذاتية'), 'sections' => [
                $cv, $band, ['type' => 'skill_groups', 'data' => ['eyebrow' => $b('Skills', 'المهارات')]], $cta,
            ]],
            ['key' => 'contact', 'slug' => 'contact', 'title' => $b('Contact', 'تواصل'), 'sections' => [$contact]],
        ];
    }

    /** @return array<string, array{name: string, items: array}> */
    public static function menus(): array
    {
        $b = self::b(...);
        $item = fn (array $label, array $link) => ['label' => $label, 'link' => $link, 'visible' => true, 'children' => []];

        // Header and drawer open the standalone pages; the home page keeps every chapter in one long scroll.
        $chapters = [
            $item($b('Work', 'الأعمال'), self::to('work')),
            $item($b('About', 'عنّي'), self::to('about')),
            $item($b('Services', 'الخدمات'), self::to('services')),
            $item($b('Résumé', 'السيرة الذاتية'), self::to('cv')),
            $item($b('Contact', 'تواصل'), self::to('contact')),
        ];

        return [
            'header' => ['name' => 'Header', 'items' => $chapters],
            'drawer' => ['name' => 'Mobile & floating menu', 'items' => $chapters],
            // The footer links to the standalone pages.
            'footer' => ['name' => 'Footer', 'items' => [
                $item($b('Work', 'الأعمال'), self::to('work')),
                $item($b('About', 'عنّي'), self::to('about')),
                $item($b('Services', 'الخدمات'), self::to('services')),
                $item($b('Résumé', 'السيرة الذاتية'), self::to('cv')),
                $item($b('Contact', 'تواصل'), self::to('contact')),
            ]],
        ];
    }

    /**
     * Replaces "@key" page references with page ids, recursively.
     *
     * @param  array<string, Page>  $pages
     */
    public static function resolveLinks(array $data, array $pages): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::resolveLinks($v, $pages);
            } elseif ($k === 'page' && is_string($v) && str_starts_with($v, '@')) {
                $data[$k] = $pages[substr($v, 1)]->id ?? null;
            }
        }

        return $data;
    }
}
