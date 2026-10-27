# Implementation Tasks — Public Website CMS

## Build order

- [ ] 1. Approve the actual launch URL and content matrix from the final SRS/public-site scope. The SRS-derived page/section matrix is in `public-page-inventory.md`; product/marketing still needs to identify launch pages and approve copy/assets.
- [x] 2. Add CMS page, section, media, menu, and allowlisted setting persistence.
- [x] 3. Add section schema validation and safe rich-text sanitizer.
- [x] 4. Add page and section admin flows: create, edit, preview, reorder, show/hide, draft save, publish/unpublish, delete safeguards.
- [x] 5. Add CMS media library with required alt text and optimization.
- [x] 6. Add header/footer navigation and global website settings screens, with draft/live settings publication.
- [x] 7. Add versioned public API for pages, navigation, settings, and catalog service grid.
- [x] 8. Add CAPTCHA-verified, rate-limited public inquiry endpoint integrated with CRM source tracking/deduplication. Live submission requires deployment Turnstile credentials.
- [x] 9. Add role/permission seeding, menu navigation, activity logging, cache invalidation, and API documentation.
- [x] 10. Update implementation audit and blueprint with current scope/status.
- [x] 11. Add the separate Next.js App Router client in `public-site/` with the seven CMS section renderers, dynamic page routing, global navigation/settings, SEO metadata, sitemap, and Turnstile lead form.
- [x] 12. Run the optimized Next.js production build.
- [ ] 13. Complete staging CMS/API acceptance for all sections and published-only behavior, responsive/accessibility review, media performance, and end-to-end inquiry capture.

## Acceptance checklist

- [ ] Every approved public URL/template and every rendered section/field is mapped to an admin control or explicitly documented as code-owned.
- [ ] Draft content never appears in public API responses; publishing returns one coherent snapshot.
- [ ] Content Manager cannot publish through UI or direct route; Operations Manager can publish and every mutation is audited.
- [ ] Active navigation cannot point to an unpublished/deleted CMS page.
- [ ] Rich text, URLs, uploads, settings keys, and lead form payloads are allowlisted and validated server-side.
- [ ] The staff UI follows root `AGENTS.md`; the public client consumes the documented API.
- [x] Production build succeeds; runtime verification returns HTTP 404 for the homepage and unknown slugs while no page is published in the local CMS.
- [ ] Staging acceptance proves all seven section renderers with published content, metadata/navigation/settings, sitemap, responsive behavior, media performance, and inquiry submit/CAPTCHA flow.
