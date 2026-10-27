# Requirements — Finance & Operations

**Final SRS coverage:** BR-005–BR-006, FR-181, FR-190–FR-192, FR-195–FR-196, FR-198, EX-05, AT-018

The final production SRS is authoritative. These requirements are a planning decomposition; resolve marked policy/infrastructure decisions before implementation and trace every criterion in `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`.

## Requirements

### 1. Invoices and price snapshots
1. The invoice generator shall evaluate active placements on the approved billing schedule and create an idempotent draft invoice.
2. Invoice lines shall derive quantity and unit amount from the placement's immutable price snapshot; calculations, currency and rounding must be reproducible.
3. Invoice numbers shall be unique, sequential and gapless as required by the SRS. The sequencing transaction must tolerate concurrent jobs and rollbacks without gaps.
4. Invoices shall route to the Household Billing Contact. Status, due date, issued amount, line snapshots and changes are auditable.

### 2. Payments and reconciliation
1. FN shall record manual payment to Amanah with allowed method, amount, date and reference. Family-to-caregiver payments and gateways are out of scope.
2. Balance is total less valid payments; status transitions include partial and paid states. Receipts are generated for recorded payments.
3. Payment is Unreconciled until FN matches the reference to the bank statement and reconciles it. Reconciled payments cannot be deleted or mutated; corrections use controlled reversal/adjustment records.
4. Overdue processing marks issued invoices overdue when due date passes and balance remains positive.

### 3. Adjustments and replacement policy
1. Only authorized roles may create adjustments, credits, penalties or waivers; required reason/approval and immutable ledger history are enforced.
2. Replacement evaluation calculates days elapsed from placement start. After the configured 60-day window, automated flow stops and requires OM to record penalty or waiver decision.
3. Exports are permission controlled and apply SRS watermarking; reports expose only approved financial data.

## Boundaries

No clinical recordkeeping, payroll, automatic matching/assignment/hiring/rejection, family/caregiver portal, family-to-caregiver payment, native app, GPS/biometric tracking or online gateway is in scope without an approved SRS change. Human decisions and SRS privacy controls are mandatory.
