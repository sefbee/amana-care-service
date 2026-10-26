# Requirements Document

## Introduction

Module 1 — CRM & Lead Management adds an end-to-end intake pipeline to the Amanah Operations Platform admin panel. It covers the full lifecycle from first contact with a prospective client through qualification, consent tracking, duplicate control, and conversion into a managed Household record. The module is implemented as a new feature group inside the existing Laravel 12 / Blade + Livewire 3 admin panel, following established conventions for controllers, models, migrations, Yajra DataTables, Spatie ActivityLog, and role-based access control.

The two core aggregates are **Lead** (pre-conversion prospect) and **Household** (converted, active client family). A Household owns one or more **HouseholdContacts** and zero or more **CareRecipients**.

---

## Glossary

- **Lead**: A prospective client who has made initial contact with Amanah but has not yet been converted to a Household.
- **Household**: A converted client family record that groups contacts and care recipients under one operational unit.
- **HouseholdContact**: A named person associated with a Household in a defined role (Primary, Billing, Emergency).
- **CareRecipient**: A person within a Household who receives care services; may be a Child, Adult, or Senior.
- **Channel**: The communication medium through which a Lead first made contact (WhatsApp, Email, Phone, Walk-in, Referral, Web Form, Other).
- **ConsentRecord**: A timestamped record of a Lead's or Household Contact's consent status per channel.
- **CommunicationLog**: A per-Lead chronological record of interactions, capturing direction, channel, summary, and next step.
- **Coordinator**: A staff User assigned as owner of a Lead or Household.
- **Operations Manager (OM)**: A privileged staff role with authority to perform merge operations and override duplicate warnings.
- **Normalized Phone**: A phone number stripped of country code prefix, spaces, and hyphens, reduced to its local digit string for comparison purposes.
- **Dhaka Zone**: A predefined picklist of Dhaka metropolitan sub-areas used for location classification.
- **Lead_Manager**: The system component responsible for Lead lifecycle operations.
- **Household_Manager**: The system component responsible for Household lifecycle operations.
- **Duplicate_Detector**: The system component responsible for detecting and resolving duplicate leads.
- **Consent_Manager**: The system component responsible for recording and enforcing consent rules.
- **Communication_Logger**: The system component responsible for recording per-Lead communication interactions.
- **Phone_Normalizer**: The system component responsible for normalizing phone numbers before storage and comparison.

---

## Requirements

---

### Requirement 1: Lead Creation

**User Story:** As a Coordinator, I want to capture a new lead with all intake details, so that the pipeline has a complete record from first contact.

#### Acceptance Criteria

1. THE Lead_Manager SHALL accept `full_name` between 2 and 100 characters.
2. THE Lead_Manager SHALL accept `phone` that matches either a valid Bangladesh mobile number (formatted as `01[3-9][0-9]{8}`) or an international E.164 format (`\+[1-9][0-9]{6,14}`).
3. THE Lead_Manager SHALL store `phone` in its normalized form (digits only, local format) alongside the original input.
4. THE Lead_Manager SHALL accept an optional `email` that conforms to RFC 5321 format with a maximum length of 254 characters.
5. THE Lead_Manager SHALL require `channel` to be one of: WhatsApp, Email, Phone, Walk-in, Referral, Web Form, Other.
6. THE Lead_Manager SHALL accept an optional free-text `service_interest` field with a maximum of 500 characters.
7. THE Lead_Manager SHALL accept an optional `area` value from the Dhaka Zone picklist.
8. THE Lead_Manager SHALL accept an optional `live_in_pref` value of: live-in, live-out, or either.
9. THE Lead_Manager SHALL accept an optional `preferred_start_date` that is a valid calendar date.
10. THE Lead_Manager SHALL accept an optional `budget_expectation` as a positive decimal value.
11. THE Lead_Manager SHALL accept an optional `notes` field with a maximum of 2000 characters.
12. THE Lead_Manager SHALL set the initial `status` to New upon creation.
13. THE Lead_Manager SHALL set the initial `consent_status` to Unknown upon creation unless explicitly provided.
14. THE Lead_Manager SHALL accept an optional `owner_id` referencing a valid active User.
15. WHEN a Lead is saved, THE Lead_Manager SHALL record the creating User's id in `created_by`.

---

### Requirement 2: Duplicate Detection

