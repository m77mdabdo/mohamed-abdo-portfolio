# Database

MariaDB 10.4 (utf8mb4_unicode_ci). Translatable fields are JSON maps `{"en": "...", "ar": "..."}`; JSON is used
only for those maps and for typed section data validated by `App\Content\Fields\FieldSchema`. Everything that is
searched, ordered or linked has its own column, key and index.

```mermaid
erDiagram
    users ||--o{ media : uploads
    users ||--o{ content_revisions : publishes
    users ||--o{ activity_logs : does
    pages ||--o{ page_sections : "has (ordered)"
    projects ||--o{ project_media : "gallery (desktop/mobile, ordered)"
    media ||--o{ project_media : "used in"
    media ||--o{ projects : "cover"
    projects }o--o{ categories : category_project
    projects ||--o{ project_technology : "stack (ordered)"
    technologies ||--o{ project_technology : ""
    projects ||--o| projects : "next project"
    menus ||--o{ menu_items : has
    menu_items ||--o{ menu_items : "children (depth 2)"
    pages ||--o{ menu_items : "link target"
    projects ||--o{ menu_items : "link target"

    pages { bigint id PK; string key UK "system pages"; string slug UK; json title; json seo; bool is_visible; int sort_order; int lock_version }
    page_sections { bigint id PK; bigint page_id FK; string uid UK; string type; string preset; string anchor; int sort_order; bool is_visible; smallint schema_version; json data }
    projects { bigint id PK; string slug UK; json title; json kind; json summary; json brief; json built; json features; string status_tone; string year; string color; bigint cover_media_id FK; bigint next_project_id FK; bool is_featured; bool is_visible; int sort_order; json seo }
    project_media { bigint id PK; bigint project_id FK; bigint media_id FK "restrict"; string kind; int sort_order; json caption }
    media { bigint id PK; string path UK; string kind; string mime; int size; int width; int height; char sha1; json alt; json variants; string status; string source_key UK }
    content_revisions { bigint id PK; string subject; int version; longtext snapshot; char hash; string note; bigint published_by FK }
    site_settings { bigint id PK; string group UK; json data; int lock_version }
    contact_messages { bigint id PK; string name; string email; string project_type; text message; string status; uuid submission_key UK; char ip_hash; string notification_status; text notification_error }
```

Shared lists (each published as one subject `collection:<type>`): `services`, `experiences`, `skill_groups`,
`spoken_languages`, `process_steps`, `testimonials` (`has_consent`), `stats`, `technologies` (`in_cube`, `band_row`),
`categories`. All have `sort_order` with `id` as tie-breaker and `is_visible` where it applies.

Other tables: `slug_redirects` (old project/page addresses → 301), `menus`, `users` (`role` owner|editor,
`is_active`, encrypted 2FA secret and recovery codes), `activity_logs`, and Laravel's `sessions`, `cache`,
`jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`.

## Indexes that serve real queries

| Index | Query |
| --- | --- |
| `content_revisions (subject, version)` unique | live version of a subject = highest version |
| `page_sections (page_id, sort_order, id)` | sections of a page in display order |
| `projects (sort_order, id)`, `(is_featured, sort_order)` | project list, featured reel |
| `project_media (project_id, kind, sort_order)` | desktop / mobile galleries |
| `menu_items (menu_id, parent_id, sort_order)` | menu tree |
| `technologies (in_cube, sort_order)`, `(band_row, sort_order)` | cube and skills band |
| `contact_messages (status, created_at)`, `submission_key` unique | inbox filters, duplicate-free submissions |
| `media (sha1)`, `source_key` unique | import idempotency |

## Draft / published model

The tables above are the **working copy** (draft). Publishing writes the complete, deterministic snapshot of a
subject (`page:3`, `project:7`, `settings:seo`, `menu:header`, `collection:services`) into `content_revisions`.
Visitors read only the latest revision of each subject (cached until the next publish); preview links read the
working copy. Restoring writes an old snapshot back into the working copy inside a transaction.
