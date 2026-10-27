# Requirements — Public Website CMS

## Purpose

Provide an editorial control panel for all approved Amanah public website pages and sections. Content is managed in the Laravel staff application and published as JSON to the separate public Next.js/React frontend, as required by the final production SRS. A public-page inventory and content matrix must be approved before any page-specific schema is considered complete.

## Requirements

### 1. Pages and section composition

1. Content Managers can create, edit, preview, and save public pages as drafts with unique lowercase hyphenated slugs.
2. A page is composed of ordered sections using only the supported types: Hero, Rich Text, Service Grid, Image/Text Split, Testimonial Carousel, FAQ Accordion, and Dynamic Lead Form.
3. Each section has a typed, validated content schema. Unsupported section types and fields are rejected server-side.
4. Editors can hide/show and reorder sections. These edits remain draft until publishing approval.
5. Existing public page inventory and section matrices define which pages/sections are required; no public-facing copy, media, CTA, SEO, navigation, or global setting expected to be managed by staff may be omitted from admin controls.

### 2. Draft, preview, and publication

1. Draft page and section changes are never returned by public endpoints.
2. Content Managers can preview the complete draft without making it indexable or public.
3. Only a user with the Operations Manager publishing permission can publish/unpublish a page or publish section changes.
4. Publishing captures a coherent snapshot of page metadata and all sections so the public frontend never receives a mixture of draft and live values.
5. A page referenced by active global navigation cannot be unpublished or deleted.

### 3. SEO and navigation

1. Each page supports unique slug, meta title (max 60 chars), meta description (max 160 chars), and optional Open Graph media.
2. Staff can manage header/footer menu links, one-level nested children, labels, internal/external URLs, visibility, order, and new-tab behavior.
3. Menu URLs are validated; internal paths cannot be protocol-relative or contain unsafe schemes. External links are allowlisted to HTTPS, mailto, and tel.

### 4. Media and site settings

1. Staff can upload public images/video to a media library with metadata and uploader attribution.
2. Images require alt text, are validated by actual MIME/type and size, and are optimized/converted to WebP when supported by the installed image pipeline.
3. Authorized staff can manage global support phone/email/address/WhatsApp, branding, supported theme values, shared website copy, and footer settings.
4. Public APIs expose only media referenced by published content or marked public.

### 5. Public content API and forms

1. A versioned, read-only public API returns published pages, active sections in configured order, public navigation, shared settings, and approved active service prices only.
2. Draft pages and draft section values return not found from public endpoints.
3. Dynamic inquiry forms validate payloads, sanitize input, validate CAPTCHA/Turnstile server-side, apply rate limits, and create a sourced Lead using the existing CRM flow.
4. API responses include page SEO data and stable media URLs/alt text.

### 6. Audit and access

1. CMS edits, publication, unpublication, navigation changes, and media changes are permission checked and activity logged with actor and record.
2. The Content Manager role can edit and preview but cannot publish. The Operations Manager can approve/publish. Superuser remains unrestricted.

## Boundaries

- Staff CMS pages use the existing Laravel Blade admin and the root `AGENTS.md` layout conventions.
- The public website renderer is a separate Next.js/React client; do not replace it with staff pages.
- Supported section types and renderer structure are code-owned. CMS users edit the content and documented options for every inventoried page/section; they do not execute or author arbitrary PHP/Blade/JavaScript.
- No clinical data is accepted by inquiry forms.
