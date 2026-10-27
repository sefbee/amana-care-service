# Public Website CMS API v1

The Laravel application is the headless CMS backend. The public Next.js/React frontend consumes these endpoints; the staff admin is not the public website renderer. Public reads contain only published page snapshots and allowlisted public settings.

All endpoints are under `/api/v1/cms` and return JSON.

## Published page inventory

`GET /api/v1/cms/pages` returns only published page titles, slugs, SEO summaries, and publication timestamps. The public client uses this endpoint to produce a sitemap; drafts and working slugs are never included.

## Published page

`GET /api/v1/cms/pages/{published-slug}`

Returns `404` unless the page is published. The response contains page SEO metadata and active sections sorted by their published order. Media references are expanded to `{ id, url, alt, mime_type }`. A `service_grid` section includes active catalog services and their currently approved, effective prices.

```json
{
  "data": {
    "title": "Care at home",
    "slug": "care-at-home",
    "seo": {
      "title": "Care at home | Amanah",
      "description": "...",
      "canonical_url": "https://example.com/care-at-home",
      "open_graph_image": null
    },
    "sections": [
      { "id": 1, "type": "hero", "content": {}, "order": 10 }
    ],
    "published_at": "2026-10-27T10:00:00.000000Z"
  }
}
```

## Navigation and global settings

- `GET /api/v1/cms/navigation/header`
- `GET /api/v1/cms/navigation/footer`
- `GET /api/v1/cms/settings`

Navigation includes visible links only and omits links whose referenced page is no longer published. Settings include only allowlisted values explicitly published by an Operations Manager; the Turnstile site key is public configuration, while the verification secret is never returned. Content Manager edits remain draft until approved.

## Website inquiry form

`POST /api/v1/cms/leads` is limited to five requests per minute per client IP. It accepts only field keys configured on an active published Dynamic Lead Form section. `full_name` and `phone` are required. `consent` must be accepted, and `captcha_token` must pass server-side Cloudflare Turnstile verification.

```json
{
  "page_slug": "contact",
  "data": {
    "full_name": "Example Name",
    "phone": "+8801XXXXXXXXX",
    "email": "person@example.com"
  },
  "consent": true,
  "captcha_token": "turnstile-response-token"
}
```

Successful submissions create a `New` lead with channel `Web Form`, a `website:{slug}` source, and an append-only granted-consent record containing a hash of the published consent-copy version. Duplicate matches return `409` with a generic message and do not reveal the matched record. Operations must publish consent copy in Website Settings. Turnstile credentials must be configured using `CMS_TURNSTILE_SITE_KEY` and `CMS_TURNSTILE_SECRET`; submissions fail closed when required consent copy or verification is not configured or available.
