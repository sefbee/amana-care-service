# Implementation Audit and Start Plan

**Audit basis:** static inspection of the current repository against `Amanah_Final_Production_SRS.pdf` and `.kiro/IMPLEMENTATION_BLUEPRINT.md` on 2026-10-27. No application tests, migrations, deployments, or external services were run/checked. A capability is marked implemented only when code artifacts for its main flow are present; that does not mean its SRS acceptance criteria are verified.

## Executive summary

The repository is an early operations-admin foundation, not a complete implementation of the final production scope. CRM/household management and the service catalog/pricing have useful working code, while the other major lifecycle modules are not represented in the current application tree. Several cross-cutting production controls also need substantial work.

| SRS capability | Audit status | Evidence in this repository | Main missing work |
|---|---|---|---|
| Public Website CMS | **Not started** | In Amanah repository, no CMS routes, models, migrations, views, public content API, or website frontend found | Editorial controls for every in-scope page and section, global site settings, media, navigation, publishing/preview, content API, forms/CAPTCHA, public frontend integration |
| CRM & Intake Pipeline | **Partial** | Leads, households, contacts, care recipients, consent/logs, conversion and menu/routes exist; detailed Kiro spec at `.kiro/specs/crm-lead-management/` | Service requests, assessment schemas, quotes, required primary and billing contact invariant, full duplicate link/merge resolution, web-form intake integration, production access rules |
| Catalog & Pricing Engine | **Partial** | Service and PriceVersion models/migrations, CRUD, approval/retirement actions and seeders exist | Quote generation, price snapshots consumed by requests/placements/invoices, immutable approved records, catalog-to-CMS API, SRS catalog reconciliation |
| Caregiver Recruitment & Document Vault | **Not started** | No caregiver, screening, training, document, readiness, or recruitment code found | Full candidate pipeline, private storage/quarantine/scanning, checks, training, readiness gate and expiry/revocation jobs |
| Matching & Placement Engine | **Not started** | No service request, proposal, placement, agreement, or schedule-overlap implementation found | Request lifecycle, human-reviewed matching, rationale, redacted candidate summary, agreement gate, transactional overlap protection, replacement cases |
| Finance & Operations | **Not started** | No invoice, invoice line, payment, receipt, reconciliation, or finance controller/model found | Billing jobs, sequential invoice numbers, balances, manual payments, reconciliation locks, adjustments and replacement policy financials |
| Quality Assurance & Safeguarding | **Not started** | No follow-up task, complaint, incident, restricted-case, or safeguarding workflow found | Day 7/30/60 follow-ups, escalation/notification, record isolation, SL hold action and access-attempt audit |
| Shared platform / production readiness | **Partial** | Web auth, user CRUD, roles, permissions, menus, API auth, activity log dependency and queue tables/config exist | MFA, record-level access controls, role-specific dashboards/work queues, notification retry/dead-letter flow, audit coverage, reporting/export, retention, migration tools, production security/infrastructure controls |

### Numbering clarification

Use **module names**, not bare numbers, in implementation plans. The final production SRS lists Public Website CMS first. The existing Kiro spec calls CRM “Module 1,” and the user has referred to Catalog & Pricing as “Module 2.” These numbering systems differ; the names are unambiguous.

## Urban Haven reference study

The separate `/home/yamin/Projects/urban-haven` codebase is a useful reference for what the user means by website control from the admin panel. This is a source-code study only; no Urban Haven files were changed and its deployment scripts/archive were not executed.

Its editorial-control pattern goes beyond CRUD for listings:

- `HomeSections`, `AboutPageSections`, `ContactPageSections`, and `ArticlesPageSections` define each supported page section, its label, editable field schema, validation metadata, defaults, and any linked structured-content source.
- `CmsBlock` stores published content and draft content separately. Admin users can edit typed fields, preview, hide/show sections, reorder sections, discard a draft, or publish; publish permission is separate from edit permission.
- `CmsPageController` and page editor screens manage custom pages, templates, SEO, media, preview, and draft/publish state.
- Settings screens cover brand identity/assets, theme colors, contact/social details, website copy, analytics/consent, listing presentation, SEO defaults, and footer details. Separate editors control menus, media, FAQs, articles, projects, properties, and location/catalog records.
- Public Blade templates render this content. The editor controls content and supported section settings; it does not rewrite arbitrary Blade markup or invent new renderer types without code changes.

