You are a senior Laravel + Inertia/Vue developer working on **UHMS — Ultimate Hospital Management System**.

We need to update the **Patient List / Patient Search page**.

Focus only on patient listing, search, filters, patient status, and deceased marking. Do not refactor unrelated modules.

---

# 1. Objective

Update the Patient List page so hospital staff can search patients using practical real-life identifiers and filter patients by insurance and last visit date.

Also add a safe way to mark a patient as deceased.

---

# 2. Search Requirements

The patient list search must support searching by:

```text
Patient name
Patient phone number
Patient Ghana Card number
Patient insurance membership card number
Emergency contact name
Emergency contact phone number
```

The search input should be a single global search box.

Example:

```text
Search by name, phone, Ghana Card, insurance card, or emergency contact ...
```

---

# 3. Fields / Relationships to Inspect

Inspect existing models and tables for:

```text
patients
patient_insurances
emergency_contacts
visits
insurance_providers
```

Search should work whether next of kin and insurance are stored directly on `patients` or in related tables.

Use existing relationships where available.

Recommended relationships:

```php
Patient::insurances()
Patient::emergencyContacts()
Patient::visits()
```

If relationships are missing, add them properly.

---

# 4. Search Logic

The search query should check:

```text
patients.name
patients.phone
patients.ghana_card_number
patient_insurances.membership_number
emergency_contacts.name
emergency_contacts.phone
```

Adapt column names to the actual database schema.

Example backend logic:

```php
$query->when($search, function ($query) use ($search) {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('phone', 'like', "%{$search}%")
          ->orWhere('ghana_card_number', 'like', "%{$search}%")
          ->orWhereHas('insurances', function ($insuranceQuery) use ($search) {
              $insuranceQuery->where('membership_number', 'like', "%{$search}%");
          })
          ->orWhereHas('emergencyContacts', function ($emergencyContactsQuery) use ($search) {
              $emergencyContactsQuery->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
          });
    });
});
```

Do not use raw unsafe SQL.

---

# 5. Remove Existing Filters

Remove these filters from the Patient List page:

```text
Blood group
Gender
```

This means:

* remove them from the frontend filter UI
* stop sending them in query parameters
* remove backend filtering logic if it only exists for this patient list page

Do not delete patient fields from the database.

Only remove the filters from the list page.

---

# 6. Add Insurance Filter

Add filter by insurance.

The user should be able to filter patients by:

```text
Insurance provider
```

Optional if already supported:

```text
Insurance type
Insurance status: active / expired
```

Minimum required filter:

```text
Insurance Provider
```

The filter should load from existing insurance providers.

Example:

```text
All Insurances
NHIS
Cash and Carry
Private Insurance A
Corporate Insurance B
```

If Cash and Carry is not stored as insurance provider, decide whether to include it as a special filter option.

---

# 7. Add Last Visit Date Range Filter

Add date range filter based on the patient’s last visit date.

Fields:

```text
Last Visit From
Last Visit To
```

The filter should return patients whose most recent visit falls within the selected range.

Example:

```text
last_visit_date >= from
last_visit_date <= to
```

If using Eloquent, calculate last visit using:

```php
withMax('visits', 'visit_date')
```

or equivalent.

Do not filter by any visit in the range if the requirement is specifically “last visit date”. It must use the latest visit per patient.

---

# 8. Patient List Columns

Status should show:

```text
Active
Deceased
Inactive if already supported
```

Use badges for status.

---

# 9. Mark Patient as Deceased

Add a safe way to mark a patient as deceased.

This should be an action button on the patient detail page:

```text
Mark as Deceased
```

When clicked, open a confirmation modal.

The modal should ask for:

```text
Date of death
Cause of death optional
Notes optional
```

Require confirmation before saving.

---

# 10. Database Fields for Deceased Status

If not already present, add fields to `patients` table:

```text
is_deceased boolean default false
deceased_at nullable datetime/date
cause_of_death nullable string/text
deceased_notes nullable text
marked_deceased_by nullable foreign key to users
```

If the project already has a patient status field, use it consistently.

Recommended:

```text
status = active/deceased/inactive
```

or:

```text
is_deceased = true/false
```

Choose the approach that best fits the existing codebase.

---

# 11. Deceased Business Rules

When a patient is marked deceased:

* patient should remain searchable
* patient should show clear `Deceased` badge
* patient should not be selected for new OPD visits unless authorized override exists
* patient should not be selected for new admission unless authorized override exists
* patient history must remain accessible
* old invoices, visits, investigations, prescriptions, and records must remain unchanged
* do not delete the patient record

If a new visit is attempted for a deceased patient, show clear warning:

```text
This patient is marked as deceased and cannot start a new visit.
```

Optional override permission:

```text
patients.deceased.override
```

---

# 12. Permissions

