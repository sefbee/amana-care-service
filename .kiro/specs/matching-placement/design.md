# Design — Matching & Placement Engine

**Blueprint:** `.kiro/IMPLEMENTATION_BLUEPRINT.md`  
**Master plan:** `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`  
**Traceability:** `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`

## Design

- Depends on Service Request/Assessment, Catalog Quote/PriceVersion, Caregiver Readiness and shared record-level authorization.
- Proposed entities: MatchRun (criteria and timestamp), MatchCandidate/MatchReason, MatchProposal, ProposalDecision/Response, Placement, PlacementSchedule, AgreementDocument and ReplacementCase.
- Implement filtering as a deterministic query/service with reason codes, not an assignment algorithm. Persist proposal rationale and candidate snapshot sufficient for audit without duplicating restricted PII.
- For concurrency, use a database-enforced strategy compatible with the provisioned engine. SRS requires exclusion at the database/transaction level; confirm engine support before choosing PostgreSQL range exclusion or an equivalent locking/constraint design. No UI-only overlap check is acceptable.
- Placement copies exact price version and agreement reference. Finance reads its immutable price snapshot.
- Candidate PDF uses an explicit allowlist DTO and branded template; never serialize a Caregiver model directly.