**User Story:** As a Coordinator, I want to be warned when a new lead's phone or email already exists, so that I avoid creating redundant records.

#### Acceptance Criteria

1. WHEN a Lead creation or update is submitted, THE Duplicate_Detector SHALL compare the normalized phone against all non-deleted Lead records and all Household records.
2. WHEN a Lead creation or update is submitted, THE Duplicate_Detector SHALL compare the email (case-insensitive) against all non-deleted Lead records and all Household records.
3. WHEN a duplicate is detected on either phone or email, THE Duplicate_Detector SHALL return the conflicting record(s) with id, full_name, status, and created_at before persisting the new record.
4. WHEN a duplicate is detected, THE Lead_Manager SHALL require the submitting user to select one of: link to existing record, merge with existing record (OM only), or override with a stated reason before proceeding.
5. WHEN a user selects override, THE Lead_Manager SHALL require a non-empty `override_reason` of at most 500 characters, and SHALL log the override reason via Spatie ActivityLog.
6. WHEN a user selects merge, THE Lead_Manager SHALL enforce that the authenticated User holds the Operations Manager role; IF the authenticated User does not hold the Operations Manager role, THEN THE Lead_Manager SHALL reject the merge request with an HTTP 403 response.
7. THE Phone_Normalizer SHALL strip the `+88` country prefix, spaces, hyphens, and parentheses before comparison.

---

### Requirement 3: Lead Status Lifecycle

**User Story:** As a Coordinator, I want to progress a lead through defined statuses, so that the pipeline reflects the current stage of each prospect.

#### Acceptance Criteria

1. THE Lead_Manager SHALL enforce the following permitted forward transitions: New → Contacted, Contacted → Qualified, Qualified → Converted, Qualified → Waitlisted, Qualified → Lost, Qualified → Not Eligible, Contacted → Lost, Contacted → Not Eligible, New → Lost, New → Not Eligible.
2. WHEN a status transition is requested, THE Lead_Manager SHALL reject any transition not listed in criterion 1 and return a validation error identifying the invalid transition.
3. WHEN `status` is set to Lost or Not Eligible, THE Lead_Manager SHALL require `lost_reason` to be selected from the predefined picklist.
4. WHEN `status` is set to Waitlisted, THE Lead_Manager SHALL require a numeric `waitlist_priority` greater than 0.
5. WHEN `status` is set to Converted, THE Lead_Manager SHALL create a corresponding Household record in the same database transaction; IF the Household creation fails, THEN THE Lead_Manager SHALL roll back the status change and return an error.
6. WHEN a status transition is persisted, THE Lead_Manager SHALL log the previous status, new status, acting User, and timestamp via Spatie ActivityLog.

---

### Requirement 4: Lost / Not Eligible Reason Picklist

**User Story:** As an Operations Manager, I want standardized closure reasons, so that reporting on lost leads is consistent.

#### Acceptance Criteria

1. THE Lead_Manager SHALL accept `lost_reason` only from the following values: Budget Mismatch, Area Not Served, Service Not Available, Unresponsive, Chose Competitor, Duplicate, Not Eligible — Age, Not Eligible — Condition, Other.
2. WHERE a `lost_reason` of Other is selected, THE Lead_Manager SHALL require a non-empty `lost_reason_notes` field of at most 500 characters.

---

### Requirement 5: Communication Log

**User Story:** As a Coordinator, I want to record every interaction with a lead, so that there is a full contact history visible to the whole team.

#### Acceptance Criteria

1. THE Communication_Logger SHALL require `contact_date` as a valid datetime not in the future.
2. THE Communication_Logger SHALL require `direction` to be one of: Inbound, Outbound.
3. THE Communication_Logger SHALL require `channel` to be one of: WhatsApp, Email, Phone, In-Person, Other.
4. THE Communication_Logger SHALL require `summary` as a non-empty string of at most 1000 characters.
5. THE Communication_Logger SHALL accept an optional `next_step` of at most 500 characters.
6. THE Communication_Logger SHALL associate each log entry with exactly one Lead via `lead_id`.
7. WHEN an Outbound entry is submitted for a Lead whose `consent_status` is not Granted, THE Communication_Logger SHALL reject the entry and return a validation error referencing the consent requirement (BR-020).
8. THE Communication_Logger SHALL store the `created_by` User id on each entry.
9. THE Communication_Logger SHALL allow soft-deletion of log entries by users with the appropriate permission.

