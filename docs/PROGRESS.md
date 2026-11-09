# Progress log

Resume from here in a new session: read this file, `git log`, README and docs/.

- 2026-11-09 — Project created (Laravel 13.40, Filament 5.11.2) in `/Applications/XAMPP/xamppfiles/htdocs/mohamed-abdo-portfolio`.
  Reference design/content outside the repo at `~/Reference/mohamed-portfolio-ref/mohamed-portfolio`.
- Database created by the owner with `scripts/create-database.sh` (MariaDB `mohamed_abdo_portfolio` + `_test`).
- Done: content model, publishing engine, 17 section types, public site, SEO, import (9 projects, 62 images),
  full dashboard in Arabic/English, policies, backup/restore, 44 PHP tests, 20 browser checks, lab vitals, docs.
- Waiting on the owner: create the owner account (`php artisan portfolio:create-owner`), upload the CV PDF,
  configure SMTP for e-mail notices, add real testimonials (with consent) when available, delete the local QA
  account `qa-owner@portfolio.test` after signing in.
