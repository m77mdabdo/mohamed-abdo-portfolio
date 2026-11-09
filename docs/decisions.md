# Engineering decisions

1. **Laravel 13 + Filament 5 on PHP 8.5.** Latest stable releases at build time (Packagist, Nov 2026);
   Filament 5 requires PHP 8.2+ and supports Laravel 13. Verified working together on this Mac before choosing.
2. **Blade + a small TypeScript runtime, no SPA.** Full HTML from the server keeps every page readable without
   JavaScript, indexable and fast; GSAP, Lenis and the lazily imported Three.js scene add the motion layer.
   The original "project dialog" became real pages (`/{locale}/projects/{slug}`) so they can be shared and indexed.
3. **Working copy + immutable revisions** (`App\Content\Publisher`) instead of draft columns on every table.
   One mechanism covers pages, projects, menus, settings and shared lists; history and restore come for free;
   visitors read one cached snapshot per subject. Cost: a snapshot per publish (a few KB), acceptable here.
4. **Typed fields drive forms, validation and cleaning** (`Field` → `FieldFormBuilder` / `FieldSchema`).
   Adding a section type = one `SectionType` class + one Blade view + one line in `SectionRegistry`
   (a keyed map, O(1) lookup). Editors never write HTML, JSON or code; unknown keys are dropped on save.
5. **Content reader strategy** (`ContentReader`: `PublishedReader` / `DraftReader`), bound per request.
   The site code does not know whether it renders live content or a preview, so drafts cannot leak by accident.
6. **Two locales as JSON maps** rather than translation tables: the set is fixed (ar/en), fields are always read
   together, and fallback is one function (`Tr::pick`). Slugs are shared by both languages, so switching
   language keeps the same page.
7. **Menus are a depth-limited tree** saved from nested arrays in one transaction; cycles are impossible by
   construction and `MenuTree::wouldCreateCycle` guards any future move operation.
8. **Media**: real MIME detection, no SVG/HTML, random names, WebP/AVIF copies by a queued job using GD
   (no extra system packages); the original is always the fallback. Usage is computed on demand before delete.
9. **Contact form**: store first, notify later (queued job with honest status); `submission_key` makes the form
   idempotent; honeypot + per-IP limits; IPs stored only as an HMAC.
10. **Security headers**: strict CSP with nonces on public pages; the dashboard keeps what Livewire/Alpine need.
11. **3D hero**: icosahedron detail 56 (desktop) / 28 (phones) instead of 96 (≈188k → 65k / 17k triangles),
    pixel ratio capped (1.5 / 1.25), rendering paused off-screen and in background tabs, static orb when
    WebGL, motion or data saving is unavailable, every GPU resource disposed on teardown.
12. **No Redis, queue or cache servers**: database queue and cache are enough for one site and keep the setup
    to MariaDB only.
