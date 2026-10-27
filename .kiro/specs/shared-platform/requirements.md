# Requirements — Shared Platform, Identity, Security & Operations

**Final SRS coverage:** §3.1–3.3, RL-01–RL-06, NFR-001, NFR-020–NFR-029, NFR-071, §10–§12

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Identities and authorization
1. Implement SRS roles SA, OM, CO, RS, TR, FN, QS, SL, CM and EX with explicit permissions; enforce both action authorization and record-level scopes on every web/API/export/job path.
2. Platform Superuser (PSU) is separate from SRS System Administrator (SA). PSU is unrestricted for platform/business actions but every sensitive read, document reveal, safeguarding access and override is explicitly audited. SA remains denied PII, vault details and safeguarding case details.
3. Enforce assigned ownership, child-record redaction, conflict-of-interest soft blocks, safeguarding isolation and export permission/watermark requirements.
   - **RL-05 implementation rule:** An OM or audited PSU may impose/revoke a reasoned block between one active staff account and exactly one Household or Caregiver. The subject cannot browse, open, match, export through covered screens, or mutate that target. Related CRM leads and caregiver references are filtered/redacted. Block history is append-only; every impose/revoke and privileged register access is audited without copying the free-text reason into general activity properties. PSU remains unrestricted and audited.
4. **Final SRS requirement:** MFA via TOTP is mandatory for SA, OM, FN, SL and RS. Login is limited to 5 attempts per 15 minutes per IP. Account deactivation/role revocation terminates sessions within 60 seconds.
5. Permission checks use only an active role assigned to the authenticated user. Permission caches are isolated by user and active role and invalidated when a role's grants or a user's active role assignments change.

### Explicit implementation deviation — 2026-10-27

The user explicitly directed removal of two-factor authentication/MFA. Accordingly, TOTP enrollment, challenges, recovery codes, MFA user fields and mandatory-MFA route gates are removed from the application. The existing login throttle and account/role authorization remain. This is a deliberate deviation from the final SRS requirement above; the application must not be represented as conformant to that MFA requirement unless the product owner formally updates the SRS/security policy. Existing MFA secret and recovery-code values are deleted by migration and cannot be restored by rollback.

### 2. Audit, notifications and jobs
1. Consequential state changes, approvals, sensitive reads/reveals, exports, overrides and denied restricted access are attributable and tamper-evident. Logs redact secrets and unnecessary PII.
2. Notifications support in-app/email/SMS, consent at send time, priority/quiet-hour rules, exponential retry and dead-letter queue.
3. Scheduled jobs are idempotent, observable, retryable and locked against concurrent duplication; implement SRS job times and operational alerting.

### 3. Security, performance and operations
1. Enforce TLS 1.2+, HSTS, CSP, ORM parameterization, managed secrets, encrypted volumes and sensitive field encryption.
2. Meet ASVS L2 control baseline, dependency scanning in CI/CD, 95th percentile CRUD/API under 500ms and stateless horizontal scaling.
3. Provide encrypted backups, restore drills, retention/PII scrubbing only after legal sign-off, controlled migration/import and operational monitoring.
4. Exports contain hidden-sheet/metadata watermark with user ID, timestamp and IP. Executive/Auditor reports are aggregated and anonymized.

### Open export-format decision

The SRS asks for a hidden worksheet and workbook metadata for CSV as well as Excel exports, but CSV has no worksheet or document-property container. No CSV export exists in this repository; the current report is XLSX-only and watermarked. Before adding CSV export, obtain an approved format-specific watermark rule (for example, a signed companion manifest or a different export format); do not silently claim a hidden-sheet watermark for CSV.

### Open quiet-hour policy decision

The SRS requires ordinary notification quiet-hour behavior but specifies no start time, end time or operational timezone. These values are configurable as `NOTIFICATIONS_QUIET_HOURS_START`, `NOTIFICATIONS_QUIET_HOURS_END` and `NOTIFICATIONS_QUIET_HOURS_TIMEZONE`; external generic email/SMS attempts remain queued when the window is missing or invalid. The separate high-priority restricted safeguarding alert path bypasses quiet hours as explicitly required by the SRS. Obtain approval for the ordinary window and provider configuration before production delivery acceptance.

## Boundaries

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
