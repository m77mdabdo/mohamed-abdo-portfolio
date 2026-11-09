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
Lighthouse itself could not be run here (downloading it was blocked by the machine's permission settings).

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
