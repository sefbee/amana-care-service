# Public Page and Section Inventory

## Source reviewed

`Amanah_Final_Production_SRS.pdf`, sections 2.1, 4.1–4.5, 13.4, and acceptance test AT-053.

## URL/template inventory

The final SRS does not prescribe a fixed list of public URL paths or separate page templates. It requires a headless frontend, new landing pages, SEO metadata, global header/footer navigation, globally injected contact variables, and a typed block builder. The CMS therefore supports arbitrary unique lowercase hyphenated slugs and composes pages from the seven SRS section types. The separate Next.js App Router client is now included in `public-site/`; it maps the configured home slug to `/` and other published slugs to `/{slug}`.

| Public content surface | Admin-controlled data | SRS basis |
|---|---|---|
| Any CMS-managed page URL | Title, unique slug, meta title, meta description, canonical URL, Open Graph image, ordered sections, section visibility | FR-300–FR-304, AT-053 |
| Hero | Eyebrow, headline, subheadline, CTA label/link, background media | FR-301 |
| Rich text | Sanitized HTML | FR-301 |
| Service grid | Heading/body, number of active services, whether to show approved indicative prices; service data and price values come from the live catalog | FR-301 |
| Image/text split | Image placement, eyebrow, heading, body, image, CTA label/link | FR-301 |
| Testimonial carousel | Ordered client name, review text, rating from 1 to 5 | FR-301 |
| FAQ accordion | Ordered question/answer pairs | FR-301 |
| Dynamic lead form | Eyebrow, heading/body, success message, submit label, allowed field keys/labels/required flags; consent and CAPTCHA are enforced by the shared form contract | FR-301, FR-312 |
| Header/footer navigation | Link label, URL or published CMS page, external flag, new-tab flag, visibility, order, one child level | FR-305, BR-023 |
| Shared website settings | Site name/tagline, support phone/email/address/WhatsApp and button labels, brand logo, theme colors, social links, footer copy and headings, consent copy, public Turnstile site key | FR-306, section 2.1 |
| Public media | Public image/video, image alt text, optimized WebP where supported, stable public URL | FR-307–FR-309 |

## Boundary and remaining acceptance evidence

- The Laravel editor/API supports any page slug and the complete SRS block set; it cannot author arbitrary frontend code or a new renderer type.
- The final SRS leaves the actual initial URL list and brand copy/assets unspecified. Marketing/product must populate and review those through the CMS before launch.
- The Next.js client consumes the versioned API and implements a dynamic CMS-page route, Turnstile form, SEO metadata, sitemap, global frame, and all seven section renderers. Build verification and live CMS/API smoke acceptance remain required.
- No public page is marked complete merely because the CMS can store it; acceptance requires the corresponding public client renderer and a published-content smoke scenario.
