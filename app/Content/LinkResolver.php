<?php

namespace App\Content;

use App\Support\SafeUrl;

/**
 * Turns a stored link ({type, page, anchor, project, url, new_tab}) into an href for one language.
 * Links to pages and projects use ids, so renaming a slug never breaks a button or menu item.
 * Returns null when the target no longer exists or is hidden — the button is then simply not shown.
 */
final class LinkResolver
{
    public function __construct(private readonly Site $site) {}

    /** @return array{href: string, external: bool, new_tab: bool, download: bool}|null */
    public function resolve(array $link, string $locale): ?array
    {
        $type = $link['type'] ?? 'none';
        $href = match ($type) {
            'page' => $this->pageUrl($link['page'] ?? null, $locale, ''),
            'section' => $this->pageUrl($link['page'] ?? null, $locale, (string) ($link['anchor'] ?? '')),
            'project' => $this->projectUrl($link['project'] ?? null, $locale),
            'email' => $this->emailUrl(),
            'cv' => route('cv.download', ['locale' => $locale]),
            'url' => SafeUrl::isAllowed($link['url'] ?? '') && ($link['url'] ?? '') !== '' ? $link['url'] : null,
            default => null,
        };
        if ($href === null) {
            return null;
        }
        $external = $type === 'url' && SafeUrl::isExternal($href);

        return [
            'href' => $href,
            'external' => $external,
            'new_tab' => (bool) ($link['new_tab'] ?? false) || $external,
            'download' => $type === 'cv',
        ];
    }

    private function pageUrl(mixed $pageId, string $locale, string $anchor): ?string
    {
        $anchor = ltrim($anchor, '#');
        if ($pageId === null || $pageId === '') {
            return $anchor !== '' ? '#'.$anchor : null;
        }
        $page = $this->site->pageById((int) $pageId);
        if ($page === null) {
            return null;
        }
        $url = $this->site->pageUrl($page, $locale);

        return $anchor !== '' ? $url.'#'.$anchor : $url;
    }

    private function projectUrl(mixed $projectId, string $locale): ?string
    {
        $project = is_numeric($projectId) ? $this->site->projectById((int) $projectId) : null;

        return $project ? route('projects.show', ['locale' => $locale, 'slug' => $project['slug']]) : null;
    }

    private function emailUrl(): ?string
    {
        $email = $this->site->settings('identity')['email'] ?? '';

        return $email ? 'mailto:'.$email : null;
    }
}
