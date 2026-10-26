# Implementation Plan: Module 1 — CRM & Lead Management

## Overview

Implement the CRM & Lead Management module incrementally, following existing codebase conventions (Laravel 12, PHP 8.2, Blade + Livewire 3, Yajra DataTables, SoftDeletes, Spatie ActivityLog, `role_permission_check` middleware). Each task builds directly on the previous one. All code must match the patterns in `UserController`, `User` model, and the `users` views exactly.

---

## Tasks

- [x] 1. Create `config/crm.php`
  - Create `config/crm.php` returning the four keys: `dhaka_zones`, `lost_reasons`, `non_clinical_keywords`, and `age_bands` as defined in the design document
  - `non_clinical_keywords` should be an empty array (loaded from env/config later)
  - _Requirements: 4.1, 11.4, 17.4_

- [x] 2. Write all 7 database migrations
  - [x] 2.1 Create migration `create_leads_table`
    - Columns: `id` (increments), `full_name` (varchar 100), `phone` (varchar 20), `normalized_phone` (varchar 20), `email` (varchar 254, nullable), `channel` (enum), `service_interest` (varchar 500, nullable), `area` (varchar 100, nullable), `live_in_pref` (enum, nullable), `preferred_start_date` (date, nullable), `budget_expectation` (decimal 10,2, nullable), `notes` (text, nullable), `status` (enum, default New), `consent_status` (enum, default Unknown), `lost_reason` (varchar 100, nullable), `lost_reason_notes` (varchar 500, nullable), `waitlist_priority` (unsignedInteger, nullable), `override_reason` (varchar 500, nullable), `household_id` (unsignedInteger, nullable, FK households), `owner_id` (unsignedInteger, nullable, FK users), `is_active` (boolean, default true), `created_by` (unsignedInteger, nullable, FK users), `softDeletes`, `timestamps`
    - _Requirements: 1.1–1.15_

  - [x] 2.2 Create migration `create_lead_communication_logs_table`
    - Columns: `id`, `lead_id` (unsignedInteger, FK leads), `contact_date` (datetime), `direction` (enum: Inbound, Outbound), `channel` (enum: WhatsApp, Email, Phone, In-Person, Other), `summary` (varchar 1000), `next_step` (varchar 500, nullable), `created_by` (unsignedInteger, nullable, FK users), `softDeletes`, `timestamps`
    - _Requirements: 5.1–5.9_

  - [x] 2.3 Create migration `create_lead_consent_records_table`
    - Columns: `id`, `lead_id` (unsignedInteger, FK leads), `channel` (enum same as Lead channel), `status` (enum: Unknown, Granted, Withdrawn), `source` (varchar 200, nullable), `consented_at` (datetime, nullable), `withdrawn_at` (datetime, nullable), `created_by` (unsignedInteger, nullable, FK users), `timestamps` (no softDeletes — append-only audit)
    - _Requirements: 6.1–6.5_

  - [x] 2.4 Create migration `create_lead_status_histories_table`
    - Columns: `id`, `lead_id` (unsignedInteger, FK leads), `from_status` (varchar 50), `to_status` (varchar 50), `changed_by` (unsignedInteger, FK users), `timestamps` (no softDeletes — audit table)
    - _Requirements: 13.5_

  - [x] 2.5 Create migration `create_households_table`
    - Columns: `id`, `display_name` (varchar 100), `area` (varchar 100), `address_line` (text, nullable), `access_notes` (varchar 500, nullable), `primary_language` (enum: Bangla, English, Other), `status` (enum: Onboarding, Active, On Hold, Closed, default Onboarding), `founding_cohort` (boolean, default false), `assigned_coordinator_id` (unsignedInteger, FK users), `is_active` (boolean, default true), `created_by` (unsignedInteger, nullable, FK users), `softDeletes`, `timestamps`
    - _Requirements: 8.1–8.10_

  - [x] 2.6 Create migration `create_household_contacts_table`
    - Columns: `id`, `household_id` (unsignedInteger, FK households), `name` (varchar 150), `role` (enum: Primary, Billing, Emergency), `phone` (varchar 20), `email` (varchar 254, nullable), `is_active` (boolean, default true), `softDeletes`, `timestamps`
    - _Requirements: 9.1–9.7_

  - [x] 2.7 Create migration `create_care_recipients_table`
    - Columns: `id`, `household_id` (unsignedInteger, FK households), `first_name` (varchar 100), `recipient_type` (enum: Child, Adult, Senior), `date_of_birth` (date, nullable), `age_band` (varchar 20, nullable), `relationship` (varchar 100, nullable), `support_notes` (text, nullable), `routines` (text, nullable), `dietary_restrictions` (varchar 500, nullable), `is_sensitive` (boolean, default false), `created_by` (unsignedInteger, nullable, FK users), `softDeletes`, `timestamps`
    - _Requirements: 10.1–10.8_

