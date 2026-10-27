# Complete Implementation Audit and Master Plan

**Plan basis:** static source review against `Amanah_Final_Production_SRS.pdf` (SRS v3.0, Final Production Launch), existing Kiro specs, routes, migrations, controllers, models, services, seeders, and Blade views. Updated after the Laravel CMS work and FrontEnd/Admin code organization. This is a source audit, not production deployment evidence or full acceptance testing.

## 1. Completion standard

“100% implemented” means every in-scope SRS functional requirement, business rule, security rule, automated job, role/record boundary, and critical acceptance test is implemented and has recorded verification evidence. A CRUD screen or a checked Kiro task alone does not satisfy this standard.

For every requirement, maintain a trace from SRS identifier → Kiro requirement/task → implementation location → automated/manual acceptance evidence. Mark a module complete only when its acceptance gate below is satisfied and no unresolved high-risk policy or infrastructure dependency remains.

## 2. Product scope and module status

The final SRS defines **seven top-level modules**. Authentication, RBAC, record-level authorization, dashboards, jobs, notifications, reports, audit, retention, and deployment controls are shared platform capabilities, not extra product modules.

| Top-level module | Current status | Existing foundation | Remaining completion scope |
|---|---|---|---|
| 1. Public Website CMS | **Partial** | Laravel headless CMS editor/API plus separate Next.js App Router client in `public-site/`; seven section renderers, dynamic published page route, menus/settings, SEO metadata, sitemap, Turnstile inquiry form | Build and staging acceptance; approve launch URLs/content; verify all API contracts, media delivery/performance, accessibility, published-only behavior, deployment configuration, and end-to-end CRM capture |
| 2. CRM & Intake Pipeline | **Partial** | Lead, consent (append-only latest-per-channel history), communication log with current Email/SMS consent guards, status history, household, contact and care-recipient flows; owner scopes; Service Request capture with scheduled weekdays, service/live-in choice, allowlisted child/elderly non-clinical assessment and household-bound access; CMS lead intake endpoint | Omnichannel completeness; provider-send consent recheck when delivery is implemented; duplicate link/merge workflows; source analytics; Service Request lifecycle, capacity/waitlist and assignment policy; quote integration; full BR coverage and acceptance |
| 3. Catalog & Pricing Engine | **Partial** | Service catalog and immutable approved price versions; OM-configurable service-to-caregiver skill requirements; household Service Requests; draft quote/line snapshots reference effective approved PriceVersion; OM approval and reasoned total override lock quote values | Reconcile exact SRS catalog/variants; acceptance for approval/overlap concurrency; quote revision/share/expiry policy; immutable downstream placement/invoice links; API consistency with public Service Grid |
| 4. Caregiver Recruitment & Document Vault | **Partial** | Caregiver profile/list/detail, protected NID duplicate fingerprint, skills/availability, screening and training records, OM readiness approval, fail-closed readiness evaluator, private S3/quarantine upload and scanner/STS reveal code; screening/training/approval/profile writes recompute readiness transactionally and failed readiness withdraws Draft proposals; Trainer-write and OM read-only minimum-field mandatory-training roster; nightly latest-police-check expiry task creates RS alerts; SL/PSU safeguarding hold atomically revokes readiness and Draft proposals | Complete edits/import and Needs Verification history, AWS Block Public Access/IAM/scanner provisioning and acceptance, references, hold-release policy, alert operations/acceptance, retention and policy approvals; define OM training update/approval workflow consistent with the matrix; production vault remains blocked on D-023 |
| 5. Matching & Placement Engine | **Partial** | Household-scoped candidate review reevaluates readiness and filters configured skills/live-in preference and potential weekday/minute conflicts; CO can save an audited rationale-required draft proposal of 1–3 candidates; staff can record immutable, rationale-required family and caregiver responses; a branded allowlist PDF omits restricted fields; activation locks the caregiver row and requires family acceptance, current readiness/price, and a scanned private signed agreement; scheduled-start job rechecks readiness; placement pause/end/cancel and reasoned 300-second agreement reveal are audited; nightly police expiry withdraws affected Draft proposals | System proposal delivery, database-engine concurrent acceptance evidence, date-aware preliminary schedule matching, resume and replacement/60-day policy remain open |
| 6. Finance & Operations | **Partial** | Immutable finance ledger schema, policy-gated idempotent draft generation, gapless sequence allocation, FN invoice issue/payment/reconciliation screens, OM invoice/payment read access, assigned-CO invoice read access, PDF receipts, balances and overdue job | Resolve billing cycle, recurring quote overrides, proration, due-date and correction policies; complete reversals/adjustments/credits/penalties, replacement decisions, exports, notifications and acceptance evidence |
| 7. Quality Assurance & Safeguarding | **Partial** | Placement follow-ups with QS/PSU outcome writes, OM/SL read access, CO assigned-Service-Request read scope and RL-05 blocking; QS/PSU routine complaint register with encrypted notes, ownership/status history and audit; encrypted Restricted incident capture; OM read-only and SL/PSU audited web/API case access; denied-access auditing; durable in-app alerts; generic email/SMS retries/DLQ and 15-minute SLA monitor; transactional SL/PSU caregiver hold | OM follow-up create/update/approve workflow; complaint escalation recipient/notification and retention policy; provider provisioning/runtime delivery evidence; severity policy; full privacy and role acceptance |
| Shared platform / production readiness | **Partial** | Authentication, user/role/permission/menu admin; transactional metadata audit of admin-console role/menu/permission mutations; read-only System Audit Logs for SA/OM/SL with PSU access and role-based redaction; encrypted/idempotent operational notification outbox, staff inbox, Email/SMS dispatcher with consent/RL-05 checks, retry/dead-letter, body-free audit, and audited PSU metadata-only queue monitor; RL-05 blocks across CRM, caregiver, matching/placement, billing and restricted-case related identity; EX aggregate reporting and watermarked XLSX export; per-request account/role gates and Sanctum-token revocation; Laravel response headers and nonce CSP plus a nonce CSP in the public Next.js proxy; CI dependency audit workflow, with current Composer/npm scans clean | SRS role matrix and complete record-level coverage; MFA remains removed by explicit user direction; NFR-021 multi-device/idle-session runtime evidence; quiet-hour/provider approval and production delivery evidence; production queue threshold alerting; broader dashboards/reports; future export-route review; retention/import tools; browser acceptance and style-attribute tightening, proxy/TLS evidence, hosted audit runs, deployment and recovery evidence |

