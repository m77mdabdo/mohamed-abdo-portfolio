# محمد عبده — Portfolio

Bilingual (Arabic / English) personal portfolio of Mohamed Abdo, with a full content dashboard:
every page, section, project, list, menu, text and setting is edited from `/admin`, saved as a draft,
previewed on a private link and published (with a restorable history).

> Work in progress — full documentation (setup, database diagram, owner guide in Arabic, backup/restore,
> content inventory and test results) is added as the project is completed.

## Stack

| Layer | Version |
| --- | --- |
| PHP | 8.5 (8.3+ supported) |
| Laravel | 13.40 |
| Filament (dashboard) | 5.11 · Livewire 4.4 |
| Database | MariaDB 10.4+ / MySQL 8 (SQLite for tests) |
| Front end | Blade, TypeScript, Vite 8, GSAP 3.15, Three.js 0.169 (lazy), Lenis 1.3 |

## Changelog

- Content model, draft/publish/restore engine, section library (17 typed section types), public site
  (home, work, about, services, résumé, contact, project pages), SEO (hreflang, sitemap, structured data),
  idempotent import of the original content and images with WebP/AVIF optimisation.
