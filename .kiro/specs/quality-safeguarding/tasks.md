# Implementation Tasks — Quality Assurance & Safeguarding

**Final SRS coverage:** FR-220–FR-221, FR-232, SG-06, RL-03, AT-022, §10 follow-up job

## Tasks

- [ ] Approve serious incident definitions, severity, SL assignment/backup, acknowledgement semantics and retention policy.
- [ ] Implement placement follow-up task scheduling, queue/worklist, attempt outcomes and routine complaint workflow. **Follow-up portion implemented:** activation creates unique Day 7/30/60 tasks; QS/PSU can record one terminal Satisfied/Concern/Escalate outcome per task with encrypted notes and immutable attempt audit; OM/SL can read all tasks and CO can read only tasks linked to its assigned Service Requests. RL-05 is enforced on non-PSU paths. OM follow-up create/update/approve actions from the role matrix remain open. **Complaint slice implemented:** QS/PSU register, encrypted intake/resolution, QS ownership, controlled status/assignment history and role-scoped views. Escalation recipient/notification policy and runtime/role acceptance remain open.
- [ ] Implement isolated restricted-incident capture, severity routing and role-scoped case view. **Core capture/access implemented:** any active staff member can report; case narratives are encrypted and immediately classified Restricted; SL gets durable high-priority in-app alert rows; OM read-only and SL/PSU web/API reads are audited; only SL/PSU can acknowledge or apply caregiver hold; unauthorized direct reads return 403 and are audited. Email/SMS, 15-minute SLA, severity policy and acceptance remain open.
- [ ] Add direct URL/API authorization, generic banners, denied-access audit and leak checks for tables/search/exports/jobs/notifications. **Direct web/API case reads and generic reporter response implemented;** search/export/job leak review and negative acceptance remain open.
- [ ] Implement immediate high-priority notification, retries, acknowledgement timestamps and 15-minute SLA monitoring. Durable in-app alerts, immediate generic email/SMS attempts, 1/5/30/120-minute retry scheduling, dead-letter state, acknowledgement timestamps and a minute SLA monitor are implemented. Configure real provider credentials and verify delivery, retry, DLQ and 15-minute operational behavior.
- [x] Implement SL On Hold action and atomic readiness/matching/proposal revocation. Hold, readiness recomputation and Draft proposal withdrawal run in one transaction; verify all remaining candidate/active placement paths in acceptance.
- [ ] Verify AT-022 including reporter/CO access, direct URL manipulation, export paths and PSU audited access.
- [ ] Record SLA, privacy, and operational drill evidence.

## Routine complaint policy notes (2026-10-27)

- Routine complaints are stored in a separate table/model from Restricted Serious Incidents. Notes and resolution are encrypted; access is QS/PSU only and sensitive reads are audited.
- Implemented statuses are Open, In Progress, Escalated and Resolved. Escalated is a routine workflow state only; it does not create a serious incident or trigger an alert.
- Operations still needs to define escalation recipient/backup, notification behavior, category taxonomy, reopen policy and retention. A complaint involving abuse, severe injury, theft or legal escalation must also be entered through the separate Serious Incident workflow to activate Restricted controls and SL alerting.
