# Verification — commands and actual results (2026-11-09, owner's Mac)

Environment: macOS, PHP 8.5.2 (Homebrew), XAMPP MariaDB 10.4.28, Node 24.22, Chromium 1248 (Playwright 1.64.1),
local server `php artisan serve` (development mode, `APP_DEBUG=true`, no HTTP/2, no compression proxy).

| Check | Command | Result |
| --- | --- | --- |
| PHP tests (SQLite) | `composer test` | 46 tests: 45 passed, 1 skipped (backup test needs MariaDB) |
| PHP tests (MariaDB test DB) | `composer test:mariadb` | 46 passed, 288 assertions |
| Static analysis | `vendor/bin/phpstan analyse` (Larastan level 5) | 0 errors |
| Code style | `vendor/bin/pint --test` | passed |
| TypeScript | `npm run typecheck` | 0 errors |
| Production build | `npm run build` | site JS 156 KB (57 KB gzip), CSS 50 KB (12 KB gzip), 3D scene 480 KB (121 KB gzip) loaded on demand |
| Browser | `BASE=… npx playwright test --trace off` | 20 passed |
| Import twice | `php artisan db:seed` ×2 | second run: "nothing new"; counts unchanged |
| Backup round-trip | `php artisan portfolio:backup` + `portfolio:restore` | 6 pages, 28 sections, 9 projects, 62 files restored into a temporary database, then dropped |

Browser checks cover: 360 / 390 / 768 / 1024 / 1440 / 1920 px in Arabic and English on all public pages
(no horizontal overflow, no console errors, correct `dir`), scrolling every page with full motion at 390 and
1440 px, reduced motion (no 3D, no loader, text fully visible), no WebGL (static orb), JavaScript disabled
(content and links work), keyboard (skip link, drawer focus trap, Escape returns focus), three rounds of
back/forward + language switching (one runtime per document, no errors), contact form double-click (one request).

## Lab Web Vitals (`node tests/perf/vitals.cjs`)

Playwright + Chrome DevTools Protocol: Moto G4 emulation, 150 ms latency, 1.6 Mbps down, 4× CPU slowdown, cache
disabled. "Interaction" is the longest Event Timing entry while opening/closing the menu and scrolling — a lab
indicator only; real INP needs field data from visitors, which a local site does not have.
Lighthouse results are in the next section.

| Page | Mode | LCP | CLS | Interaction | Transfer |
| --- | --- | --- | --- | --- | --- |
| /ar | mobile (Moto G4, slow 4G, 4x CPU) | 2124 ms | 0.008 | 80 ms | 1267 KB |
| /en | mobile (Moto G4, slow 4G, 4x CPU) | 1912 ms | 0.008 | 72 ms | 1251 KB |
| /en/projects/creative-mark | mobile (Moto G4, slow 4G, 4x CPU) | 1384 ms | 0 | 56 ms | 634 KB |
| /ar/work | mobile (Moto G4, slow 4G, 4x CPU) | 2004 ms | 0 | 48 ms | 619 KB |
| /en/about | mobile (Moto G4, slow 4G, 4x CPU) | 1188 ms | 0.057 | 64 ms | 477 KB |
| /ar | desktop 1440 | 376 ms | 0.002 | 160 ms | 2409 KB |
| /en | desktop 1440 | 284 ms | 0.001 | 176 ms | 2393 KB |
| /en/projects/creative-mark | desktop 1440 | 148 ms | 0 | 56 ms | 829 KB |
| /ar/work | desktop 1440 | 264 ms | 0 | 64 ms | 1803 KB |
| /en/about | desktop 1440 | 172 ms | 0.016 | 64 ms | 494 KB |

Targets (LCP ≤ 2.5 s, CLS ≤ 0.1, interaction ≤ 200 ms) are met in these lab conditions. Before preloading the
first-screen fonts, Arabic pages showed CLS 0.18–0.23 on desktop; preloading fixed it. A reel visible at load
(work page) used to shift by 0.48 when it pinned; pinning now uses transforms (CLS 0).

## Visual comparison with the original

Screenshots of the original standalone build and of this site at 1440 px (Arabic) and 390 px (English), same
state (reduced motion), were compared: layout, typography, colours, portrait, highlight, buttons, header and
time tag match. Intentional differences: with reduced motion the original still drew one 3D frame, this site
shows the static orb; project details are pages instead of an overlay; the contact button reads "Send
message" because the form now really sends; Arabic text in monospace labels is no longer letter-spaced (it
broke the joining of Arabic letters).

## Lighthouse 13.6 (2026-11-14)

Run from a scratch folder (not a project dependency) against production mode (`APP_ENV=production`,
`APP_DEBUG=false`) behind a local Apache 2.4 with the project's `public/.htaccess`: gzip and browser caching,
like a hosting server. Lighthouse's default simulated mobile throttling (slow 4G, 4× CPU); scores out of 100.

| Page | Mode | Performance | Accessibility | Best practices | SEO | LCP | CLS | TBT |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| /ar | mobile | 87 | 97 | 100 | 100 | 3.2 s | 0.007 | 230 ms |
| /ar | desktop | 95 | 97 | 100 | 100 | 1.3 s | 0.005 | 30 ms |
| /en | mobile | 79 | 97 | 100 | 100 | 4.6 s | 0.003 | 210 ms |
| /en | desktop | 98 | 97 | 100 | 100 | 0.9 s | 0.005 | 20 ms |
| /ar/work | mobile | 95 | 98 | 100 | 100 | 2.9 s | 0 | 20 ms |
| /ar/work | desktop | 98 | 98 | 100 | 100 | 0.2 s | 0.099 | 0 ms |
| /en/projects/cashier | mobile | 100 | 96 | 100 | 100 | 0.7 s | 0 | 0 ms |
| /en/projects/cashier | desktop | 100 | 96 | 100 | 100 | 0.6 s | 0 | 0 ms |

The home page's mobile LCP element is the portrait, which the opening animation reveals; measured
(unthrottled) its render delay is about 190 ms, the simulated figure adds the 3D scene's script cost on a slow CPU.
The remaining accessibility points are the statement words that start dim and light up while scrolling
(Lighthouse samples them mid-animation). On `php artisan serve` without compression the same pages score lower
(home mobile 58–75), and in development mode SEO shows 69 because `robots.txt` blocks indexing outside production.

## Dashboard, sign-in and restore (2026-11-14)

| Check | How | Result |
| --- | --- | --- |
| Sign-in code | Playwright: owner and editor sign in, code read from the log mailer | wrong code refused, right code signs in, too many tries are paused |
| Menus | move an item, edit a label, save draft, preview, add link, discard | live site unchanged by the draft, preview shows it, discard restores order and text |
| Interface texts | edit, save draft, Arabic preview, history, discard | as expected |
| Media | upload PNG, edit description, where used, re-create copies, delete; delete an image in use | 4 copies made, used image refused with the place it is used |
| Users | weak password refused, add editor, rename, delete; editor's own view | editor gets 403 on users, activity and owner settings, and no publish button |
| Restore over the main database | backup → `portfolio:restore --into-main` → per-table row counts and `CHECKSUM TABLE` | identical before and after |
| Production mode | `APP_ENV=production` server | pages 200, forged Host 400, 404 without debug details, robots allows indexing |