- [x] 3. Create all 7 Eloquent models
  - [x] 3.1 Create `App\Models\Lead`
    - Use `SoftDeletes`, `CommonAttributes`, `BuildsQueries` traits; set `$fillable`, `$hidden`, `$appends = ['status_badge']`, and `casts()` exactly as specified in the design
    - Add `getStatusBadgeAttribute()` returning an HTML badge span based on `status` value
    - Add relationships: `owner()` BelongsTo User, `creator()` BelongsTo User (FK `created_by`), `household()` BelongsTo Household, `communicationLogs()` HasMany LeadCommunicationLog, `consentRecords()` HasMany LeadConsentRecord, `statusHistories()` HasMany LeadStatusHistory
    - _Requirements: 1.1–1.15, 13.1_

  - [x] 3.2 Create `App\Models\LeadCommunicationLog`
    - Use `SoftDeletes`; set `$fillable` for all columns; add `lead()` BelongsTo Lead and `creator()` BelongsTo User (FK `created_by`)
    - _Requirements: 5.1–5.9_

  - [x] 3.3 Create `App\Models\LeadConsentRecord`
    - No SoftDeletes (append-only); set `$fillable`; add `lead()` BelongsTo Lead and `creator()` BelongsTo User (FK `created_by`); cast `consented_at` and `withdrawn_at` to datetime
    - _Requirements: 6.1–6.5_

  - [x] 3.4 Create `App\Models\LeadStatusHistory`
    - No SoftDeletes (audit table); set `$fillable`; add `lead()` BelongsTo Lead and `changedBy()` BelongsTo User (FK `changed_by`)
    - _Requirements: 13.5_

  - [x] 3.5 Create `App\Models\Household`
    - Use `SoftDeletes`, `CommonAttributes`, `BuildsQueries`; set `$fillable`, `$hidden = ['address_line', 'deleted_at']`, `$appends = ['status_badge']`; add `casts()` for `founding_cohort` (boolean), `is_active` (boolean)
    - Add relationships: `coordinator()` BelongsTo User (FK `assigned_coordinator_id`), `creator()` BelongsTo User, `contacts()` HasMany HouseholdContact, `careRecipients()` HasMany CareRecipient, `leads()` HasMany Lead
    - _Requirements: 8.1–8.10, 12.5, 14.7_

  - [x] 3.6 Create `App\Models\HouseholdContact`
    - Use `SoftDeletes`; set `$fillable`; add `household()` BelongsTo Household
    - _Requirements: 9.1–9.7_

  - [x] 3.7 Create `App\Models\CareRecipient`
    - Use `SoftDeletes`; set `$fillable`; add `household()` BelongsTo Household and `creator()` BelongsTo User; cast `date_of_birth` to date and `is_sensitive` to boolean
    - _Requirements: 10.1–10.8_