---

### Requirement 6: Consent Management

**User Story:** As a Coordinator, I want to record and update consent per channel, so that outbound communications comply with BR-020.

#### Acceptance Criteria

1. THE Consent_Manager SHALL store one ConsentRecord per Lead per channel, with `status` (Granted, Withdrawn, Unknown), `source` (free text, max 200 chars), and `consented_at` timestamp.
2. WHEN consent is Granted for any channel, THE Consent_Manager SHALL set the parent Lead's `consent_status` to Granted.
3. WHEN all per-channel consents are Withdrawn or Unknown, THE Consent_Manager SHALL set the parent Lead's `consent_status` to Withdrawn.
4. WHEN a ConsentRecord `status` is updated to Withdrawn, THE Consent_Manager SHALL record a `withdrawn_at` timestamp.
5. THE Consent_Manager SHALL append, not overwrite, consent history; each status change SHALL create a new ConsentRecord row with its own timestamp.
6. WHEN a user attempts to add an Outbound CommunicationLog entry and the Lead `consent_status` is not Granted, THE Consent_Manager SHALL surface an inline warning with a link to the consent management screen.

---

### Requirement 7: Lead-to-Household Conversion

**User Story:** As a Coordinator, I want to convert a qualified lead into a Household, so that service delivery can begin without re-entering data.

#### Acceptance Criteria

1. WHEN `status` is set to Converted, THE Lead_Manager SHALL require the Lead's `status` to be Qualified before accepting the conversion request.
2. WHEN a conversion is triggered, THE Household_Manager SHALL pre-populate the new Household with `display_name` from `Lead.full_name`, `area` from `Lead.area`, `assigned_coordinator_id` from `Lead.owner_id`, and `status` set to Onboarding.
3. WHEN a conversion is triggered, THE Household_Manager SHALL create a Primary HouseholdContact using `Lead.full_name`, `Lead.phone`, and `Lead.email`.
4. WHEN a conversion transaction completes successfully, THE Lead_Manager SHALL store `household_id` on the Lead record as a non-nullable foreign key reference.
5. WHEN a conversion transaction fails at any step, THE Lead_Manager SHALL roll back all changes and leave the Lead in its previous state.
6. WHEN a conversion completes, THE Lead_Manager SHALL log the event including `lead_id`, `household_id`, acting User, and timestamp via Spatie ActivityLog.

---

### Requirement 8: Household Management

**User Story:** As a Coordinator, I want to manage household records with contacts and care recipients, so that each family's operational profile is complete and current.

#### Acceptance Criteria

1. THE Household_Manager SHALL require `display_name` between 2 and 100 characters.
2. THE Household_Manager SHALL require `area` from the Dhaka Zone picklist.
3. THE Household_Manager SHALL accept an optional `address_line` of at most 300 characters; this field SHALL be marked sensitive and excluded from DataTable column output.
4. THE Household_Manager SHALL accept an optional `access_notes` of at most 500 characters.
5. THE Household_Manager SHALL require `primary_language` to be one of: Bangla, English, Other.
6. THE Household_Manager SHALL require `status` to be one of: Onboarding, Active, On Hold, Closed.
7. THE Household_Manager SHALL accept an optional boolean `founding_cohort` flag, defaulting to false.
8. THE Household_Manager SHALL require `assigned_coordinator_id` referencing a valid active User.
9. WHEN `status` is Closed, THE Household_Manager SHALL reject any new Service Request associated with the Household and return a validation error.
10. WHEN a Household `status` changes, THE Household_Manager SHALL log the previous status, new status, acting User, and timestamp via Spatie ActivityLog.

---

### Requirement 9: Household Contacts

**User Story:** As a Coordinator, I want to record multiple contacts per household with defined roles, so that billing and emergency contacts are always accessible.

#### Acceptance Criteria

1. THE Household_Manager SHALL require at least one HouseholdContact with `role` of Primary for any persisted Household.
2. THE Household_Manager SHALL require `name` as a non-empty string of at most 150 characters per contact.
3. THE Household_Manager SHALL require `role` to be one of: Primary, Billing, Emergency.
4. THE Household_Manager SHALL require `phone` in the same format as Lead phone validation (Bangladesh mobile or international E.164).
5. THE Household_Manager SHALL accept an optional `email` per contact conforming to RFC 5321 format with a maximum length of 254 characters.
6. WHEN a contact with `role` of Primary is deleted and no other Primary contact exists, THE Household_Manager SHALL reject the deletion and return a validation error.
7. THE Household_Manager SHALL allow a Household to have at most one Primary contact.

