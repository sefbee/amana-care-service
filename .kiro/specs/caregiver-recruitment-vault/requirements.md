# Requirements — Caregiver Recruitment, Vetting & Document Vault

**Final SRS coverage:** FR-090, FR-092–FR-093, FR-110, FR-116, DS-02, FR-133–FR-134, RL-02, AT-007

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Candidate profile and recruitment
1. RS staff shall create and manage caregiver profiles with name, DOB, matching-only gender preference, phone, encrypted unique National ID hash, service skills, live-in preference, availability date and zone preferences. Raw National ID shall not be persisted.
2. The system shall support duplicate review and recruitment stage history without automated hiring/rejection decisions. RS CSV imports shall preserve supplied historical screening outcomes as unverified claims, set each imported check to Needs Verification, and always create caregivers as Not Ready. Unverified imported results cannot be approved or satisfy a readiness gate; verification requires a later operational check with clean evidence.

### 2. Sensitive document vault
1. Identity, police and psychological documents shall use private S3 storage with Block Public Access. The database stores storage keys and allowed metadata, never public paths or document bytes.
2. Uploads shall first enter quarantine and become available only after a clean malware scan. Failed/incomplete scans remain inaccessible and visible to authorized staff as a blocked state.
3. Only authorized RS, OM, SL and approved Platform Superuser actors may reveal eligible documents, subject to the approved role matrix. A reveal requires a predefined reason and returns a user-bound signed URL expiring at 300 seconds.
4. Every reveal attempt (allowed or denied), actor, reason, document, timestamp and result shall be audited without logging file contents or signed URL secrets. SRS SA is denied document reveal.

### 3. Screening, training and readiness
1. Screening records shall capture type, outcome, reviewer, dates, validity and evidence; mandatory training shall capture attendance and outcome.
   - Trainer role receives a dedicated mandatory-training roster and can record attendance/outcomes without access to caregiver profiles, contact details, vetting documents, or readiness controls. The roster is filtered through the RL-05 caregiver scope.
2. Ready is derived only when identity passed, police passed and unexpired, psychological passed, all mandatory training complete, no active safeguarding block, and OM approval exists.
   - Each final screening result requires separate auditable OM or SL approval per the role matrix; this is distinct from the caregiver-level OM readiness decision. Pending results cannot be approved, and approved screening results are immutable. A correction is recorded as a new screening check.
3. Expiry/failure/hold shall revoke Ready and remove the caregiver from eligible matching and draft proposals. A nightly job alerts RS about checks expiring in 30 days and marks expired candidates Not Ready.
4. Users may not bypass readiness by directly changing a status, importing a record, calling an API or editing a proposal.

## Boundaries

The final SRS role matrix lists OM training permissions as RUA, while the role definition describes TR as the restricted role for attendance/outcome entry and the current data model has no separate training approval state. Until this is clarified, only TR may record attendance/outcomes; OM readiness approval remains on the distinct caregiver readiness workflow.

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
