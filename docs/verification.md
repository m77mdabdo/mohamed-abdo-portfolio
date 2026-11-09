# Verification — commands and actual results (2026-11-09, owner's Mac)

Environment: macOS, PHP 8.5.2 (Homebrew), XAMPP MariaDB 10.4.28, Node 24.22, Chromium 1248 (Playwright 1.64.1),
local server `php artisan serve` (development mode, `APP_DEBUG=true`, no HTTP/2, no compression proxy).

| Check | Command | Result |
| --- | --- | --- |
| PHP tests (SQLite) | `composer test` | 44 tests: 43 passed, 1 skipped (backup test needs MariaDB) |
| PHP tests (MariaDB test DB) | `composer test:mariadb` | 44 passed, 271 assertions |
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
| /ar | mobile (Moto G4, slow 4G, 4x CPU) | 2036 ms | 0.008 | 72 ms | 1262 KB |
| /en | mobile (Moto G4, slow 4G, 4x CPU) | 1836 ms | 0.008 | 72 ms | 1247 KB |
| /en/projects/creative-mark | mobile (Moto G4, slow 4G, 4x CPU) | 1352 ms | 0 | 56 ms | 631 KB |
| /ar/work | mobile (Moto G4, slow 4G, 4x CPU) | 1388 ms | 0 | 56 ms | 444 KB |
| /ar | desktop 1440 | 392 ms | 0.004 | 136 ms | 2404 KB |
| /en | desktop 1440 | 272 ms | 0.001 | 136 ms | 2389 KB |
| /en/projects/creative-mark | desktop 1440 | 128 ms | 0 | 56 ms | 825 KB |
| /ar/work | desktop 1440 | 232 ms | 0 | 56 ms | 1549 KB |

Targets (LCP ≤ 2.5 s, CLS ≤ 0.1, interaction ≤ 200 ms) are met in these lab conditions. Before preloading the
first-screen fonts, Arabic pages showed CLS 0.18–0.23 on desktop; preloading fixed it.

## Visual comparison with the original

Screenshots of the original standalone build and of this site at 1440 px (Arabic) and 390 px (English), same
state (reduced motion), were compared: layout, typography, colours, portrait, highlight, buttons, header and
time tag match. Intentional differences: with reduced motion the original still drew one 3D frame, this site
shows the static orb; project details are pages instead of an overlay; the contact button reads "Send
message" because the form now really sends; Arabic text in monospace labels is no longer letter-spaced (it
broke the joining of Arabic letters).
