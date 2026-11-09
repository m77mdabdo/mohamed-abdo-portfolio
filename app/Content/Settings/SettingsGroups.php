<?php

namespace App\Content\Settings;

use App\Content\Fields\Field;

/**
 * Typed settings groups. Each group is edited on its own dashboard screen, saved as a draft and
 * published like a page. Security settings (mail passwords, app keys, debug) are NOT here: they stay in .env.
 */
final class SettingsGroups
{
    public const ACCENTS = ['#C15B46', '#B4533F', '#D06A52', '#A84A37'];

    public const SIGNALS = ['#49DDE4', '#3CC7CE', '#6BE6EC', '#2FB8BF'];

    /** @return array<string, string> group => label */
    public static function labels(): array
    {
        return [
            'identity' => __('Identity & social links'),
            'header' => __('Header & menu labels'),
            'footer' => __('Footer'),
            'seo' => __('SEO & sharing'),
            'appearance' => __('Appearance & motion'),
            'contact' => __('Contact inbox'),
            'cv' => __('CV file'),
            'project_labels' => __('Project page labels'),
            'ui' => __('Interface texts'),
        ];
    }

    /** @return array<int, Field> */
    public static function fields(string $group): array
    {
        return match ($group) {
            'identity' => [
                Field::text('name', 'Name')->translatable()->required()->max(80),
                Field::text('role', 'Job title')->translatable()->max(80),
                Field::text('city', 'City and country')->translatable()->max(80),
                Field::text('city_short', 'City (short, for the time tag)')->translatable()->max(40),
                Field::text('timezone', 'Time zone (e.g. Asia/Riyadh)')->max(40)->default('Asia/Riyadh'),
                Field::text('timezone_label', 'Time zone label (e.g. GMT+3)')->max(12)->default('GMT+3'),
                Field::text('availability', 'Availability badge')->translatable()->max(90),
                Field::email('email', 'Public e-mail'),
                Field::media('portrait', 'Portrait (cut-out, transparent background)'),
                Field::items('social', 'Social links', [
                    Field::text('label', 'Name (e.g. GitHub)')->max(30),
                    Field::url('url', 'Address (https://…)'),
                ], 8),
            ],
            'header' => [
                Field::text('logo_tagline', 'Small text beside the logo')->translatable()->max(40),
                Field::text('menu_label', 'Menu button')->translatable()->max(20),
                Field::text('close_label', 'Close button')->translatable()->max(20),
                Field::text('skip_label', 'Skip to content (keyboard users)')->translatable()->max(40),
                Field::text('switch_label', 'Language switch (accessible name)')->translatable()->max(40),
            ],
            'footer' => [
                Field::text('cta', 'Line above the e-mail')->translatable()->max(80),
                Field::text('nav_title', 'Column: pages')->translatable()->max(40),
                Field::text('live_title', 'Column: live projects')->translatable()->max(40),
                Field::toggle('show_live', 'Show the live projects column'),
                Field::text('social_title', 'Column: social links')->translatable()->max(40),
                Field::text('time_title', 'Column: local time')->translatable()->max(40),
                Field::text('giant_name', 'Giant name at the bottom')->translatable()->max(30),
                Field::text('edition', 'Year text (e.g. 2026 ©)')->translatable()->max(30),
                Field::text('rights', 'Rights line')->translatable()->max(120),
                Field::text('top_label', 'Back to top')->translatable()->max(30),
            ],
            'seo' => [
                Field::text('site_title', 'Site title (browser tab)')->translatable()->required()->max(70),
                Field::textarea('description', 'Site description (search results)')->translatable()->max(170),
                Field::media('share_image', 'Sharing image (1200×630)'),
                Field::media('favicon', 'Site icon (square PNG, at least 192px)'),
                Field::toggle('indexable', 'Allow search engines to index the site'),
                Field::text('job_title', 'Job title for search engines (structured data)')->max(80),
            ],
            'appearance' => [
                Field::color('accent', 'Accent colour (brick)', self::ACCENTS),
                Field::color('signal', 'Signal colour (turquoise)', self::SIGNALS),
                Field::select('motion', 'Motion level', ['full' => 'Full cinematic motion', 'calm' => 'Calm (fewer effects)', 'off' => 'Off (static site)'], 'full'),
                Field::toggle('scene_3d', '3D scene in the hero'),
                Field::toggle('smooth_scroll', 'Smooth scrolling'),
                Field::toggle('custom_cursor', 'Custom cursor and spotlight (mouse only)'),
                Field::toggle('grain', 'Film grain'),
                Field::toggle('loader', 'Opening greeting screen'),
                Field::items('greetings', 'Greetings on the opening screen', [
                    Field::text('text', 'Greeting')->max(30),
                ], 12),
            ],
            'contact' => [
                Field::email('recipient', 'Send new-message notices to'),
                Field::toggle('notify', 'E-mail me when a message arrives (needs a working mail server)'),
            ],
            'cv' => [
                Field::media('file', 'CV (PDF)', 'document'),
            ],
            'project_labels' => [
                Field::text('back', 'Back to projects')->translatable()->max(40),
                Field::text('next', 'Next project')->translatable()->max(40),
                Field::text('role', 'Label: role')->translatable()->max(30),
                Field::text('role_default', 'Default role (when a project has none)')->translatable()->max(60),
                Field::text('status', 'Label: status')->translatable()->max(30),
                Field::text('client', 'Label: client')->translatable()->max(30),
                Field::text('languages', 'Label: languages')->translatable()->max(30),
                Field::text('languages_default', 'Default languages (when a project has none)')->translatable()->max(60),
                Field::text('stack', 'Label: stack')->translatable()->max(30),
                Field::text('year', 'Label: year')->translatable()->max(30),
                Field::text('challenge', 'Heading: the brief')->translatable()->max(40),
                Field::text('solution', 'Heading: the solution')->translatable()->max(40),
                Field::text('features', 'Heading: highlights')->translatable()->max(40),
                Field::text('shots', 'Heading: screenshots')->translatable()->max(40),
                Field::text('visit', 'Button: live site')->translatable()->max(40),
                Field::text('repo', 'Button: source code')->translatable()->max(40),
            ],
            default => [],
        };
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
