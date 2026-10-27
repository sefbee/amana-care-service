# Requirements — Catalog & Pricing Engine

**Final SRS coverage:** FR-060 (referenced in SRS; exact statement to verify), BR-005, §2.1(3), §8.1, AT-018 downstream price trace

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Service catalog
1. The catalog shall contain the four SRS core services (Maid, Nanny, Elderly Care, Cook), their approved variants, active status and version history. Exact names, variants and founding prices must be reconciled to the SRS/business-approved catalog before seeding.
2. Catalog edits shall be authorized by the SRS role matrix, validated server-side, and auditable. Existing request, quote, placement and invoice references shall remain stable when a service definition changes.

### 2. Price versions and immutability
1. Each price version shall have currency, amount, effective period, lifecycle status, actor/approval timestamps and a service reference. BDT shall be formatted consistently in staff and public surfaces.
2. Approved versions shall not be edited or deleted. Referenced versions shall remain available as immutable financial history; corrections require a new version or an approved compensating record.
3. The system shall prevent overlapping effective approved versions for the same service/variant and shall select the version effective at the transaction time.
4. OM approval and quote override actions shall require authorization and an audit reason; creators cannot approve their own versions if the SRS separation requires another approver.

### 3. Quotes and consumers
1. Staff shall create, revise, expire and approve quotes for Service Requests using exact approved price-version references and deterministic totals.
2. Quote snapshots shall preserve quantities, unit amounts, discounts/adjustments only as allowed by approved policy, totals, currency, actor and price version.
3. CMS Service Grid and later placement/invoice flows shall read active approved catalog values from the same source; draft/unapproved values shall never be exposed publicly.
4. The module shall preserve history and activity/audit records for catalog, price, quote and approval changes.

## Boundaries

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
