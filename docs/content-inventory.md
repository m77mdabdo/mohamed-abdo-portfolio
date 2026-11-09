# Content inventory — every visible element and where it is edited

Legend — **Where**: dashboard path (menu › screen › field). **Store**: table.column / JSON key.
**Who**: E = editor and owner can edit the draft; O = owner only. Publishing is always owner-only.
**Test**: automated check that covers it (`tests/Feature/*`, `tests/e2e/site.spec.ts`).

No marketing text, image or link is hard-coded in templates. The only fixed strings are the language names
("العربية" / "English", "EN" / "ع") and technical markers (arrows, ©). Everything below is editable.

## Global (every page)

| Element | Where | Store | Who | Test |
| --- | --- | --- | --- | --- |
| Name, job title, city, short city, time zone + label, availability, public e-mail | Settings › Identity & social links | `site_settings[identity]` | E | NavigationAndTextsTest |
| Portrait (hero, CV card, structured data) | Settings › Identity › Portrait | `identity.portrait` → `media` | E | CvAndMediaTest (usage) |
| Social links (header drawer, contact, footer, JSON-LD sameAs) | Settings › Identity › Social links | `identity.social[]` | E | SeoAndLinksTest |
| Logo tagline, menu/close labels, skip link, language-switch label | Navigation & texts › Header & menu labels | `site_settings[header]` | E | NavigationAndTextsTest |
| Header menu, floating/mobile menu, footer menu (labels, targets, order, visibility, sub-items) | Navigation & texts › Menus | `menus`, `menu_items` | E | NavigationAndTextsTest |
| Footer texts (CTA line, column titles, giant name, edition, rights, back to top, live column on/off) | Navigation & texts › Footer | `site_settings[footer]` | E | NavigationAndTextsTest |
| Footer "live projects" list | Projects › each project › Live site | `projects.live_url` | E | SeoAndLinksTest |
| Interface texts (form messages, a11y labels, error pages, preview bar) | Navigation & texts › Interface texts | `site_settings[ui]` (defaults `lang/*/site.php`) | E | NavigationAndTextsTest |
| Site title, description, sharing image, favicon, indexing, job title (JSON-LD) | Settings › SEO & sharing | `site_settings[seo]` | O | SeoAndLinksTest |
| Accent / signal colours (approved palette), motion level, 3D, smooth scroll, cursor, grain, opening screen, greetings | Settings › Appearance & motion | `site_settings[appearance]` | O | e2e reduced-motion / no-WebGL |
| Contact notice recipient, notices on/off | Settings › Contact inbox | `site_settings[contact]` | O | ContactTest |
| CV file (PDF) | Settings › CV file | `site_settings[cv].file` → `media` | E | CvAndMediaTest |

## Pages (Site content › Pages › page › Sections)

Each page opens with its sections in visual order; every section can be shown/hidden, moved, duplicated,
deleted, given a link name (#anchor) and, where offered, a design preset. New sections come from the library.

| Page | Sections (visual order) |
| --- | --- |
| Home `/` | Hero · Statement · About + cube · Work reel · Project list · Services · Résumé · Skills band · Testimonials (hidden until consented quotes exist) · Process · Contact |
| Work `/work` | Page header · Project list (with category filter) · Call to action |
| About `/about` | Page header · About + cube · Statement · Process · Call to action |
| Services `/services` | Page header · Services · Process · Call to action |
| Résumé `/resume` | Résumé · Skills band · Skill groups · Call to action |
| Contact `/contact` | Contact + form |
| Added pages `/{slug}` | any sections from the library |

| Section | Editable fields (Arabic + English unless marked) | Shared data it shows | Test |
| --- | --- | --- | --- |
| Hero | badge, title (`*highlight*`, line breaks), subtitle, up to 3 buttons (text, style, link), portrait override, moving name, scroll hint, 3D on/off, clock on/off | identity | PublishingTest, SecurityTest |
| Statement | label, three lines | — | e2e |
| About + cube | label, lead, paragraph, cube on/off, figures on/off | Lists › Technologies (cube), Figures | e2e |
| Work reel | label, title, all/featured, limit, cursor label | Projects | ProjectsTest |
| Project list | column titles, filter on/off, "all" label, cursor label | Projects, Categories | ProjectsTest, SeoAndLinksTest |
| Services | label | Lists › Services | — |
| Résumé | label, title, download/pending texts, headings, "now" sticker, portrait card on/off | Experience, Languages, CV file | CvAndMediaTest |
| Skills band | label, title | Technologies (band rows 1–3) | e2e |
| Skill groups | label | Lists › Skill groups | — |
| Testimonials | label | Lists › Testimonials (consent required) | — |
| Process | label | Lists › Process steps | — |
| Contact + form | label, title, round button, copy/copied labels, form on/off, field labels, project types, send label, note, success/failure messages | identity (e-mail, social, city) | ContactTest, e2e |
| Page header / Text block / Image + text / Call to action / Figures | titles, texts, image, image side, buttons, design preset | Figures | PagesTest |

Page settings tab: title (menus, tab), address (custom pages), visibility, search title/description,
sharing image, hide from search engines — `pages.title / slug / is_visible / seo` — PagesTest, SeoAndLinksTest.

## Projects (Projects › Projects)

| Element | Field | Test |
| --- | --- | --- |
| Name, type, summary, brief, solution, highlights | Story tab (`projects.title/kind/summary/brief/built/features`) | ProjectsTest |
| Status label + dot, year, colour, role, client, languages | Facts tab | ProjectsTest |
| Categories (filter), ordered stack | Facts tab (`category_project`, `project_technology`) | ProjectsTest |
| Cover, computer screenshots, phone screenshots (order, captions) | Images tab (`cover_media_id`, `project_media`) | ProjectsTest |
| Address (old ones redirect), live site, source code, next project, visible, featured, order | Address, links & order tab | ProjectsTest |
| Search title / description | SEO tab | SeoAndLinksTest |
| Labels on project pages (back, next, role, status, …) | Navigation & texts › Project page labels | — |

Order can also be changed by dragging rows in the list; "Publish all project changes" publishes them together.

## Lists, media, inbox, people

- Lists: Services, Experience, Skill groups, Languages, Process steps, Testimonials, Figures (Lists menu);
  Technologies and Categories (Projects menu). Drag to order, switch visibility, publish the list.
- Media library: upload (images / PDF), descriptions (alt) in both languages, where-used, re-optimise, delete
  (owner, refused while in use) — CvAndMediaTest.
- Messages: status new/read/in progress/archived, search, filter, view, delete (owner) — ContactTest.
- Users & permissions, Activity log (owner) — AccessTest.