---

### Requirement 10: Care Recipients

**User Story:** As a Coordinator, I want to record care recipients within a household, so that service planning is based on accurate recipient profiles.

#### Acceptance Criteria

1. THE Household_Manager SHALL require `first_name` as a non-empty string of at most 100 characters per CareRecipient.
2. THE Household_Manager SHALL require `recipient_type` to be one of: Child, Adult, Senior.
3. THE Household_Manager SHALL require either `date_of_birth` (a valid past calendar date) or `age_band` (a non-empty string from the predefined picklist: 0–5, 6–12, 13–17, 18–59, 60–74, 75+); IF neither is provided, THEN THE Household_Manager SHALL return a validation error.
4. THE Household_Manager SHALL accept an optional `relationship` string of at most 100 characters describing the care recipient's relation to the Primary contact.
5. THE Household_Manager SHALL accept an optional `support_notes` field of at most 1000 characters; this field SHALL be marked non-clinical and SHALL not accept text matching diagnosis keywords (diagnoses, drug names, prescriptions).
6. THE Household_Manager SHALL accept an optional `routines` field of at most 1000 characters.
7. THE Household_Manager SHALL accept an optional `dietary_restrictions` field of at most 500 characters.
8. WHEN `recipient_type` is Child, THE Household_Manager SHALL mark the CareRecipient record as Sensitive and restrict its visibility to users with the sensitive_data_view permission.

---

### Requirement 11: Non-Clinical Boundary Enforcement

**User Story:** As a system administrator, I want notes fields to block clinical terminology, so that Amanah remains outside the scope of medical record keeping.

#### Acceptance Criteria

1. THE Lead_Manager SHALL validate that the `notes` field does not contain any string matching the non-clinical keyword blocklist before persisting the record.
2. THE Household_Manager SHALL validate that `support_notes`, `routines`, `access_notes`, and `CareRecipient.support_notes` do not contain any string matching the non-clinical keyword blocklist before persisting the record.
3. WHEN a non-clinical keyword is detected in any notes field, THE Lead_Manager or Household_Manager (as applicable) SHALL reject the input and return a validation error identifying the offending field and the nature of the violation.
4. THE Lead_Manager and Household_Manager SHALL maintain a configurable non-clinical keyword blocklist stored in application configuration (not hard-coded in model classes).

---

### Requirement 12: Role-Based Access Control

**User Story:** As an Operations Manager, I want access to be gated by role and permission, so that sensitive records and privileged operations are protected.

#### Acceptance Criteria

1. THE Lead_Manager SHALL restrict all Lead routes behind the `auth` and `role_permission_check` middleware.
2. THE Household_Manager SHALL restrict all Household routes behind the `auth` and `role_permission_check` middleware.
3. WHEN an authenticated User without the Operations Manager role attempts a merge operation, THE Lead_Manager SHALL return an HTTP 403 response.
4. WHEN an authenticated User without the `sensitive_data_view` permission requests a CareRecipient record marked Sensitive, THE Household_Manager SHALL exclude the record from the response.
5. THE Lead_Manager SHALL allow the `address_line` field of Household to be returned only to Users with the `sensitive_data_view` permission.

---

### Requirement 13: Audit Logging

**User Story:** As an Operations Manager, I want a full audit trail for all CRM changes, so that I can review the history of any lead or household.

#### Acceptance Criteria

1. WHEN a Lead record is created, updated, or soft-deleted, THE Lead_Manager SHALL dispatch a Spatie ActivityLog event with `causer` set to the authenticated User and `subject` set to the Lead model.
2. WHEN a Household record or any of its HouseholdContacts or CareRecipients is created, updated, or soft-deleted, THE Household_Manager SHALL dispatch a Spatie ActivityLog event with `causer` set to the authenticated User and `subject` set to the affected model.
3. WHEN a ConsentRecord is created or updated, THE Consent_Manager SHALL dispatch a Spatie ActivityLog event capturing the previous and new consent status.
4. WHEN a duplicate override is accepted, THE Duplicate_Detector SHALL dispatch a Spatie ActivityLog event capturing the `override_reason` and the conflicting record reference.
5. THE Lead_Manager SHALL record status transition history (previous status, new status, acting User, timestamp) in a dedicated `lead_status_histories` table, separate from the general activity log.

