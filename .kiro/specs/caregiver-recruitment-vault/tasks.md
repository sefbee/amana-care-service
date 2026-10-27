# Implementation Tasks — Caregiver Recruitment, Vetting & Document Vault

**Final SRS coverage:** FR-090, FR-092–FR-093, FR-110, FR-116, DS-02, FR-133–FR-134, RL-02, AT-007

## Tasks

- [ ] Trace all caregiver/vetting requirements and approve data classification, retention, role matrix, document types, training criteria and import rules.
- [ ] Provision and verify private S3, quarantine bucket, scanner, KMS/encryption and STS prerequisites.
- [x] Implement caregiver profile, encrypted NID hash, skills, availability, duplicate block and initial recruitment history.
- [ ] Complete import, full recruitment stage workflow, historical-check Needs Verification handling and duplicate review/link decisions. **Historical CSV import implemented:** RS-only template/form, 500-row/5 MB limits, row-level validation report, duplicate blocking via protected NID fingerprint, and operational provenance. Imported candidates start Not Ready; claimed historical outcomes are immutable Needs Verification records without evidence, excluded from readiness and OM/SL approval. Raw NID is excluded from persistence, report, activity values and validation flash sessions. Duplicate link/merge decision workflow remains open.
- [ ] Verify quarantined uploads, malware scan/promotion, metadata and secure reveal endpoint/audit in provisioned AWS. Code now requires two private S3 disks, verifies the complete quarantined byte count before scanning and checks quarantine/promotion/deletion write results, keeps scans fail-closed, promotes only clean files, and creates an object-scoped STS session/presigned URL for 300 seconds; infrastructure and security acceptance are pending.
- [ ] Implement checks, references, training, readiness evaluator and OM approval. Current implementation includes clean-evidence screening records, separate OM/SL approval of final checks and OM-only readiness approval, immutable approved results, training outcomes, and a fail-closed evaluator; reference checks, full evidence UI and continuously triggered recomputation remain open. Screening approval is separate from the caregiver-level readiness decision because the SRS role matrix lists both Screening Results approval and Readiness Gates approval.
- [x] Give Trainer a dedicated mandatory-training roster and attendance/outcome entry route, with minimum identity display, RL-05 scoping, role checks, readiness reevaluation and audit. OM has a read-only roster view per the SRS matrix; OM update/approval semantics and acceptance remain open.
- [ ] Implement idempotent expiry alerts/revocation and withdrawal from proposals; ensure no route can set Ready directly.
  - Partial implementation: the nightly job checks the latest passed Police check, creates durable RS alerts within 30 days and after expiry, reevaluates readiness, and changes matching Draft proposals to Withdrawn with immutable events after expiry. Screening, training, approval and profile/skill writes serialize on the caregiver row and recompute readiness; any failed readiness reevaluation withdraws Draft proposals. Alert acknowledgement is audited. SL/PSU safeguarding hold now recomputes readiness and withdraws Draft proposals atomically; expiry-job execution, alert visibility, matching-path and AT-007 acceptance remain open.
- [ ] Add role/record boundary and AT-007 acceptance coverage, including scanner outage, expired URL, denied reveal and import bypass cases.
- [ ] Record evidence in traceability matrix and operational runbook.

## Current implementation status

The profile/list/detail workflow is available to RS and OM. Raw National ID is transformed into a keyed HMAC blind index and an encrypted digest; only the fingerprint is used for uniqueness checks. Readiness is derived and cannot be written directly by a form. The caregiver migration and route/menu grant seeding have been applied.

This module is **partial**. Do not enable production screening until private S3, quarantine, scanner, and operational policies are provisioned. Secure upload/reveal, external readiness-alert delivery, expiry scheduler, import, hold-release policy, retention, and acceptance evidence remain open. Safeguarding hold application and draft proposal withdrawal are implemented. The skill taxonomy, gender values, mandatory training criteria, NID key rotation, and retention period require approved policy decisions.
