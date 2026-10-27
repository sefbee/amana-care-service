# Design — Quality Assurance & Safeguarding

**Blueprint:** `.kiro/IMPLEMENTATION_BLUEPRINT.md`  
**Master plan:** `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`  
**Traceability:** `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`

## Design

- Depends on Placement activation events, shared notification retry/priority delivery, Caregiver ReadinessEvaluator and shared record-level authorization.
- Separate routine Complaint/FollowUpTask from RestrictedIncident; protect incident queries with policies/scoped repositories, not UI hiding only. Minimize case data in notifications.
- Restricted incident read/denial audit records actor, action, case id, timestamp and result without duplicating allegations or reporter PII. OM has read-only case access; SL can acknowledge assigned alerts and apply holds; PSU sensitive access is explicit/audited and retains platform support controls; SA cannot retrieve case details.
- Follow-up generation is idempotent by placement and milestone; scheduled retries cannot duplicate tasks.
- Follow-up task notes are encrypted at the model cast; attempt rows and task completion are immutable/audited. QS/PSU can record outcomes; OM and SL have read access; CO read access is limited to placements on their assigned Service Requests. Every non-PSU read/write checks the active user's RL-05 blocks. SA and other roles are denied. The role matrix's extra OM create/update/approve powers require a defined task-management workflow before they are enabled.
- Serious-incident category, summary and narrative are encrypted; validation failures must not flash narrative fields into session storage. Alert bodies contain no case narrative or reporter identity. A durable delivery outbox records channel, retry state, sanitized error code, delivered time and dead-letter state. High priority is sent immediately, independent of quiet-hour rules; a minute job records acknowledgement target breaches.
- Incident severity definitions, designated SL recipient rules, notification provider SLAs and acknowledgement behavior require Operations approval before final schema/automation.

## Routine complaint implementation

- Routine complaint intake uses `routine_complaints` and append-only `routine_complaint_events`; it has no foreign-key or polymorphic link to restricted incident records.
- QS and PSU can access the register. Only active QS users can own complaints; PSU may access for platform support and every sensitive view/action is logged.
- Subject, details, resolution and event rationale use Laravel encrypted casts. The list excludes complaint body text, and intake validation does not flash subject/details into the session.
- Household is required; placement is optional and must belong to the selected household. Intake facts are immutable. Status transitions are Open → In Progress/Escalated, In Progress → Escalated/Resolved and Escalated → In Progress/Resolved. Resolution requires a note, actor and timestamp; resolved records cannot be edited or reassigned.
- Escalated is only a routine status. Recipient, notification, category, reopening and retention rules remain open; do not route or notify until approved. Serious allegations use the separate restricted incident workflow.
