# Amanah Operations Platform — Implementation Blueprint

## Purpose and source of truth

This file gives AI coding agents a durable map of the complete application scope. It is based on **Amanah_Final_Production_SRS.pdf**, the final production SRS. The earlier **Amanah_SRS_v1.pdf** is a draft and describes some capabilities as later phase work; where the documents differ, use the final production SRS unless an approved change says otherwise.

The SRS PDFs supplied with the project are currently outside this repository at:

- `/home/yamin/Documents/SefBee/Amanah_Final_Production_SRS.pdf`
- `/home/yamin/Documents/SefBee/Amanah_SRS_v1.pdf` (draft/reference only)

Keep this summary aligned with the final SRS. Do not treat it as permission to invent requirements where the SRS leaves a policy decision open. Record unresolved decisions and ask the product owner when they affect implementation.

## Product shape

Amanah is a staff-facing operations platform for the full care-service lifecycle: intake, family onboarding, caregiver recruitment and readiness, human-reviewed matching, placements, finance, and ongoing quality/safeguarding. The final production SRS defines **seven integrated top-level modules**:

1. Public Website CMS
2. CRM & Intake Pipeline
3. Catalog & Pricing Engine
4. Caregiver Recruitment & Document Vault
5. Matching & Placement Engine
6. Finance & Operations
7. Quality Assurance & Safeguarding

Identity and access control, dashboards/work queues, notifications, reports, settings, audit, data migration, retention, and background jobs are shared platform capabilities supporting the modules; do not count them as additional top-level modules unless a future approved SRS does so.

## Module scope

### 1. Public Website CMS

Provide staff content management for a **headless CMS**, decoupled from the public website frontend. The CMS serves published JSON content to a separate Next.js/React frontend.

- “Website control” means editorial control of the whole approved public site, not just control of catalog/database records: every public page template and each of its sections must have an explicit content schema and editor controls for its content and supported section options.
- Page and section builder using the documented section types: hero, sanitized rich text, service grid, image/text split, testimonials, FAQ, and dynamic lead form.
- Per-section visibility and ordering; page draft/review/publish workflow.
- Page slugs and SEO metadata; header/footer navigation builder; global contact/social variables; editable site-wide branding, supported theme settings, footer content, and shared website copy.
- Media library with optimization and required alt text.
- Public API exposes published content only. Dynamic forms require CAPTCHA/Turnstile validation and submit into CRM intake.
- Prevent unpublishing/deleting pages still referenced by active global navigation.
- Before defining the CMS schema, inventory every in-scope public URL/template and every section/field rendered by it. No public-facing copy, image, CTA, title, section visibility/order, SEO field, menu/footer item, or theme/contact setting should remain unintentionally hard-coded when it is expected to change through the admin.

CMS staff screens belong in this Laravel admin and must follow the Blade conventions in root `AGENTS.md`. The public website frontend is a separate client; do not implement it as staff-admin pages.

The Urban Haven project at `/home/yamin/Projects/urban-haven` is the reference for the desired level of editorial control: typed per-page/per-section fields, drafts separate from live content, show/hide/reorder controls, previews and publishing, global website settings, menus, media, and editors for structured site records. Adapt that **content-control model** to Amanah and its approved SRS; do not copy Urban Haven property entities, design, or Laravel 13 implementation. In that reference, section field schemas and Blade renderers are still code-defined: admin users control content and supported options within those schemas, while engineers own the rendering/template structure. Keep this distinction explicit when defining what “everything” means.

### 2. CRM & Intake Pipeline

- Capture inquiries across the documented channels, normalize phone numbers, detect duplicates, and preserve consent history.
- Manage lead lifecycle, ownership, communication logs, waitlist/cohort handling, and conversion.
- Manage households, decision/billing/emergency contacts, care recipients, and non-clinical needs assessments.
- Conversion from a qualified lead creates/links the household and primary contact transactionally, preserving the audit trail.
- Respect consent before outbound communication and protect sensitive household/care-recipient information.

The existing detailed spec is `.kiro/specs/crm-lead-management/`. Read its requirements, design, and tasks before modifying CRM behavior; reconcile any conflict with the final production SRS explicitly.

### 3. Catalog & Pricing Engine

- Manage the documented service definitions and variants.
- Maintain approved, versioned price records with effective dates; retain historical versions as immutable financial references.
- Generate quotes using the applicable approved price version and localized BDT formatting.
- Keep website service-grid pricing sourced from the same catalog/API so published prices do not drift from operations pricing.

