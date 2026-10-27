# Design — Caregiver Recruitment, Vetting & Document Vault

**Blueprint:** `.kiro/IMPLEMENTATION_BLUEPRINT.md`  
**Master plan:** `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`  
**Traceability:** `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`

## Design

- Proposed aggregates: Caregiver, CaregiverSkill, Availability, CandidateStageHistory, CaregiverDocument, ScreeningCheck, TrainingModule, TrainingRecord, ReadinessApproval and RevealAudit. Define retention and foreign-key behavior with legal sign-off.
- Store only a keyed/encrypted stable NID hash for deduplication; choose field encryption/key rotation and uniqueness behavior before schema freeze.
- Upload pipeline: private quarantine disk → asynchronous scanner → promote clean file to private vault → persist metadata/status. Fail closed when scanner/storage is unavailable.
- Reveal flow uses a server authorization endpoint and S3 STS or equivalent short-lived, user-bound access; do not create public routes. URL duration is exactly five minutes.
- One domain ReadinessEvaluator computes readiness from current records. All write paths invoke it; expiry and safeguard hold dispatch reevaluation.
- Historical caregiver CSV import is RS-only, row-validated, duplicate-blocked by the protected NID fingerprint, limited to 500 rows and 5 MB, and does not accept a readiness or verification override. Imported profiles and historical outcome claims are tagged `Historical Import`; claims are retained with `Needs Verification` and no evidence link. Imports never update an existing profile or screening record. Validation reports identify only row numbers and field names, never submitted values or National IDs.
- Treat screening-result approval and caregiver readiness approval as separate decisions, matching the SRS role matrix. OM or SL approves a non-pending screening result with a reason; the approval actor/time/reason are retained, approved result fields cannot be edited, and corrections are recorded as new checks. Only a passing, current approved check satisfies that readiness gate; OM alone records the caregiver-level readiness approval.
- Admin UI/routes under `app/Admin` and `resources/views/admin/caregivers`; operational roles only. Infrastructure credentials are deployment-managed secrets.
