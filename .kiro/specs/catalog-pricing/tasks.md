# Implementation Tasks — Catalog & Pricing Engine

**Final SRS coverage:** FR-060 (referenced in SRS; exact statement to verify), BR-005, §2.1(3), §8.1, AT-018 downstream price trace

## Tasks

- [ ] Extract and trace every catalog/pricing SRS criterion; confirm FR-060 reference and approved service/variant/pricing seed matrix.
- [ ] Complete audit and hardening of service/price CRUD and authorization (transaction-locked approval, nested service/version binding checks, approved/retired model immutability, draft-only deletion, and service-history delete protection are implemented; remainder needs acceptance).
- [ ] Complete database and model invariants for price immutability and non-overlapping approved periods (model lifecycle guard and service-row-locked overlap check implemented; database-level uniqueness and concurrency acceptance remain open).
- [ ] Complete quote/quote-line lifecycle and acceptance. Draft creation now snapshots the currently effective approved PriceVersion, one service line, BDT amount and quantity/unit; OM approval locks the quote and supports reasoned total override. Revision presentation, public/client consumption, expiry/withdrawal policy and downstream placement/invoice consumption remain open.
- [ ] Connect quote to Service Request (initial FK and exact price-version snapshot now exist); expose approved active values to CMS API and placements/invoices.
- [ ] Add role-based acceptance scenarios for approval separation, effective-date boundaries, concurrent approvals, historical reads and public API filtering.
- [ ] Record verification evidence and update the master traceability matrix before marking complete.

**Open decisions:** The SRS does not specify quote expiry, share/acceptance workflow, whether approved price overrides have a ceiling, or when quantity differs from one service unit. Current quote capture deliberately snapshots one unit with no automatic discount and requires an OM reason for any total override. Confirm those policies before claiming the quote workflow complete.
