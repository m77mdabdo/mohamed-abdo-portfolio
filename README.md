# محمد عبده — Portfolio

Bilingual (Arabic / English) portfolio of Mohamed Abdo, software engineer in Riyadh — a cinematic one-page
home with standalone pages, and a complete dashboard where every page, section, project, list, menu,
text, image and setting is edited, previewed, published and restored without touching code.

- Public site: `/{ar|en}` — home, `/work`, `/about`, `/services`, `/resume`, `/contact`, `/projects/{slug}`
- Dashboard: `/admin` (Arabic / English, owner and editor roles, optional two-factor sign-in)

Documentation: [owner guide (Arabic)](docs/owner-guide-ar.md) · [content inventory](docs/content-inventory.md) ·
[database](docs/database.md) · [decisions](docs/decisions.md) · [verification](docs/verification.md)

## Stack (installed versions — see `composer.lock` / `package-lock.json`)

| Layer | Version |
| --- | --- |
| PHP | 8.5.2 (requires ^8.3) |
| Laravel | 13.40.0 |
| Filament (dashboard) · Livewire | 5.11.2 · 4.4.8 |
| Database | MariaDB 10.4 (XAMPP) — MySQL 8 compatible; SQLite for fast tests |
| Front end | Blade, TypeScript 5.9, Vite 8.3, GSAP 3.15, Three.js 0.169 (loaded on demand), Lenis 1.3 |
| Fonts (self-hosted) | Bricolage Grotesque, Alexandria, Instrument Sans, IBM Plex Sans Arabic, JetBrains Mono (Fontsource) |
| Quality | PHPUnit 12, Larastan 3 (level 5), Pint, Playwright 1.64 |

No React, Inertia, Redis or search engine: Blade renders complete HTML (readable without JavaScript) and a
small TypeScript runtime adds the motion layer.

## Install (local)

```bash
git clone https://github.com/m77mdabdo/mohamed-abdo-portfolio.git
cd mohamed-abdo-portfolio
composer install
npm ci
cp .env.example .env && php artisan key:generate
bash scripts/create-database.sh            # XAMPP MariaDB: database, test database, two least-privilege users
php artisan migrate --database=mariadb_migrate
php artisan db:seed                          # imports the original content and images (safe to repeat)
php artisan storage:link
npm run build
php artisan portfolio:create-owner           # your sign-in; the password is typed, never stored in code
```

`scripts/create-database.sh` asks once for the MariaDB root password and writes generated passwords into
`.env` only. The runtime user (`map_app`) can only read/write data; schema changes use `map_migrate`.

## Run

```bash
php artisan serve --host=127.0.0.1 --port=8020   # site: http://127.0.0.1:8020/ar · dashboard: /admin
php artisan queue:work --tries=3                 # optional: e-mail notices and image copies also run right after each request
php artisan schedule:work                        # daily backup at 03:30 (Asia/Riyadh server time)
```

To keep them running after a restart on macOS, use `launchd` agents or a terminal tool such as `tmux`; in
production use Supervisor for `queue:work` and one cron entry: `* * * * * php /path/artisan schedule:run`.
Production checklist: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS (adds HSTS automatically), a real
`MAIL_*` configuration, `php artisan optimize`, document root on `public/`. No deployment is configured here.

## Content workflow

Saving in the dashboard changes the **draft** only. **Preview** opens a signed 30-minute link (signed-in staff
only, `noindex`, `no-store`). **Publish** (owner) freezes the draft into an immutable version; **History** restores
any previous version into the draft, or straight to the live site. Deleting a page or project is immediate
(owner only). Media descriptions apply immediately. Details: [owner guide](docs/owner-guide-ar.md).

## Commands

| Command | What it does |
| --- | --- |
| `composer test` | PHP tests on SQLite (in memory) |
| `composer test:mariadb` | the same tests on the separate MariaDB test database, plus backup/restore |
| `composer lint` | Pint (style) + Larastan (static analysis) |
| `npm run typecheck` / `npm run build` | TypeScript check / production assets |
| `BASE=http://127.0.0.1:8020 npx playwright test --trace off` | browser checks (needs the server running) |
| `BASE=http://127.0.0.1:8020 node tests/perf/vitals.cjs` | lab Web Vitals (mobile emulation) |
| `php artisan db:seed` | re-run the content import (only adds what is missing) |
| `php artisan portfolio:create-owner` | create or reset the owner account |
| `bash scripts/configure-mail.sh` | set up Gmail/SMTP for contact notices (password stays in `.env`) |
| `php artisan portfolio:mail-test [to]` | send a test e-mail with the current settings |
| `php artisan portfolio:backup [--no-media]` | database + uploads to `storage/app/private/backups` |
| `php artisan portfolio:restore <file>` | verify a backup in a temporary database (`--into-main` to replace live data) |

## Backup and restore

Backups are private (`storage/app/private`, not served over HTTP), mode `0600`, rotated (14 kept). To restore
uploaded files: `tar -xzf storage/app/private/backups/media-<stamp>.tar.gz -C storage/app/public`. To restore
the database: `php artisan portfolio:restore <db-file>` first (checks it in a temporary database), then
`php artisan portfolio:restore <db-file> --into-main` (asks you to type the database name).

## Security

Dashboard behind sign-in (no registration, rate-limited login, optional 2FA), owner/editor policies on every
model, CSRF on all forms, strict Content-Security-Policy with nonces on the public site, escaped CMS text
(titles support only `*highlight*` and line breaks), URL allow-list for every link, uploads checked by real
file type (no SVG/HTML/scripts, PDFs verified), random file names, contact form with validation, honeypot,
rate limit and idempotent submissions. Secrets live only in `.env`.

## Third-party licences

Laravel, Filament, Livewire, Three.js, Lenis, Vite, Fontsource packages: MIT. Fonts: SIL Open Font License.
GSAP: GreenSock Standard "no charge" licence (https://gsap.com/standard-license). Technology icons: simple-icons
(CC0); brand names and marks belong to their owners and are used only to name the tools.

## Changelog

- Header links open standalone pages; floating WhatsApp button and phone (editable); CV view + download;
  e-mail notices without a background worker and `scripts/configure-mail.sh`; CV portrait and anchor fixes.
- Browser checks (6 widths × 2 languages, reduced motion, no WebGL, no JavaScript, keyboard), lab Web Vitals,
  font preloading.
- Tests (44), Larastan level 5, backup/restore commands and daily schedule.
- Dashboard: media library, inbox, users, activity log, publishing centre, Arabic translation.
- Dashboard: projects, shared lists, settings, menus, interface texts.
- Dashboard page editor: sections library, drafts, signed preview, publish, history.
- Content model, publishing engine, section library, public site, SEO, import of the original content.
