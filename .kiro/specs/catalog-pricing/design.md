# Design — Catalog & Pricing Engine

**Blueprint:** `.kiro/IMPLEMENTATION_BLUEPRINT.md`  
**Master plan:** `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`  
**Traceability:** `.kiro/traceability/FINAL_SRS_TRACEABILITY.md`

## Design

- Existing foundation: `app/Admin/Models/Service.php`, `PriceVersion.php`, service/price controllers and migrations. Audit before extending.
- Add quote and quote-line entities referencing service and immutable `price_version_id`; snapshot amount/currency and accepted quantity at quote time. Link quotes to the CRM Service Request.
- Keep service definitions separate from temporal price versions. Do not update an approved amount in place. Use transactions/locking for approval and effective-date overlap validation.
- Service definitions may record OM-configured `required_skill_keys` from the caregiver skill taxonomy. An empty mapping must disable candidate matching for that service; do not infer requirements from a service name.
- Public CMS API consumes only active services with currently approved indicative prices. The API response must not expose internal cost, notes, draft quote, or unapproved prices.
- Admin pages live under `resources/views/admin/catalog`; routes in `routes/admin.php`; follow root UI/RBAC/DataTable conventions.
- Open decision: verify precise core catalog variants and approval/quote adjustment policy against the final SRS/business sign-off. Confirm storage precision and currency rounding rules before migration.
