# Implementation Tasks — Finance & Operations

**Final SRS coverage:** BR-005–BR-006, FR-181, FR-190–FR-192, FR-195–FR-196, FR-198, EX-05, AT-018

## Tasks

- [ ] Extract finance rules and resolve billing cycle, sequential invoice algorithm, rounding, adjustment limits, reconciliation allocation, receipt and reversal policy. **Open:** cycle, quote override allocation, partial-month proration, due-date, reversal/adjustment and replacement fee/waiver decisions.
- [x] Apply RL-05 household blocks to invoice/payment queues, details, receipt/reconcile paths, invoice allocation choices and payment recording. Finance read access is billing-only through the household scope; direct CRM household routes remain permission-gated.
- [x] Grant OM read-only access to invoice/payment registers, invoice details and receipts, and assigned CO read-only access to invoices; keep invoice issue, payment entry and reconciliation restricted to FN/PSU. CO cannot see payment rows or receipts through invoice detail.
- [x] Implement initial invoice/line/sequence schema, immutable placement price snapshots, transactional gapless sequence allocation and idempotent draft generator. **Partial:** generator remains gated on explicit `FINANCE_BILLING_CYCLE` and `FINANCE_RECURRING_AMOUNT_BASIS`; non-integer billing units remain unsupported.
- [x] Implement initial issue/due/overdue transitions, Billing Contact snapshot, balances, partial/full payment states and receipt PDF. **Partial:** FN chooses due date; delivery/notification and policy-approved due date remain open.
- [x] Implement manual Amanah payment entry, same-household multi-invoice allocations and unreconciled/reconciled lock. **Open:** controlled reversal/adjustment/credit/penalty workflows and overpayment policy.
- [ ] Implement replacement policy calculation and OM manual decision after 60 days.
- [ ] Add permission-scoped finance dashboards and watermarked exports.
- [ ] Verify FR-190–198 and AT-018 including partial payment, concurrency, idempotency, overdue and immutable-history cases.
- [ ] Record audit, operational and acceptance evidence.