- [x] 4. Implement `LeadService`
  - [x] 4.1 Create `App\Services\LeadService` with `normalizePhone(string $raw): string`
    - Strip `+88`, `0088`, spaces, hyphens, parentheses; validate result matches `01[3-9][0-9]{8}` or international E.164; throw `ValidationException` with descriptive message on failure
    - _Requirements: 1.2, 1.3, 16.1–16.6_

  - [ ]* 4.2 Write property test for `normalizePhone` (Property 1)
    - **Property 1: Phone normalization round-trip equivalence**
    - Generate variants: `+8801712345678`, `01712345678`, `017-1234-5678`, `0088 017 1234 5678`; assert all produce identical `01XXXXXXXXX` output
    - **Validates: Requirements 1.2, 1.3, 16.1, 16.5, 16.6**

  - [x] 4.3 Add `getAllowedTransitions(): array` and `validateTransition(string $from, string $to): bool` to `LeadService`
    - Encode the full status transition map from the design as an associative array; `validateTransition` returns true IFF `to` is in `getAllowedTransitions()[$from]`
    - _Requirements: 3.1, 3.2_

  - [ ]* 4.4 Write property test for `validateTransition` (Property 2)
    - **Property 2: Status transition validity**
    - Generate random `(from, to)` pairs from the Lead status enum; assert accepted IFF pair is in allowed map
    - **Validates: Requirements 3.1, 3.2**

  - [x] 4.5 Add `recordStatusHistory(Lead $lead, string $from, string $to): void` to `LeadService`
    - Creates a `LeadStatusHistory` row using `auth()->id()` as `changed_by`
    - _Requirements: 13.5_

  - [x] 4.6 Add `detectDuplicates(string $normalizedPhone, ?string $email, ?int $excludeId = null): Collection` to `LeadService`
    - Query non-deleted `leads` by `normalized_phone`; query `households` contacts by normalized phone; query both by case-insensitive email; return collection of matching records with `id`, `full_name`/`display_name`, `status`, `created_at`; exclude `excludeId` when provided
    - _Requirements: 2.1–2.3, 2.7_

  - [ ]* 4.7 Write property test for `detectDuplicates` (Property 10)
    - **Property 10: Duplicate detection normalizes before comparison**
    - Generate Lead records; submit new Lead with same phone in different formatting or same email in different case; assert duplicate response contains required fields
    - **Validates: Requirements 2.1, 2.2, 2.3**

- [x] 5. Implement `HouseholdService`
  - [x] 5.1 Create `App\Services\HouseholdService` with `convertFromLead(Lead $lead, array $extraData = []): Household`
    - Wrap in `DB::transaction()`; create Household with `display_name = lead.full_name`, `area = lead.area`, `assigned_coordinator_id = lead.owner_id`, `status = Onboarding`; create Primary HouseholdContact from lead phone/email/full_name; set `lead->household_id`; set `lead->status = Converted`; call `LeadService::recordStatusHistory()`; dispatch Spatie ActivityLog; return Household
    - _Requirements: 3.5, 7.1–7.6_

  - [ ]* 5.2 Write property test for `convertFromLead` (Property 5)
    - **Property 5: Lead-to-Household conversion preserves data**
    - Generate random Qualified Leads; trigger conversion; assert Household fields match Lead fields and `lead->household_id` is set
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**

  - [x] 5.3 Add `guardLastPrimaryContact(Household $household, int $contactId): void` to `HouseholdService`
    - Count non-deleted contacts where `role = Primary` excluding `$contactId`; throw `ValidationException` if count is 0
    - _Requirements: 9.6, 9.7_

  - [ ]* 5.4 Write property test for `guardLastPrimaryContact` (Property 6)
    - **Property 6: Household always has at least one Primary contact**
    - Generate Household with random contacts; attempt to delete down to zero Primary contacts; assert final delete is rejected
    - **Validates: Requirements 9.1, 9.6, 9.7**

  - [x] 5.5 Add `cascadeSoftDelete(Household $household): void` to `HouseholdService`
    - Wrap in `DB::transaction()`; soft-delete all `household->contacts()` and `household->careRecipients()`
    - _Requirements: 15.2, 15.3_

  - [ ]* 5.6 Write property test for `cascadeSoftDelete` (Property 12)
    - **Property 12: Household cascade soft-delete**
    - Generate Household with random counts of contacts and care recipients; soft-delete the Household; assert all contacts and recipients have non-null `deleted_at`
    - **Validates: Requirements 15.2, 15.3**

