<?php

namespace App\Content;

use App\Support\RichTitle;
use App\Support\Tr;

/**
 * Title, description, canonical, sharing image and structured data for a page or project.
 * Structured data uses only facts that exist in the content (name, job title, links, project data).
 */
final class SeoBuilder
{
    public function __construct(private readonly Site $site) {}

    public function forPage(array $page, string $locale, string $canonical): array
    {
        $seo = $this->site->settings('seo');
        $siteTitle = Tr::pick($seo['site_title'] ?? null, $locale);
        $own = $page['seo'] ?? [];
        $isHome = ($page['key'] ?? null) === 'home';
        $title = Tr::pick($own['title'] ?? null, $locale) ?: ($isHome ? $siteTitle : Tr::pick($page['title'], $locale).' — '.$siteTitle);

        return [
            'title' => $title,
            'description' => Tr::pick($own['description'] ?? null, $locale) ?: Tr::pick($seo['description'] ?? null, $locale),
            'canonical' => $canonical,
            'image' => $this->site->media($own['image'] ?? null) ?? $this->site->media($seo['share_image'] ?? null),
            'type' => 'website',
            'noindex' => (bool) ($own['noindex'] ?? false) || ! ($seo['indexable'] ?? true),
            'jsonld' => $isHome ? $this->personGraph($locale, $canonical) : null,
        ];
    }

    public function forProject(array $project, string $locale, string $canonical): array
    {
        $seo = $this->site->settings('seo');
        $own = $project['seo'] ?? [];
        $title = Tr::pick($own['title'] ?? null, $locale) ?: Tr::pick($project['title'], $locale).' — '.Tr::pick($project['kind'], $locale);
        $description = Tr::pick($own['description'] ?? null, $locale) ?: Tr::pick($project['summary'], $locale);
        $identity = $this->site->settings('identity');

        return [
            'title' => $title.' · '.Tr::pick($identity['name'] ?? null, $locale),
            'description' => mb_substr($description, 0, 300),
            'canonical' => $canonical,
            'image' => $this->site->media($project['cover_media_id'] ?? null) ?? $this->site->media($seo['share_image'] ?? null),
            'type' => 'article',
            'noindex' => ! ($seo['indexable'] ?? true),
            'jsonld' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'CreativeWork',
                'name' => Tr::pick($project['title'], $locale),
                'description' => $description,
                'url' => $canonical,
                'inLanguage' => $locale,
                'dateCreated' => preg_match('/^\d{4}$/', (string) $project['year']) ? $project['year'] : null,
                'creator' => ['@type' => 'Person', 'name' => Tr::pick($identity['name'] ?? null, $locale)],
                'keywords' => implode(', ', array_column($project['stack'] ?? [], 'name')) ?: null,
                'sameAs' => array_values(array_filter([$project['live_url'] ?? null, $project['repo_url'] ?? null])) ?: null,
            ]),
        ];
    }

    private function personGraph(string $locale, string $url): array
    {
        $identity = $this->site->settings('identity');
        $seo = $this->site->settings('seo');
        $portrait = $this->site->media($identity['portrait'] ?? null);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => Tr::pick($identity['name'] ?? null, $locale),
            'jobTitle' => ($seo['job_title'] ?? '') ?: RichTitle::plain(Tr::pick($identity['role'] ?? null, $locale)),
            'url' => $url,
            'email' => ($identity['email'] ?? '') ? 'mailto:'.$identity['email'] : null,
            'image' => $portrait?->url(),
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => Tr::pick($identity['city_short'] ?? null, $locale)],
            'sameAs' => array_values(array_filter(array_column($identity['social'] ?? [], 'url'))) ?: null,
        ]);
    }
}