---

### Requirement 14: DataTable Listing and Filtering

**User Story:** As a Coordinator, I want filterable DataTable views for leads and households, so that I can quickly locate records without full-text searching.

#### Acceptance Criteria

1. THE Lead_Manager SHALL return a Yajra DataTables-compatible JSON response WHEN the incoming HTTP request includes the `wantsJson()` header.
2. THE Lead_Manager DataTable SHALL support server-side filtering by: `status`, `channel`, `area`, `owner_id`, and date range on `created_at`.
3. THE Lead_Manager DataTable SHALL display columns: full_name (linked to detail view), phone, channel, area, status badge, owner name, created_at, and action buttons (edit, delete, view).
4. THE Household_Manager SHALL return a Yajra DataTables-compatible JSON response WHEN the incoming HTTP request includes the `wantsJson()` header.
5. THE Household_Manager DataTable SHALL support server-side filtering by: `status`, `area`, `assigned_coordinator_id`, and `founding_cohort`.
6. THE Household_Manager DataTable SHALL display columns: display_name (linked to detail view), area, status badge, coordinator name, contact count, created_at, and action buttons.
7. THE Household_Manager DataTable SHALL NOT include `address_line` in any column output regardless of user permission.

---

### Requirement 15: Soft Delete and Restore

**User Story:** As an Operations Manager, I want deleted records to be soft-deleted and restorable, so that accidental deletions can be recovered.

#### Acceptance Criteria

1. THE Lead_Manager SHALL use Laravel SoftDeletes on the `leads` table; deleted records SHALL NOT appear in default queries.
2. THE Household_Manager SHALL use Laravel SoftDeletes on the `households`, `household_contacts`, and `care_recipients` tables.
3. WHEN a Household is soft-deleted, THE Household_Manager SHALL also soft-delete all associated HouseholdContacts and CareRecipients in the same database transaction.
4. WHEN a Lead or Household record is restored, THE Lead_Manager or Household_Manager (as applicable) SHALL dispatch a Spatie ActivityLog event recording the restore action and the acting User.
5. THE Lead_Manager SHALL expose a restore action restricted to users with the Operations Manager role.

---

### Requirement 16: Phone Number Normalization (Parser/Serializer)

**User Story:** As a developer, I want phone numbers normalized consistently, so that duplicate detection is accurate regardless of input format.

#### Acceptance Criteria

1. WHEN a phone input is provided, THE Phone_Normalizer SHALL strip the following from the raw input before storage: `+88` prefix, `0088` prefix, spaces, hyphens, parentheses, and plus signs not part of international format.
2. THE Phone_Normalizer SHALL accept `01XXXXXXXXX` (11 digits starting with 01) as a valid Bangladesh mobile number.
3. THE Phone_Normalizer SHALL accept `+[country_code][number]` of total length 8 to 15 digits (excluding the leading `+`) as a valid international number.
4. IF a phone input does not match either the Bangladesh mobile pattern or the international E.164 pattern after normalization, THEN THE Phone_Normalizer SHALL return a descriptive validation error.
5. THE Phone_Normalizer SHALL format normalized Bangladesh numbers for storage as `01XXXXXXXXX` (no spaces, no prefix).
6. FOR ALL valid phone inputs that produce the same logical number via different formatting variants (e.g., `+8801712345678`, `01712345678`, `017-1234-5678`), THE Phone_Normalizer SHALL produce the same stored normalized value (round-trip equivalence property).

---

### Requirement 17: Dhaka Zone Picklist

**User Story:** As a developer, I want area values drawn from a central picklist, so that filtering and reporting are consistent across leads and households.

#### Acceptance Criteria

1. THE Lead_Manager and Household_Manager SHALL source the `area` field values exclusively from a centrally defined Dhaka Zone picklist.
2. THE Lead_Manager SHALL return a validation error WHEN `area` is provided but does not match a value in the Dhaka Zone picklist.
3. THE Household_Manager SHALL return a validation error WHEN `area` does not match a value in the Dhaka Zone picklist.
4. THE Lead_Manager and Household_Manager SHALL expose the Dhaka Zone picklist values via a configuration key (`crm.dhaka_zones`) rather than embedding them in migration files or model classes.
