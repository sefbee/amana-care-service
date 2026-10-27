# Design — Finance & Operations

**Blueprint:** `.kiro/IMPLEMENTATION_BLUEPRINT.md`  
**Master plan:** `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`  
**Traceability:** `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`

## Design

- Depends on approved PriceVersion/Quote and active Placement; billing contact is resolved through Household contact invariants.
- Proposed entities: Invoice, InvoiceLine, Payment, PaymentAllocation, Receipt, Reconciliation, FinancialAdjustment/CreditNote, InvoiceSequence and ReplacementPolicyDecision. Define multi-invoice reconciliation based on the SRS role/workflow; no family payment portal.
- Snapshot service/item description, unit price, quantity, currency, tax/discount only if approved, and exact price version at invoice creation. Financial records are immutable; corrections are additive and audited.
- Invoice generation, overdue marking and notifications are idempotent scheduled jobs with retry visibility. Sequence allocation must be transactional.
- Reports/export use scoped query services and watermark metadata. Avoid exporting document, safeguarding or unnecessary household PII.

## Implementation state and open decisions (2026-10-27)

- A first ledger slice now exists: immutable invoice/line/payment/allocation snapshots, transactional sequence allocation, idempotent draft generation, FN issue/payment/reconciliation screens, receipts and overdue processing. Reversal/adjustment/credit/penalty/replacement, finance exports, and acceptance evidence remain unimplemented.
- Draft generation is disabled unless `FINANCE_BILLING_CYCLE=monthly_in_advance` **and** `FINANCE_RECURRING_AMOUNT_BASIS=placement_unit_price` are explicitly configured. Do not set these merely to enable a job.
- Product-owner decisions needed: billing-cycle timing; whether recurring invoices use the placement unit price or approved quote-total override and how override is allocated over periods; partial-month proration; issue/due-date policy; rounding for non-integer day quantities; correction/reversal/overpayment treatment; 60-day replacement fee/waiver rules; approved financial export roles and watermark format.
- Until these decisions are approved, FN selects due dates per invoice, monthly partial periods are skipped, and the generator does not run. No payment reversal or adjustment is simulated by editing immutable ledger rows.