- [x] 6. Checkpoint — Ensure all service tests pass
  - Run `php artisan test --filter=LeadService` and `--filter=HouseholdService`; all unit and property tests must be green before proceeding.

- [x] 7. Implement `LeadController` and sub-controllers
  - [x] 7.1 Create `App\Http\Controllers\LeadController`
    - `index()`: branch on `wantsJson()` — return Yajra DataTables response with columns: full_name (linked), phone, channel, area, status_badge, owner name, created_at, action buttons; apply `when()` filters for status, channel, area, owner_id, and `created_at` date range; otherwise return `view('leads.index')`
    - `create()` / `store()`: load Dhaka zones, channels, and coordinators for the form; in `store()`, normalize phone via `LeadService`, run `detectDuplicates()` before saving, set `created_by = auth()->id()`, dispatch Spatie ActivityLog
    - `show($id)`: load lead with `communicationLogs`, `consentRecords`, `statusHistories`; return `view('leads.show')` for a tabbed detail view
    - `edit()` / `update()`: re-check duplicates on phone/email change; call `validateTransition()` if status is changing; call `recordStatusHistory()` on status change; dispatch Spatie ActivityLog
    - `destroy()`: soft-delete; dispatch Spatie ActivityLog; return back
    - `updateStatus($id)`: toggle `is_active` exactly like `UserController::updateStatus()`
    - `convert($id)`: validate lead status is Qualified; delegate to `HouseholdService::convertFromLead()`; redirect to household show
    - _Requirements: 1.1–1.15, 2.1–2.7, 3.1–3.6, 13.1, 14.1–14.3_

  - [x] 7.2 Create `App\Http\Controllers\Lead\CommunicationLogController`
    - `store($leadId)`: validate all fields per Requirement 5; reject Outbound if `lead->consent_status != Granted` with error referencing BR-020; set `created_by`; dispatch Spatie ActivityLog
    - `destroy($leadId, $id)`: soft-delete the log entry; dispatch Spatie ActivityLog
    - _Requirements: 5.1–5.9, 6.6_

  - [ ]* 7.3 Write property test for Outbound consent enforcement (Property 3)
    - **Property 3: Outbound communication requires consent**
    - Generate Leads with random `consent_status`; attempt Outbound log; assert accepted only when `consent_status = Granted`
    - **Validates: Requirements 5.7, 6.6**

  - [x] 7.4 Create `App\Http\Controllers\Lead\ConsentController`
    - `store($leadId)`: append a new ConsentRecord row; roll up `lead->consent_status` (Granted if any record is Granted, else Withdrawn); dispatch Spatie ActivityLog
    - `update($leadId, $id)`: set `status = Withdrawn`, record `withdrawn_at = now()`; re-run consent roll-up; dispatch Spatie ActivityLog
    - _Requirements: 6.1–6.6, 13.3_

  - [ ]* 7.5 Write property test for consent roll-up (Property 4)
    - **Property 4: Consent status roll-up invariant**
    - Generate random sequences of ConsentRecord mutations; assert `lead->consent_status` reflects roll-up rule after each mutation
    - **Validates: Requirements 6.2, 6.3, 6.4, 6.5**

