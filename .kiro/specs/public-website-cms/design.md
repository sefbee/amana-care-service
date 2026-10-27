# Design — Public Website CMS

## Architecture

The Laravel application owns CMS administration, persisted content, publication snapshots, media references, and a versioned read-only JSON API. A separate Next.js/React client consumes only the published API. Rendering structure is selected from a closed set of section types; content is editable by staff within validated schemas.

## Data model

- `cms_pages`: unique working slug, nullable unique published slug, template, draft payload, published payload snapshot, publication state/timestamps/actors, creator/updater, soft-delete and timestamps.
- `cms_sections`: page id, type, draft content JSON, active draft flag, draft order, timestamps. The publish service compiles all section drafts into one immutable page snapshot.
- `cms_media`: path/disk, original name, MIME, size, alt text, public flag, uploader, timestamps. Files live on the configured public/S3 disk; storage keys, never client paths, are persisted.
- `cms_menu_items`: header/footer location, optional parent, label, URL, visibility, order, external/new-tab flag.
- `cms_settings`: allowlisted draft `value` and Operations Manager-published `published_value` JSON settings only; no arbitrary config writes.
- Published payload contains page metadata and sorted visible section snapshots; public readers never query draft columns.

## Supported section schemas

- Hero: eyebrow, headline, subheadline/body, CTA label/link, background media.
- Rich Text: sanitized HTML from an allowlist; no script, iframe, event-handler, or unsafe URL attributes.
- Service Grid: display options and references to active catalog records; prices resolve from the approved catalog API/source.
- Image/Text Split: alignment, heading/body, media reference, CTA.
- Testimonial Carousel: bounded list of client display name, review text, and 1–5 rating.
- FAQ Accordion: bounded list of question/answer entries.
- Dynamic Lead Form: approved field definitions and labels; submissions use server-side validation and CAPTCHA verification.

Final field limits and page-specific required sections must be captured in the page inventory/content matrix before implementation is marked complete.

## Access and workflow

- Reuse the role/permission system and add separate CMS edit and publish permissions.
- Require publish permission for page publish/unpublish, global-settings publication, and immediate navigation changes. Route permissions are not a substitute for policy checks in controllers/services.
- Draft updates are transactional. Publishing locks the page while compiling and writing the full snapshot, then invalidates tagged/public cache entries.
- Unpublish/delete checks active header/footer references before changing publication state.

## Admin screens

- Page list: `.row` / `.col-md-12`, page actions in toolbar, `.card` with `<x-datatable>`.
- Create/edit form: `.row` / `.col-md-8.offset-md-2`, `<x-alert :show_validations="false" />`, `.card.shadow-sm`, title/breadcrumb rendered by `layouts.base`.
- Page editor: metadata plus section cards; section navigation/order/visibility; per-type field controls; preview and draft/live status. Do not expose raw JSON editing as the staff UX.
- Separate admin screens for Media, Navigation, and Site Settings, following the same shared conventions.

## API surface

- `GET /api/v1/cms/pages/{slug}` — published page snapshot or 404.
- `GET /api/v1/cms/navigation/{location}` — published visible header/footer links.
- `GET /api/v1/cms/settings` — allowlisted public settings only.
- `POST /api/v1/cms/leads` — rate-limited, CAPTCHA-verified inquiry form into CRM.

The API contract must be documented and versioned; do not expose Eloquent models or draft attributes directly.


## Current code ownership

Laravel CMS classes use `App\FrontEnd\Cms`; internal business dependencies such as CRM leads and catalog services remain under `App\Admin`. CMS staff views live at `resources/views/front-end/cms`; CMS staff routes are in `routes/front-end.php`; public API routes are in `routes/front-end-api.php`. Route names and URLs remain stable. See `docs/CODEBASE_STRUCTURE.md`.
