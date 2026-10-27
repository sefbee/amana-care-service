# Requirements — Matching & Placement Engine

**Final SRS coverage:** FR-140–FR-142, FR-144, FR-160–FR-162, AT-013, §2.2 exclusions

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Candidate filtering and proposals
1. Matching shall start from an eligible Service Request and hard-filter caregivers who are not Ready, lack the service skill, conflict with live-in/out requirements, violate schedule availability, or fail a documented hard constraint.
2. Remaining candidates may show soft-match indicators for language, proximity/zone and experience. The system shall explain included/excluded candidates and shall not make an automatic selection, assignment, hiring or rejection decision.
3. CO shall select 1–3 candidates for a proposal and provide a rationale of at least 20 characters. Proposals shall preserve status, human decision, family response and caregiver response history.
4. Family-facing candidate PDFs shall omit National ID, exact street address and last name, and shall not include other restricted data.

### 2. Placement and agreement
1. A placement shall capture household, caregiver, request, schedule, start/end, status and exact approved price version effective at activation. Price references remain immutable.
2. Activation shall require documented family acceptance and an uploaded signed agreement. No direct request or background job may bypass the gate.
3. The database transaction shall prevent overlapping active/scheduled placements for a caregiver down to the minute, including concurrent requests. Return a safe actionable overlap error.
4. Placement pause/end/cancel and replacement workflows shall be auditable. The system evaluates the configured 60-day replacement window; beyond policy, it stops for a recorded OM manual decision on penalty or waiver.

### 3. Human authority and privacy
1. Matching and placement decisions remain human-controlled. Every proposal, approval, rejection and override records its actor and rationale as required by the role matrix.
2. Record-level access, conflict-of-interest blocks, child-data redaction and restricted safeguarding holds apply to candidate and placement queries.

## Boundaries

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