- [x] 8. Implement `HouseholdController` and sub-controllers
  - [x] 8.1 Create `App\Http\Controllers\HouseholdController`
    - `index()`: branch on `wantsJson()` — DataTable response with columns: display_name (linked), area, status_badge, coordinator name, contact count, created_at, action buttons; never include `address_line`; apply filters for status, area, assigned_coordinator_id, founding_cohort; otherwise return `view('households.index')`
    - `create()` / `store()` / `edit()` / `update()`: validate per Requirement 8; dispatch Spatie ActivityLog on all mutations; set `created_by`
    - `destroy()`: delegate to `HouseholdService::cascadeSoftDelete()`; dispatch Spatie ActivityLog
    - `updateStatus($id)`: toggle `is_active` same pattern as `UserController::updateStatus()`
    - _Requirements: 8.1–8.10, 13.2, 14.4–14.7, 15.2, 15.3_

  - [ ]* 8.2 Write property test for DataTable address_line exclusion (Property 11)
    - **Property 11: DataTable response never exposes address_line**
    - Generate authenticated requests to Household DataTable endpoint as any user; assert `address_line` key is absent from all response rows
    - **Validates: Requirements 8.3, 14.7, 12.5**

  - [x] 8.3 Create `App\Http\Controllers\Household\HouseholdContactController`
    - `store($householdId)` / `update($householdId, $id)`: validate per Requirement 9; dispatch Spatie ActivityLog
    - `destroy($householdId, $id)`: call `HouseholdService::guardLastPrimaryContact()` before soft-deleting; dispatch Spatie ActivityLog
    - _Requirements: 9.1–9.7, 13.2_

  - [x] 8.4 Create `App\Http\Controllers\Household\CareRecipientController`
    - `store($householdId)` / `update($householdId, $id)` / `destroy($householdId, $id)`: validate per Requirement 10; enforce Property 7 (dob OR age_band required); enforce Property 8 (Child always sets `is_sensitive = true`); filter sensitive records for users without `sensitive_data_view` permission; dispatch Spatie ActivityLog
    - _Requirements: 10.1–10.8, 12.4, 13.2_

  - [ ]* 8.5 Write property test for CareRecipient age identification (Property 7)
    - **Property 7: CareRecipient age identification required**
    - Generate payloads with both-null, one-set, both-set combinations; assert null-both is rejected, otherwise accepted
    - **Validates: Requirements 10.3**

  - [ ]* 8.6 Write property test for Child sensitivity enforcement (Property 8)
    - **Property 8: Child CareRecipient is always marked Sensitive**
    - Generate CareRecipient with `recipient_type = Child` and any `is_sensitive` value; assert stored record always has `is_sensitive = true`
    - **Validates: Requirements 10.8, 12.4**

- [x] 9. Add non-clinical keyword validation
  - [x] 9.1 Add a custom Validation Rule class `App\Rules\NonClinical` (or a reusable validator macro) that checks a string against `config('crm.non_clinical_keywords')` and fails with a descriptive message naming the offending field
    - Apply this rule to: `Lead.notes`, `Household.access_notes`, `CareRecipient.support_notes`, `CareRecipient.routines` in their respective `store`/`update` validation blocks
    - _Requirements: 11.1–11.4_

  - [ ]* 9.2 Write property test for non-clinical keyword blocking (Property 9)
    - **Property 9: Non-clinical keywords blocked in all notes fields**
    - Generate notes strings with/without injected keywords; assert keyword-containing inputs rejected, clean inputs accepted
    - **Validates: Requirements 11.1, 11.2, 11.3, 11.4**

- [x] 10. Checkpoint — Ensure all controller and validation tests pass
  - Run `php artisan test --filter=Lead` and `--filter=Household`; all tests must be green before proceeding.

- [x] 11. Register all CRM routes in `routes/web.php`
  - Inside the existing `auth` + `role_permission_check` middleware group, add a `crm` prefix group:
    - `Route::resource('leads', LeadController::class)`
    - `POST leads/{lead}/update-status` → `LeadController@updateStatus` (name: `leads.update-status`)
    - `POST leads/{lead}/convert` → `LeadController@convert` (name: `leads.convert`)
    - `POST leads/{lead}/communication-logs` → `CommunicationLogController@store` (name: `leads.communication-logs.store`)
    - `DELETE leads/{lead}/communication-logs/{log}` → `CommunicationLogController@destroy` (name: `leads.communication-logs.destroy`)
    - `POST leads/{lead}/consents` → `ConsentController@store` (name: `leads.consents.store`)
    - `PUT leads/{lead}/consents/{consent}` → `ConsentController@update` (name: `leads.consents.update`)
    - `Route::resource('households', HouseholdController::class)`
    - `POST households/{household}/update-status` → `HouseholdController@updateStatus` (name: `households.update-status`)
    - `POST households/{household}/contacts` → `HouseholdContactController@store` (name: `households.contacts.store`)
    - `PUT households/{household}/contacts/{contact}` → `HouseholdContactController@update` (name: `households.contacts.update`)
    - `DELETE households/{household}/contacts/{contact}` → `HouseholdContactController@destroy` (name: `households.contacts.destroy`)
    - `POST households/{household}/care-recipients` → `CareRecipientController@store` (name: `households.care-recipients.store`)
    - `PUT households/{household}/care-recipients/{recipient}` → `CareRecipientController@update` (name: `households.care-recipients.update`)
    - `DELETE households/{household}/care-recipients/{recipient}` → `CareRecipientController@destroy` (name: `households.care-recipients.destroy`)
  - _Requirements: 12.1, 12.2_