For Amanah, build an explicit **public-page inventory and page-section-content matrix** before implementing CMS storage. It must enumerate every in-scope public URL/template and all content and section controls required on it. Keep the final Amanah SRS's headless Next.js/React delivery boundary; adopt Urban Haven's completeness of editorial controls, not its property-business schema, visual design, or Laravel 13 stack. Amanah's Blade staff screens must continue to follow this repository's `AGENTS.md` conventions.

## Detailed findings

### CRM & Intake Pipeline — partial

Present: Lead/Household/HouseholdContact/CareRecipient and communication/consent/status-history entities; CRUD pages; duplicate checking; phone normalization; status history; consent roll-up; outbound-consent guard; lead-to-household conversion; non-clinical validation; role/menu seeders.

Gaps against the final SRS:

- No ServiceRequest, service-specific NeedsAssessment, quote, or waitlist/cohort-cap data model/workflow was found. These are needed to move a household toward matching and service delivery.
- Current HouseholdContact logic protects the last `Primary` contact, but the final SRS requires both a Primary Decision Maker and a Billing Contact. Billing routing is not implemented as an enforced invariant.
- Duplicate detection raises a warning/override path, but the final SRS also calls for an explicit link/merge resolution workflow and mandatory intake source tracking. Verify the full flow and permissions before treating it as complete.
- The only intake routes are staff-facing lead CRUD; no CMS form-to-CRM endpoint or CAPTCHA verification exists.
- Current CRM spec checkboxes being complete document that spec's work, not the additional final production SRS scope.
- Check current household/care-recipient fields against the final data dictionary and privacy rules before extending them; do not store clinical information.

### Catalog & Pricing Engine — partial

Present: service CRUD and seeded catalog; price-version history with date range, draft/approved/retired status, creator/approver attribution, separate approver check, and overlap check for approved periods.

Gaps/risks to resolve:

- No quote model, quote workflow, or historical price-version snapshot on a quote/placement/invoice exists yet.
- There is no server-enforced immutable ledger boundary visible in the model/controller: price records are ordinary Eloquent models and the module exposes deletion of drafts. Ensure approved or referenced versions cannot be edited/deleted and that referenced prices remain stable.
- No API for the CMS Service Grid to read active services and approved indicative prices was found.
- Compare seeded services/variants, prices, and approval policy with the final catalog requirements; keep unverified website values clearly unapproved.

### Shared platform — partial

Present: Laravel authentication, users, role/permission/menu administration, an API authentication/profile flow, activity-log integration in business controllers, queue/job database tables, and an SMS service/configuration.

Gaps/risks to resolve:

- No MFA implementation or per-record authorization policy layer was found in the route/controller/model inventory. Role-level middleware alone is not equivalent to the final record-level controls.
- Dashboard route/view exists, but role-specific operational queues and KPI/report coverage are not evidenced by a corresponding domain data model/workflow.
- No scheduled domain jobs, failed-notification retry/dead-letter workflow, data migration/import tools, retention scrubber, or controlled reporting/export module was found.
- Infrastructure requirements (private S3, malware scanning, managed secrets, TLS/HSTS, dependency scanning, encrypted backups, restore drills) cannot be confirmed from application source alone and need deployment evidence.

## Recommended implementation sequence

This sequence is dependency-based. It is an engineering plan, not a change to the SRS's release scope or any unresolved business decision.

### Phase 0 — Resolve scope and production foundations

1. Preserve this audit as the baseline and add one Kiro requirements/design/tasks spec per missing or partial module under `.kiro/specs/`.
2. Build a traceability matrix from final-SRS requirement IDs to code, spec task, and acceptance evidence. Resolve conflicting field/status definitions and approval-dependent decisions before schema freezes.
3. Close shared architecture/security gaps that affect every module: MFA, record-level authorization, audit events, notification delivery/retry, environment/secrets, private file storage/scanning, and backup/restore approach.
4. Add navigation and role/permission definitions module-by-module only as those screens and actions are implemented.

**Exit:** each module has approved requirements/design/tasks; high-risk policy decisions and deployment dependencies are explicit; data classification and access boundaries are defined.

### Phase 1 — Complete Catalog & Pricing and CRM service intake

1. Audit and harden existing service/pricing workflows and their UI against the SRS.
2. Add immutable approved price behavior and historical price references.
3. Add ServiceRequest, service-specific non-clinical NeedsAssessment, quote generation, and waitlist/cohort rules.
4. Add required Primary and Billing contact invariants, duplicate link/merge decisions, source tracking, and the public-form intake API contract (CAPTCHA enforcement can be completed with CMS integration).

**Exit:** an inquiry can be qualified, converted, associated with a household/request, assessed, and quoted against a stable approved price version with auditable history.