### Approved policy decision — Platform Superuser vs SRS System Administrator

The user has decided to **distinguish the platform superuser from the SRS System Administrator (SA)**. The SRS SA remains restricted from PII, sensitive vault documents, and safeguarding case details. The separate Platform Superuser (PSU) may perform unrestricted technical/platform actions and business actions, with every sensitive read, reveal, override, or break-glass operation explicitly audited. The role/permission design must not alias PSU to SA or let ordinary SA permissions bypass these controls. Update the role taxonomy and implementation specs accordingly.

## 3. Dependency-ordered delivery plan

Each phase requires its own Kiro requirements/design/tasks (or an updated existing spec), migrations where needed, named routes, authorization, UI, activity/audit handling, navigation/permissions, automated checks, and acceptance evidence. Keep staff features in `app/Admin` / `resources/views/admin`; put public-website CMS features in `app/FrontEnd/Cms` / `resources/views/front-end/cms` as documented in `docs/CODEBASE_STRUCTURE.md`.

### Phase 0 — Baseline, traceability, and policy decisions

- [x] Create a first-pass requirement traceability baseline for SRS FR, BR, RL, SG, NFR, scheduled-job rows, acceptance tests, and data dictionary entities in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`; remaining BR rows must be decomposed individually from the PDF before acceptance.
- [x] Reconcile `.kiro/specs/crm-lead-management/` and `.kiro/specs/public-website-cms/` with the current `Admin` / `FrontEnd` namespaces, route files, and view paths. Existing checked boxes still require behavior-level SRS revalidation.
- [x] Create initial requirements/design/tasks specs for Catalog & Pricing, Recruitment & Vault, Matching & Placement, Finance & Operations, Quality & Safeguarding, and shared platform/security. Reconcile details and update during implementation.
- [x] Record the user-approved distinction between Platform Superuser (unrestricted, fully audited) and SRS System Administrator (restricted from sensitive data).
- [ ] Record remaining policy decisions and owner approvals: retention period and disposition of deprecated CRM PII/free-text columns (D-001); approved care-recipient age bands and classification boundaries; safeguarding severity and SLA definitions (D-011); contract/agreement rules; matching constraints; invoice numbering/accounting policy; replacement windows and fee/waiver authority; cohort capacity; and any SRS ambiguity.
- [ ] Record infrastructure owners and readiness for AWS/S3 private buckets, quarantine scanning, STS, email/SMS providers, Turnstile, managed secrets, TLS/DNS, and public-site deployment.

**Exit gate:** requirement scope is traceable; high-risk decisions are approved or marked as blocked; no implementation silently assumes a missing business policy.

### Phase 1 — Shared identity, authorization, audit, and runtime foundation

- [x] Establish separate Platform Superuser and System Administrator identities; migrate the existing superuser flag/seed role and add the SA role.
- [x] Seed the current dashboard, CMS, catalog, and CRM navigation/route grants from the single `database/seeders/Admin/MenuSeeder.php`; reserve the SA and Platform Superuser role definitions for Platform Superusers.
- [x] Bind permission caches to the authenticated user and active assigned role; reject unassigned/inactive role IDs and invalidate cached permissions on grant/assignment changes.
- [ ] Implement the full SRS role taxonomy and permission matrix, separating global/platform administration from business approvals.
- Progress: repaired the staff password-reset mismatch (confirmed POST, existing `UserController@passwordReset` permission, body-free audit event and Sanctum-token revocation); readiness-alert grants for RS/OM are now included in the canonical synchronized matrix. Added a Trainer-write mandatory-training workflow and OM read-only roster access. OM caregiver grants now match profile CRU and readiness approval authority; screening-result approval is distinct and granted to OM/SL, with RS entering results and SL navigation to authorized caregiver records; restricted-document access remains reasoned/audited. OM and assigned CO now have matrix-scoped invoice reads; OM alone has payment reads; FN retains finance writes. Admin-console authorization changes are audited transactionally, permission synchronization uses POST, and System Audit Logs is available to SA/OM/SL with role-based redaction. A database-backed audit found permission definitions and at least one role grant for all 122 effective `role_permission_check` routes; remaining SRS role-cell reconciliation and negative acceptance remain open.
- [ ] Complete server-side record-level access rules across every module and non-UI path. CRM lead/household owner scopes, nested-record parent checks, child-data redaction and core RL-05 block enforcement are implemented. Broader API/export/job coverage, complete safeguarding-isolation review and negative acceptance remain open.
- [ ] **SRS deviation:** User explicitly requested MFA removal on 2026-10-27. TOTP setup/challenge/recovery and mandatory-role gates have been removed, contrary to final SRS NFR-029. Login throttling remains; record this as nonconforming until the SRS/security policy is formally revised. Verify session/device revocation within 60 seconds separately.
- [x] Add an explicit Platform Superuser request audit and remove submitted form values/query strings from general activity log payloads.
- [ ] Complete audit coverage for consequential writes, sensitive reads, document reveals, exports, denied access and all future module actions; redact secrets and unnecessary PII.
- [x] Implement durable in-app/email/SMS notification delivery, priority/quiet-hour rules, exponential retry and dead-letter handling; provide an audited PSU-only metadata queue monitor. Production alert thresholds/monitoring and external delivery evidence remain open.
- [ ] Establish scheduler/queue monitoring, idempotent jobs, error reporting, environment-managed secrets, TLS/proxy deployment checks, encrypted backup and restore procedures. Application security headers and nonce CSP are implemented for web/API responses; interactive browser acceptance and reducing the legacy inline-style attribute allowance remain open. A GitHub Actions dependency audit scans Composer and both npm lockfiles on PRs, main/master pushes and weekly; current Composer 2 and npm scans report zero advisories, while hosted execution and ongoing remediation remain open.
- [ ] Define reusable secure upload, private storage, download/reveal, authorization, short-lived URL and access-audit patterns before the caregiver vault starts.

**Exit gate:** role and record boundaries are enforced on direct requests and non-UI paths; mandatory authentication controls and audit/notification foundations are demonstrably active.

### Phase 2 — CMS client integration and public launch acceptance

- [x] Add the separate Next.js/React client under `public-site/`, isolated from the Laravel staff UI. Confirm its production owner, environment, and deployment access before launch.
- [ ] Map all approved public launch URLs/templates and content fields to the CMS page/section schemas; identify code-owned layout/renderers versus editor-owned content/options.
- [x] Implement all seven SRS section renderers, SEO/head metadata, global settings, menus, dynamic page routing, and CMS media/alt-text consumption; Next.js production build passes. Verify responsive/accessibility behavior, cache invalidation, lazy-loaded/optimized media, and Lighthouse performance target (>90) in staging.
- [ ] Verify the client consumes only published API snapshots, correctly renders active ordered sections and approved service prices, returns 404 for drafts, and supports staff preview without exposing drafts publicly.
- [ ] Integrate the Turnstile/reCAPTCHA widget and server credentials, CAPTCHA failure/rate-limit behavior, and consent copy. Complete the CMS-to-CRM lead end-to-end path in Phase 3 after CRM duplicate/source handling is ready.
- [ ] Run public publishing, page/section hide/reorder, navigation integrity, media, and SEO acceptance in staging. Complete inquiry end-to-end acceptance with Phase 3 CRM.

**Exit gate:** marketing can launch and update approved public content without code edits; public users never receive draft data. Inquiry capture is accepted jointly with Phase 3 CRM.

### Phase 3 — Catalog & Pricing plus CRM intake and household onboarding

- [ ] Audit current service and PriceVersion CRUD, approvals, effective dates, status transitions, deletion, and seeded values against the SRS catalog and authorization matrix.
- [ ] Make approved/referenced price versions immutable; prevent overlapping approved periods; retain exact `price_version_id` and monetary snapshot for every quote/placement/invoice consumer.
- Current progress: approved/retired price terms are guarded at the model layer; approval locks the service row and price row while checking date overlap; nested service/price IDs are checked. Quote/placement/invoice references and acceptance evidence remain open.
- [ ] Complete quote creation, localized BDT totals, approval/OM overrides with rationale and audit, revision/expiry policy, and downstream conversion. Draft quote and line snapshots, current-price lookup, OM override and immutability are implemented; expiry/share policy and placement/invoice consumers remain open.
- [ ] Complete lead channels/source attribution, normalized phone/email duplicate matching across leads and household contacts, and blocking link/merge/justified-override workflows with role enforcement and audit.
- [ ] Complete consent history and ensure every outbound SMS/email path checks consent at send time, not only when a communication is recorded.
- [ ] Enforce Household Primary Decision Maker and Billing Contact invariants, allowing the same contact to hold both; route invoices to the Billing Contact.
- [ ] Add Service Request lifecycle and service-specific, conditional non-clinical assessments (including strict forbidden clinical-field and free-text validation); support waitlist/cohort capacity and assignment without inventing capacity policy.
- Progress: initial household-bound Service Request capture is implemented with a service-linked schedule and allowlisted Nanny/ELDERLY assessment fields validated by the non-clinical rule. State lifecycle, capacity/waitlist, reassignment, request edit, quote link, and matching schedule semantics remain open.
- [ ] Verify public CMS lead form creates a sourced CRM lead, resolves duplicates safely, respects consent and rate limits, and returns privacy-safe responses.

**Exit gate:** an inquiry can be captured, deduplicated/resolved, consented, converted to a household with valid contacts, assessed and quoted against an immutable approved price, with audit and access rules intact.

### Phase 4 — Caregiver recruitment, vetting, training, and readiness

- [ ] Complete caregiver identity/profile, contact, encrypted NID hash (never raw NID), service skills, gender preference only for matching, availability, zone preferences, duplicate review, profile edit/import, and stage workflow per FR-090/092/093. RS historical CSV import now creates Not Ready candidates, stores historical check claims as immutable Needs Verification records, blocks duplicates by protected NID fingerprint, and reports row/field errors without values; duplicate link/merge, full stage workflow and acceptance remain open.
- [ ] Verify private S3 storage with Block Public Access, upload quarantine, malware scanning, clean-file promotion, metadata, and no public storage route. Private S3/quarantine disks, fail-closed ClamAV scanning, clean promotion and metadata are coded; AWS buckets, Block Public Access, IAM and scanner deployment/acceptance remain pending D-023.
- [ ] Verify role-authorized reveal with required reason, object-scoped STS session, URL expiring in exactly 300 seconds, and access audit. RS/OM/SL/PSU role check, predefined reasons, access records, and 300-second presigning are coded; AWS trust-policy/STS acceptance remains open.
- [ ] Complete identity/police/psych checks, expiry/outcomes, references, policy-defined mandatory training and attendance/outcomes, and OM approval records. Evidence-linked checks now have separate immutable OM/SL approval, distinct from the caregiver-level OM readiness decision; role permissions are seeded to OM/SL and the readiness evaluator requires approval of each latest passing result. References, full evidence acceptance, private upload provisioning and training policy remain open.
- [ ] Complete continuous server-side readiness recomputation enforcing every FR-133 condition; current evaluator derives status from latest checks, training, safeguarding hold and OM decision. Screening/training/OM approval/profile edits and SL/PSU incident hold now re-evaluate under caregiver-row locks and readiness failure withdraws Draft proposals. Hold release policy and AT-007 acceptance remain open. No form writes Ready directly.
- [ ] Implement scheduled expiry alerts/revocation and withdrawal from draft proposals for expired checks; expose readiness reasons to authorized staff. Nightly job now checks the latest passed police record, issues durable RS alerts for checks within 30 days/expired, recomputes readiness, and withdraws affected Draft proposals with immutable events; alert queue supports audited acknowledgement. Operational delivery and AT-007 acceptance remain open.

**Exit gate:** only compliant, OM-approved, trained caregivers can become Ready; expired/failed/held caregivers are automatically excluded, with no direct-URL or import bypass.

### Phase 5 — Human-reviewed matching, proposals, placement, and replacement cases

- [ ] Implement matching from an eligible Service Request using hard filters: readiness, service skill, live-in/out compatibility, schedule overlap, and documented constraints.
- [ ] Add soft-match flags for language, zone/proximity, and experience; show exclusion/match reasons; never auto-select, hire, or assign.
- [ ] Implement CO proposals of 1–3 candidates with mandatory rationale of at least 20 characters, human/family decision records, caregiver response, and privacy-redacted branded PDF summaries. Draft creation with current eligibility revalidation and immutable event audit is implemented; staff can record external family and caregiver decisions and download an audit-logged allowlist PDF. System delivery and withdrawal on readiness revocation remain open.
- [ ] Implement placement lifecycle with schedule, start/end, immutable approved price reference, assigned roles, agreement upload requirement, and full audit history. Activation with family acceptance, approved current quote/price, private scanned agreement, price/quote snapshots, readiness-gated scheduled-start automation, audited pause/end/cancel transitions, and reasoned private agreement reveal are implemented. Resume and replacement remain open.
- [ ] Enforce time/day overlap in a transaction at the database layer, including concurrent activation attempts and direct API requests; return a safe actionable overlap error. Caregiver-row transaction serialization and minute-slot checks are implemented; engine-specific concurrent acceptance remains required. Preliminary date-aware conflict filtering is open because Service Requests have no requested-date period.
- [ ] Implement pause/end/cancel and replacement cases; evaluate configured 60-day eligibility; stop for OM manual decision after the window and record fee/waiver rationale.

**Exit gate:** no unready caregiver, schedule conflict, missing agreement, missing human rationale/decision, or mutable historical price can activate a placement.

### Phase 6 — Finance & Operations ledger

- [ ] Implement invoice generation from active placement schedules using the placement's immutable price snapshot and documented billing cycle.
- [ ] Implement mathematically verifiable line items, due dates, idempotent scheduled generation, draft/issue transitions and SRS gapless invoice sequencing.
- [ ] Implement manual payment entry to Amanah only, method/reference/date/amount validation, receipt generation, partial/full balance calculations, and overpayment handling per approved policy.
- [ ] Implement unreconciled/reconciled payment states, bank-statement reconciliation workflow, and prevention of deletion or mutation after reconciliation; provide controlled reversal workflows with audit.
- [ ] Implement adjustments, credits, penalties, finance exports and overdue processing with role controls and SRS watermarking.
- [ ] Connect replacement policy outcomes to invoice adjustment/penalty/waiver only after OM authorization.

**Exit gate:** invoice totals, balances, partial payments, receipts, reconciliation locks and immutable price links pass the SRS finance acceptance scenarios end-to-end.

### Phase 7 — Quality assurance, complaints, and safeguarding

- [x] On placement activation, create idempotent Day 7/30/60 QS follow-up work; record one terminal Satisfied/Concern/Escalate outcome per task with encrypted notes and immutable audit history. QS/PSU can write; OM/SL can read all and CO can read assigned Service Requests only. RL-05 is applied to every non-PSU path; OM's matrix-listed create/update/approve operations remain open.
- [x] Implement QS routine complaint intake, ownership, status, escalation state and household/placement links with encrypted notes, append-only history and audit. Escalated routine status does not create a restricted case or alert; routing/notification/reopen/retention policy and acceptance remain open.
- [ ] Allow staff to report serious incidents; implement immediate Restricted classification, reporter/CO isolation, role-scoped case detail access, generic restricted-case banners, and 403 for unauthorized direct requests. **Core web/API paths implemented:** encrypted report, fixed Restricted classification, generic reporter response, OM read-only and SL/PSU case reads, privacy-safe deny audit and 403; only SL/PSU may acknowledge alerts or apply caregiver holds; full data-surface review remains open.
- [ ] Send immediate high-priority in-app/email/SMS alerts to SL that bypass quiet hours, with delivery/retry visibility and acknowledgement timestamp for the 15-minute SLA. Durable in-app alerts, immediate generic email/SMS attempts, 1/5/30/120-minute retries, dead-letter recording and 15-minute SLA monitor are implemented; external provider configuration and runtime acceptance remain open.
- [x] Implement SL caregiver On Hold action; revoke readiness and withdraw the caregiver from every matching pool/proposal/eligible placement path immediately. Hold and readiness recomputation withdraw Draft proposals within the transaction; verify every active path in acceptance.
- [ ] Audit denied access attempts and ensure logs, dashboards, exports, notifications and search cannot leak restricted notes or reporter identity.

**Exit gate:** AT-022 and safeguarding isolation scenarios pass; alerts and caregiver holds are effective across every access/matching path.

### Phase 8 — Dashboards, reporting, migration, retention, and go-live

- [ ] Deliver role-specific dashboards/work queues and operational/financial KPIs without exposing records beyond role/ownership scope.
- [ ] Deliver controlled, audited, watermark-protected CSV/Excel exports and anonymized aggregated Executive/Auditor reports. **Current surface implemented:** EX-only aggregate dashboard and XLSX export use hidden-sheet/metadata watermarks; no other CSV/XLSX export routes exist. Remaining: role-specific work queues, any future module export paths, CSV watermark policy (CSV has no sheets), and full RL-06/RL-05 acceptance.
- [ ] Implement safe caregiver/family imports, validation/error reports, source/provenance, and “Needs Verification” defaults for historical caregiver checks.
- [ ] Implement approved retention schedules and PII scrubbing only after D-001 legal sign-off; preserve required financial/audit records.
- [ ] Perform migration dry runs, verify SRS API latency target (95% under 500 ms), performance/security review, accessibility/responsive review, dependency scan, third-party penetration test, backup restore drill and operational monitoring review.
- [ ] Execute role-based UAT and full end-to-end dry run: Inquiry → Assessment/Quote → Caregiver Vetting/Readiness → Human Match → Agreement/Placement → Invoice/Payment → Follow-up → Safeguarding drill.
- [ ] Record training, cutover, rollback, incident response, support ownership and hypercare sign-off.

**Exit gate:** all in-scope SRS requirements have evidence, no unresolved critical/high risks remain, UAT is signed, and recovery/operations are ready.

## 4. Module completion evidence required

For each module, the Kiro task file must link each SRS acceptance item to:

1. Code location and migration/data model.
2. Authorization and privacy behavior, including direct URL/API/export/background job paths.
3. Automated unit/integration/property/security checks for business invariants.
4. Role-based UAT evidence, including success, denied access, invalid transition, failure/retry and concurrency cases where applicable.
5. Deployment/configuration prerequisites and operational monitoring evidence.

At minimum, verify the SRS critical scenarios AT-007 (readiness revocation), AT-013 (overlap lock), AT-018 (partial payment and overdue state), AT-022 (safeguarding isolation), and AT-053 (CMS live publishing), plus all BR-001–BR-023 rules that apply to the implemented release.

## 5. Existing detailed specs and code ownership

- CRM spec: `.kiro/specs/crm-lead-management/requirements.md`, `design.md`, and `tasks.md`. Its implementation paths predate the Admin/FrontEnd organization and its checked tasks require reconciliation before being used as current truth.
- CMS spec: `.kiro/specs/public-website-cms/requirements.md`, `design.md`, `tasks.md`, and `public-page-inventory.md`. Laravel CMS backend/admin exists; the public client and full launch acceptance remain open.
- Code organization and future placement rules: `docs/CODEBASE_STRUCTURE.md` and root `AGENTS.md`.
- Migration history remains in `database/migrations` for Laravel discovery. Menu creation remains in the single `database/seeders/Admin/MenuSeeder.php`.

## 6. Source and boundary notes

The final production SRS is the product source of truth. The older SRS is draft material only. Do not add clinical records, payroll, automated assignment/hiring/rejection, family/caregiver login portals, direct family-to-caregiver payments, GPS/biometric attendance, native mobile applications, or payment gateways unless an approved SRS change expands scope. Human decisions remain mandatory for matching and placement.