- [x] 12. Build Lead views
  - [x] 12.1 Create `resources/views/leads/index.blade.php`
    - Extend `layouts.base`; include breadcrumb; "Add New Lead" button; Filter modal with inputs for status, channel, area, owner_id, date range; `<x-datatable>` component wired to `route('leads.index')`; JS filter form submission pattern identical to `users/index.blade.php`
    - _Requirements: 14.1–14.3_

  - [x] 12.2 Create `resources/views/leads/create.blade.php` and `resources/views/leads/edit.blade.php`
    - Extend `layouts.base`; form with all Lead fields using `<x-form-group>`, `<x-form-label>`, `<x-input-error>` components; channel and area as `<select>` populated from `config('crm.*')`; duplicate warning section displayed when duplicates are returned (link/merge/override options)
    - _Requirements: 1.1–1.15, 2.4–2.6_

  - [x] 12.3 Create `resources/views/leads/show.blade.php`
    - Extend `layouts.base`; tabbed layout with three tabs: Details (all Lead fields), Communication Log (table of logs + inline add-log form), Consent (table of consent records + add-consent form); Outbound log tab shows inline consent warning when `consent_status != Granted`; "Convert to Household" button visible when `status = Qualified`
    - _Requirements: 5.1–5.9, 6.1–6.6, 7.1_

- [x] 13. Build Household views
  - [x] 13.1 Create `resources/views/households/index.blade.php`
    - Extend `layouts.base`; "Add New Household" button; Filter modal for status, area, coordinator, founding_cohort; `<x-datatable>` wired to `route('households.index')`; same JS filter pattern as `users/index.blade.php`
    - _Requirements: 14.4–14.7_

  - [x] 13.2 Create `resources/views/households/create.blade.php` and `resources/views/households/edit.blade.php`
    - Form with all Household fields; area as `<select>` from `config('crm.dhaka_zones')`; status select from enum values; coordinator select from active Users
    - _Requirements: 8.1–8.10_

  - [x] 13.3 Create `resources/views/households/show.blade.php`
    - Extend `layouts.base`; tabbed layout: Details tab (Household fields), Contacts tab (table of HouseholdContacts + inline add/edit form; Primary contact deletion blocked by guard), Care Recipients tab (table of CareRecipients + inline add/edit form; sensitive records hidden for users without `sensitive_data_view` permission)
    - _Requirements: 9.1–9.7, 10.1–10.8, 12.4_

- [x] 14. Add CRM menu items via seeder
  - Create `database/seeders/CrmMenuSeeder.php` using the same `menu()` helper pattern as `SuperAdminSeeder`
  - Add a parent menu "CRM" with icon and order ~20, and two child items: "Leads" (`leads.index`) and "Households" (`households.index`)
  - Register `CrmMenuSeeder` in `DatabaseSeeder::run()`
  - _Requirements: 12.1, 12.2_

- [x] 15. Final checkpoint — Ensure all tests pass
  - Run `php artisan test`; run migrations fresh on a test database; seed with `CrmMenuSeeder`; confirm all CRM routes resolve and all unit + property tests are green. Ask the user if any questions arise.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- All mutations must be wrapped in `try/catch`; multi-model operations use `DB::transaction()`
- `address_line` must never appear in any DataTable JSON response (enforced at DataTable column definition, not just model `$hidden`)
- Spatie ActivityLog is dispatched inside each transaction so it rolls back on failure
- Property tests use Pest with a minimum of 100 iterations per property
- Each property test must be tagged: `// Feature: crm-lead-management, Property N: <property text>`