Service catalog and pricing code already exists in this repository. Audit its behavior against the final SRS before extending it; existing files do not prove the complete module is finished.

### 4. Caregiver Recruitment & Document Vault

- Manage caregiver recruitment, profiles, duplicate checks, and consent.
- Store sensitive identity, police/background, and psychological documents in private storage; enforce authorization and audited, short-lived reveal access.
- Track screening outcomes and document expiry; run expiry checks as scheduled jobs.
- Record required training and outcomes. Readiness requires all documented checks/training/approvals and must be revoked when a required condition expires or fails.
- Do not make automated hiring or rejection decisions.

### 5. Matching & Placement Engine

- Accept service requests and non-clinical needs assessments; support quote and approval workflow.
- Filter candidates by readiness, service skills, live-in/live-out preference, schedule availability, language, area/proximity, and documented constraints.
- Show reasons for filtering and require staff rationale for candidate proposals; support family decision and caregiver response records.
- Generate family-facing candidate summaries with restricted PII removed.
- Manage agreements, placement activation/pause/end/cancellation, and replacement cases.
- Enforce schedule overlap prevention transactionally at the database level. Humans select and approve; the system must not automatically assign caregivers.

### 6. Finance & Operations

- Generate invoices from active placement schedules and applicable price versions.
- Record manual payments to Amanah, receipts, balances, multi-invoice reconciliation, reversals, and approved adjustments/credits/penalties.
- Support overdue processing, finance exports, and the documented replacement-policy evaluation.
- Preserve immutable financial references and audit changes. Payment gateways, tax/VAT, and refund processing are excluded unless an approved SRS change adds them.

### 7. Quality Assurance & Safeguarding

- Schedule placement follow-ups/check-ins from activation and manage templates, outcomes, and follow-up queues.
- Centralize complaints and incidents with ownership, status, and escalation.
- Support restricted safeguarding workflows: limit visibility, alert designated safeguarding staff immediately, and apply documented restrictions/holds without exposing sensitive case details to unauthorized staff.

## Shared platform capabilities

Implement these consistently across modules wherever applicable:

- Authentication, sessions, user/role/permission administration, and record-level access boundaries.
- Dashboards and role-appropriate work queues.
- In-app/email notification delivery, consent checks, failure handling, and scheduled/background jobs.
- Append-only or otherwise protected audit history for consequential changes and sensitive-data access.
- Versioned settings and picklists; reporting with controlled exports; migration, retention, backup, and restore workflows.
- Input validation, privacy-safe logging, accessibility, responsive screens, and secure document/media handling.

## Explicit production boundaries

Do not implement these as ordinary in-scope features without an approved SRS change:

- Clinical records such as diagnoses, prescriptions, treatment plans, or clinical notes.
- Payroll processing.
- AI/automatic caregiver matching, assignment, hiring, or rejection. Candidate filtering is allowed; human decisions are mandatory.
- Family or caregiver login portals in this release.
- Direct family-to-caregiver payments, hiring, or marketplace messaging.
- GPS/biometric attendance, native mobile applications, or online payment gateway integrations.

## Implementation workflow for agents

For each requested feature:

1. Identify the module and exact SRS requirements/acceptance criteria it implements; inspect the current routes, controllers, models, migrations, policies, services, and views first.
2. Check whether a Kiro spec exists. If it does, keep its requirements/design/tasks coherent with the final SRS; do not declare a whole module complete based on a partial CRUD screen.
3. Preserve established application structure and existing data/behavior. Use migrations for schema changes, named routes and existing authorization patterns, and activity logging for consequential mutations.
4. Apply the root `AGENTS.md` page-title, breadcrumb, action, tab, CRUD, card, and DataTable conventions to every changed/new staff page.
5. Protect sensitive data at query, authorization, serialization, export, and UI levels. Enforce business invariants in server-side domain logic, not only in forms.
6. Update the relevant Kiro spec/checklist and this blueprint only when scope or implementation status materially changes. Keep product requirements distinct from implementation notes and unresolved decisions.

## Current planning/documentation status

- `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md` is the static repository audit and recommended dependency-based implementation sequence; update it as implementation status changes.
- `.kiro/specs/crm-lead-management/` contains the only detailed module spec currently in the repository.
- No detailed Kiro specs for the other six top-level modules were present when this blueprint was created.
- Existing service, price-version, user, and admin-console code should be audited against the production SRS before marking those capabilities complete.
- Create additional Kiro module specs under `.kiro/specs/<module-slug>/` as modules are planned; each should include requirements, design, and tasks and link back to this blueprint.
