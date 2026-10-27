# Requirements — Quality Assurance & Safeguarding

**Final SRS coverage:** FR-220–FR-221, FR-232, SG-06, RL-03, AT-022, §10 follow-up job

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Follow-ups and routine complaints
1. Placement activation shall spawn idempotent QS tasks for day 7, 30 and 60. Tasks appear only to authorized staff and assigned-record rules apply.
2. QS shall record contact attempt, timestamp, Satisfied/Concern/Escalate outcome and notes. Escalation creates a controlled follow-up and notification.
3. The SRS role matrix grants OM create/read/update/approve, CO assigned-record read, QS create/read/update, and SL read access to follow-ups. Current screens enforce OM/SL read access, CO assigned-Service-Request read scope, and QS outcome recording. The additional OM task-management/approval actions have no matching workflow definition in FR-220/FR-221 and remain an explicit product decision; do not mutate generated tasks or QS outcome history without defining those actions.
4. Routine complaints shall have ownership, status, related household/placement, resolution and audit history; routine complaints are kept separate from restricted serious incidents.

### 2. Restricted serious incidents
1. Any staff member may report a serious incident. Save immediately classifies it Restricted and creates a high-priority alert for active SL users. Do not infer incident severity tiers; definitions and operational SLA policy remain external decisions to approve.
2. Reporter and ordinary CO/QS users shall not see restricted case notes or reporter identity after submission. They may see only the generic “Restricted Case Active - Contact SL” banner. OM has audited read-only case access; SL and approved Platform Superuser access is audited and may perform authorized safeguarding actions. SRS SA is restricted. OM cannot acknowledge alerts or apply caregiver holds.
3. Unauthorized direct route/API attempts return 403 and create a privacy-safe denied-access audit event. Restricted data must not leak in search, DataTables, exports, email/SMS payloads, logs or dashboards.
4. High-priority in-app/email/SMS alerts to SL bypass quiet hours and record delivery and acknowledgement time; target acknowledgement is within 15 minutes.
5. SL can place an associated caregiver On Hold; this immediately revokes Ready status and excludes them from matching/proposals globally.

## Boundaries

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
