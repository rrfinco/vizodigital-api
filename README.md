# Developer Portal

CMS-driven API Documentation Portal (Laravel 12 · Blade · Tailwind · Docker).

**Module 0** — Foundation only. No hardcoded APIs. No Filament CMS yet.

## Ports

| Service | URL |
|---------|-----|
| App | http://localhost:9021 |
| phpMyAdmin | http://localhost:9024 |

> **Note:** Requested phpMyAdmin port was `9022`, but that port is already bound by `payin_app` on this machine. Compose currently publishes phpMyAdmin on **9024**. Change `docker-compose.yml` back to `9022:80` once that port is free.

## Quick start (Docker)

```bash
# 1. Environment
cp .env.example .env
# Ensure APP_KEY exists (Docker entrypoint can generate it)

# 2. Build PHP image & start stack
docker compose up -d --build

# 3. Build frontend assets (first time / after CSS/JS changes)
docker compose --profile build run --rm node
# or locally:
npm install && npm run build
```

Then open:

- Portal: http://localhost:9021  
- Docs shell: http://localhost:9021/docs  
- phpMyAdmin: http://localhost:9024  

Default MySQL credentials (see `.env`):

- Database: `api_portal`
- User: `portal` / `secret`
- Root: `rootsecret`

## Local PHP (without Docker)

Requires PHP 8.3+, Composer, Node 22+, MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
# Point DB_* at your MySQL, then:
php artisan migrate
npm install && npm run build
php artisan serve --port=9021
```

## Module 0 deliverables

- Laravel 12 application
- Docker Compose (PHP 8.3-FPM, Nginx, MySQL 8.4, phpMyAdmin)
- Design system: Inter, primary `#2563EB`, radius 16px, light/dark mode
- Landing page + docs layout shell (empty CMS state)
- `config/portal.php` placeholders for environments & sidebar

## Auth (Module 1)

| Item | Value |
|------|-------|
| Admin panel | http://localhost:9021/admin/login |
| Admin | `admin@portal.test` / `password` (`super_admin`) |
| User panel | http://localhost:9021/user/login |
| User | `user@portal.test` / `password` (`developer`) |
| Portal login | http://localhost:9021/login |
| Issue API token | `POST /api/v1/auth/token` |
| Current user | `GET /api/v1/auth/me` (Bearer token) |

**Panels are separate:** staff CMS is only on `/admin`; developers use `/user`. Wrong-panel login is rejected.

Roles: `super_admin`, `admin`, `editor`, `viewer`, `developer`

```bash
# If login is rate-limited or users missing:
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
docker compose exec app php artisan cache:clear
```

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

## CMS Schema (Module 2)

Database-driven documentation model is in place (no hardcoded APIs):

- Versions + Environments (UAT / Production seeded)
- Categories → Groups → Endpoints
- Composable `endpoint_sections` + `section_definitions`
- Headers, parameters, bodies, responses, errors, notes
- Env-scoped examples, code samples, Postman, SDK packages
- Documentation pages, FAQ, changelog, navigation, search index, settings
- Repository contracts bound in `RepositoryServiceProvider`

Foundation seed only: environments, `v1` draft version, section definitions — **zero demo endpoints**.

## Admin CMS (Module 3)

Filament v4 panel at **http://localhost:9021/admin**

Login with `admin@portal.test` / `password` (staff roles only).

Resources under **Documentation CMS**:

- Versions
- Environments
- Categories
- Groups
- Endpoints (core fields; composer relation managers in Module 4)

Dashboard shows CMS stats. Access gated by Spatie `docs.view_admin` / staff roles.

## Endpoint Composer (Module 4)

On **Endpoints → Edit**, compose full endpoint docs via relation managers:

- **Section layout** — enable/disable + drag reorder (auto-seeded on create)
- **Headers** · **Parameters** · **Request bodies** · **Responses** · **Errors** · **Notes**
- JSON schema/example editors; Markdown notes; reorderable children

## Env-Scoped Content (Module 5)

Per-environment documentation content (UAT vs Production):

- **Examples** · **Code samples** · **Base URL overrides** on Endpoints
- Base URL overrides also on Versions / Categories / Groups (cascade: endpoint → group → category → version → environment)
- **Postman** collections (version × environment)
- **SDK packages** (install markdown, repo, language)

`BaseUrlResolver` resolves the cascade for portal rendering (Module 7+).

## Publish & Preview (Module 6)

- **Publish / Unpublish** actions on Endpoints (admin roles with `docs.publish`)
- Bulk publish/unpublish from the endpoints table
- **Preview** draft endpoints: `/docs/preview/endpoints/{id}` (`docs.preview`)
- **Public** published docs: `/docs/{version}/endpoints/{slug}` — drafts return **404**
- **Audit log** resource records `endpoint.published` / `endpoint.unpublished`

## Rendering Engine (Module 7)

Portal pages render from CMS data via DTOs + `SectionRenderer`:

- **Endpoint docs** — enabled `endpoint_sections` in sort order → `docs/sections/*`
- **Env-aware** — examples & code samples filtered to the default environment; base URL via `BaseUrlResolver`
- **Related APIs** — linked endpoints in the right rail (published only on public pages)
- **CMS pages** — `/docs/{version}/pages/{slug}` (+ preview at `/docs/preview/pages/{id}`)
- Markdown rendered with Laravel `Str::markdown`

## Portal Navigation (Module 8)

- **DB sidebar** — `navigation_items` CMS (+ auto API Reference tree from published categories/groups/endpoints)
- **Version switcher** — header select; rewrites URL to the same content in another version when possible
- **Environment switcher** — `?env=` + session; rebinds base URL, examples, and code samples
- **Explorer** — `/docs/{version}/explorer`, category & group index pages
- Filament **Navigation** resource; Versions form attaches environments

## Search (Module 9)

- **`search_index`** denormalized rows for endpoints, pages, categories, groups (FAQ/changelog indexed for later)
- **Observers** reindex on save/delete; publish/unpublish updates status automatically
- **Instant search** — Alpine + axios dropdown in docs header → `GET /docs/search?q=`
- Rebuild: `php artisan search:reindex`

## CMS Pages Pack (Module 10)

Filament resources under **Documentation CMS**:

- **Pages** (guides, auth, webhooks, custom… + page sections relation manager)
- **FAQs** · **Changelog** · **Media**
- Existing **SDK packages** power the public SDK hub

Public portal:

| Route | Purpose |
|-------|---------|
| `/docs/{version}/pages/{slug}` | CMS documentation pages |
| `/docs/{version}/faqs` | FAQ hub |
| `/docs/{version}/changelog` | Changelog list |
| `/docs/{version}/changelog/{slug}` | Changelog entry |
| `/docs/{version}/sdk` | SDK hub |

Search URLs for FAQ/changelog are wired. Nav seeds FAQs / Changelog / SDKs.

## Hardening (Module 11)

- **Settings** — Filament page (`settings.manage`) for portal name / tagline / logo text (DB overrides config)
- **Permissions** — Navigation / Versions / Environments gated by `nav.manage`, `versions.manage`, `environments.manage`
- **Publish lock** — status fields disabled without `docs.publish` (editors cannot self-publish via forms)
- **Empty states** — shared `<x-docs.empty-state>` on explorer, FAQs, changelog, SDK, category/group, overview
- Branding reads from `PortalSettings` on docs + landing + admin panel

## Done

Module board complete through hardening. Later optional work: Try API, analytics, merchant go-live checklist, Scramble sync.

No further `Approve Module` gate — ship polish as needed.
