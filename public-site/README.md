# Amanah public website

This is the separate Next.js App Router client for the Laravel headless CMS. It renders the seven CMS section types from published page snapshots; the staff CMS stays in Laravel Blade.

## Local setup

1. Start the Laravel application at `http://127.0.0.1:8000` and run its database migrations/seeds.
2. Copy `.env.example` to `.env.local`. Set `NEXT_PUBLIC_CMS_API_BASE_URL` to the Laravel `/api/v1/cms` base URL and set `CMS_HOME_SLUG` to the published homepage slug.
3. Set Laravel `FRONTEND_URL` to the exact public-site origin. The public lead form calls Laravel directly so its per-IP rate limit sees the visitor's address.
4. Publish the website's consent text and Turnstile site key in CMS settings. Configure the matching private Turnstile secret in Laravel `CMS_TURNSTILE_SECRET`.
5. Run `npm install`, then `npm run dev`.

## Deployment variables

- `NEXT_PUBLIC_CMS_API_BASE_URL`: public Laravel API base, ending in `/api/v1/cms`.
- `NEXT_PUBLIC_SITE_URL`: canonical origin for sitemap and metadata.
- `CMS_HOME_SLUG`: published CMS slug rendered at `/` (defaults to `home`).
- Laravel `FRONTEND_URL`: exact origin of this site for CORS; do not use `*` for a credential-capable origin.
- Laravel `CMS_TURNSTILE_SITE_KEY` and `CMS_TURNSTILE_SECRET`: paired public/private Turnstile keys.

The API returns published snapshots only. Page routes return 404 for drafts and unpublished slugs. Content Managers cannot publish through this client; publishing remains a staff-admin operation.

The configured home slug maps to `/` in internal page links and the sitemap. Other published slugs map to `/{slug}`. Canonical URLs explicitly set in CMS metadata take precedence.