### Phase 2 — Public Website CMS and integration

1. Inventory every in-scope public page/template, each rendered section, all copy/media/CTA/SEO fields, and the structured data each section references. Get the page inventory and editable/fixed boundary approved before schema work.
2. Implement CMS page/section/media/navigation/global-setting data and staff screens, with typed field definitions for every section on every inventoried page.
3. Add draft/live separation, preview, publish approval, section visibility/order controls, and slug/navigation integrity.
4. Add a public read API that exposes only published page/section content and serves the active catalog prices.
5. Add secure lead-form submission, CAPTCHA validation, sanitization, and CRM deduplication integration.
6. Establish the separate Next.js/React frontend integration boundary; keep CMS administration in the Laravel Blade UI.

**Exit:** every inventoried public page can be maintained through admin within its approved schema; draft changes do not leak; published content is delivered via API; public forms create properly sourced CRM inquiries.

### Phase 3 — Caregiver Recruitment, Vetting & Readiness

1. Implement candidate profiles, service skills, preferences, and availability.
2. Implement private document intake, quarantine/malware scan, metadata, authorized audited reveal links, and expiry dates.
3. Implement checks, training, outcomes, approval, and a single server-side readiness evaluator.
4. Add expiry/revocation jobs and alerts; ensure imported candidates start as Needs Verification rather than Ready.

**Exit:** only a fully compliant, approved Ready caregiver can enter matching candidate pools; expired/blocked candidates are removed automatically.

### Phase 4 — Matching, Placement & Replacement

1. Implement service-request candidate filtering with explicit exclusion reasons and soft-sort indicators.
2. Add staff proposals with required rationale, approval/family decision, caregiver response, and PII-redacted summaries.
3. Implement placement, signed-agreement upload gate, exact price-version reference, and transactional overlap protection.
4. Add pause/end/cancel and replacement case workflows using configured policy parameters and manual-decision paths where required.

**Exit:** the system cannot activate an ineligible or double-booked caregiver placement, and all human decisions have reasons and audit history.

### Phase 5 — Finance & Quality/Safeguarding

1. Add invoice generation from placements and immutable price snapshots, line items, sequential numbering, due dates, balances, and overdue jobs.
2. Add manual payment entry, receipts, reconciliation, reversal/adjustment controls, and finance exports.
3. Add placement follow-up scheduling and outcome queues.
4. Add complaint/incident intake, restricted safeguarding access, immediate alerts, audit of denied attempts, and SL caregiver hold action.

**Exit:** placement-to-payment records reconcile end-to-end; restricted incidents cannot be retrieved by unauthorized roles and required alerts/holds take effect.

### Phase 6 — Reports, UAT & production hardening

1. Implement required operational/financial reports and privacy-safe exports.
2. Complete migration dry runs, retention behavior, accessibility and responsive review, performance checks, security review, penetration-test remediation, backup restore drill, and role-based UAT.
3. Run end-to-end acceptance scenarios: inquiry → household → assessment/quote → caregiver readiness → match → placement → invoice/payment → follow-up/safeguarding.
4. Update module checklists only after acceptance evidence is recorded; prepare training, cutover, and hypercare.

**Exit:** all SRS acceptance gates have evidence, no unresolved critical/high issues remain, UAT is signed, and operational recovery/monitoring procedures are ready.

## Definition of module completion

Do not mark a module complete because its CRUD pages exist. Completion requires:

- Every in-scope final-SRS requirement mapped to an implementation and acceptance check, or explicitly approved as deferred/changed.
- Server-side business rules and authorization enforced on every route/API path, not only in UI controls.
- Sensitive fields excluded from unauthorized views, tables, logs, exports, and public APIs.
- Audit history and transactional behavior for consequential operations.
- Navigation, role/permission configuration, error/empty states, and the repository's shared Blade layout conventions in place.
- Required verification evidence recorded in the module's Kiro tasks and release checklist.

## Repository evidence used for this audit

- Routes: `routes/web.php`, `routes/api.php`.
- Implemented business areas: `app/Http/Controllers/LeadController.php`, `app/Http/Controllers/HouseholdController.php`, `app/Http/Controllers/ServiceController.php`, `app/Http/Controllers/PriceVersionController.php`, corresponding models/services, migrations, views, and `database/seeders/`.
- Current detailed spec: `.kiro/specs/crm-lead-management/`.
- Absence findings are based on inventory of current application model/controller/service/migration/view/route files; they are not a runtime or deployment audit.