Add or verify permission:

```text
patients.mark_deceased
```

Only authorized users should see or use the Mark as Deceased action.

Suggested allowed roles:

```text
Admin
Super Admin
Records Supervisor
Doctor if permitted
```

Do not allow normal users to mark patients deceased unless assigned permission.

---

# 13. Backend Requirements

Create or update:

```text
PatientController@index
PatientController@markDeceased
PatientService
PatientFilterService if available
MarkPatientDeceasedRequest
```

Recommended route:

```php
Route::patch('/patients/{patient}/mark-deceased', [PatientController::class, 'markDeceased'])
    ->name('patients.mark-deceased')
    ->middleware('can:patients.mark_deceased');
```

Use existing route prefixes/names if different.

---

# 14. Frontend Requirements

On Patient List page:

* update search placeholder
* remove gender filter
* remove blood group filter
* add insurance filter
* add last visit date range filters
* preserve filters in URL/query string
* reset filters button should work
* pagination should preserve filters
* add deceased badge
* add Mark as Deceased action if user has permission
* show confirmation modal
* show validation errors in modal
* modal should close only after successful save

Use SPA behavior. No full page reloads.

---

# 15. Performance Requirements

Avoid N+1 queries.

Eager-load:

```text
insurances.provider
nextOfKins
latestVisit or visits max date
```

Use efficient last visit calculation.

Add indexes if needed:

```text
patients.name
patients.phone
patients.ghana_card_number
patient_insurances.membership_number
next_of_kins.name
next_of_kins.phone
visits.patient_id
visits.visit_date
```

Do not load all visits for every patient just to find last visit date.

---

# 16. Tests Required

Add or update tests for:

1. Search by patient name works.
2. Search by patient phone works.
3. Search by Ghana Card number works.
4. Search by insurance membership card number works.
5. Search by next of kin name works.
6. Search by next of kin phone works.
7. Gender filter is no longer shown.
8. Blood group filter is no longer shown.
9. Insurance filter works.
10. Last visit date range filter works using latest visit date.
11. Patient can be marked deceased by authorized user.
12. Unauthorized user cannot mark patient deceased.
13. Deceased patient shows deceased badge.
14. Deceased patient cannot start new visit unless override is allowed.
15. Pagination preserves filters.

---

# 17. Deliverables

Provide:

1. Updated Patient List search.
2. Removed gender and blood group filters.
3. Added insurance filter.
4. Added last visit date range filter.
5. Added deceased status support.
6. Added Mark as Deceased action/modal.
7. Updated backend filtering.
8. Updated frontend filters.
9. Updated routes/requests/controllers/services.
10. Tests or verification notes.
11. Files modified.
12. Remaining TODOs if any.

---

# 18. Important Rules

Do not delete patient records.

Do not remove gender or blood group from patient profile/database.

Only remove gender and blood group filters from the patient list page.

Do not break patient registration.

Do not break visit creation.

Do not allow deceased patients to start new visits without clear authorization.

Do not use unsafe raw SQL.

Do not load all visits just to calculate last visit date.

Now inspect the existing patient list implementation and apply these changes carefully.


# Patient ID Generation

Implement configurable patient ID generation.

The system must generate a unique permanent Patient ID when a patient is created.

The Patient ID must not be manually typed by normal users.

Default pattern:

{PREFIX}-{YEAR}-{SEQUENCE}

Default example:

UHMS-2026-000001

The pattern must be configurable from system settings.

Supported placeholders:

{PREFIX}
{YEAR}
{YY}
{MONTH}
{DAY}
{SEQUENCE}

Settings:

patient_id_prefix
patient_id_pattern
patient_id_sequence_length
patient_id_reset_period

Supported reset periods:

never
yearly
monthly
daily

Rules:

- Patient ID must be unique.
- Patient ID must be generated inside a database transaction.
- Patient ID must be safe under concurrent patient registration.
- Patient ID must never be reused.
- Patient ID must remain unchanged after patient creation.
- Only Super Admin can regenerate or manually edit a Patient ID, and that action must be audited.
- Patient search must support searching by Patient ID.
- Patient list must display Patient ID.
- Visit creation must show Patient ID clearly.

Create a PatientNumberService or PatientIdGeneratorService.

Required method:

generate(): string

The generator must:
1. load current settings
2. determine reset period
3. lock sequence row
4. increment sequence
5. format ID using pattern
6. ensure uniqueness
7. return generated patient ID

Suggested table:

patient_number_sequences
- id
- prefix
- period_type
- period_key
- last_sequence
- created_at
- updated_at

Example period_key:
2026
2026-05
2026-05-21
GLOBAL

Use lockForUpdate() during generation to prevent duplicates.

When creating a patient, assign:

patients.patient_number = generated ID

Do not use patient database ID as the visible Patient ID.