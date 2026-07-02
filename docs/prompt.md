# UHMS Privacy & Patient Data Protection — Phase 1: Sensitive Patient Data Gap Analysis & Field-Level Permissions

## Goal

Perform a complete privacy and security gap analysis of patient data exposure throughout UHMS.

Introduce a new permission model for sensitive patient information so that users without the appropriate permission can still work with patients but cannot view confidential Personally Identifiable Information (PII).

This is **field-level authorization**, not page-level authorization.

Example:

```text
Receptionist
✓ Can search patient
✓ Can register patient
✓ Can create visit
✓ Can identify patient

✗ Cannot see full phone number
✗ Cannot see full email
✗ Cannot see national ID
✗ Cannot see insurance/member numbers
```

Meanwhile:

```text
Medical Records Officer

✓ Can view complete patient profile
✓ Can see full phone number
✓ Can see full email
✓ Can see identifiers
```

The system must remain HIPAA/GDPR-inspired even if the deployment country has different regulations.

Do not weaken existing permissions.

Do not expose sensitive fields through Blade, APIs, exports, reports, audit logs, or search.

---

# Phase 1 Scope

This phase is an analysis plus infrastructure phase.

Do NOT immediately hide fields everywhere.

Instead:

1. Discover every place sensitive patient data is exposed.
2. Classify every sensitive field.
3. Introduce reusable authorization helpers.
4. Introduce masking helpers.
5. Introduce permissions.
6. Document every affected screen.
7. Prepare for phased rollout.

---

# 1. Gap Analysis

Search the entire project for patient information exposure.

Include:

```text
Controllers
Blade views
Vue/Inertia pages if present
API Resources
Transformers
Exports
Imports
Reports
Dashboards
Widgets
Activity logs
Notification templates
SMS templates
Emails
PDFs
Receipts
Print views
Autocomplete search
Patient lookup
Global search
Ajax endpoints
JSON responses
Audit history
```

Produce an inventory of every place patient information is rendered.

---

# 2. Sensitive Data Classification

Classify fields into security levels.

## Level 0

Safe

Examples

```text
Patient ID
Hospital Number
Age
Gender
Visit Number
Queue Number
```

---

## Level 1

Operational

Examples

```text
Nationality
Occupation
Marital Status
Religion
Language
```

---

## Level 2

Personally Identifiable Information (PII)

Examples

```text
Phone number
Alternative phone
Email
Digital address
Residential address
Postal address
GPS coordinates
Emergency contact phone
Emergency contact address
National ID
Passport
Driving licence
Voter ID
NHIS number
Insurance member number
Corporate member number
```

---

## Level 3

Highly Sensitive

Examples

```text
HIV indicators
Mental health markers
Sexual health
Confidential clinical notes
Genetic information
Psychiatric history
Domestic abuse flags
Child protection flags
Court restrictions
```

Do not expose these without explicit permission.

---

# 3. New Permissions

Introduce granular permissions.

Suggested:

```text
patients.pii.view
patients.contact.view
patients.identity.view
patients.address.view
patients.insurance.view
patients.emergency_contact.view
patients.clinical_sensitive.view
patients.export_sensitive.view
```

Do NOT replace existing patient permissions.

These permissions are additive.

Example:

```text
patients.view

+

patients.contact.view
```

---

# 4. Default Role Behaviour

Suggested defaults

Reception

```text
patients.view

NO

patients.contact.view
patients.identity.view
```

Doctor

```text
patients.view
patients.contact.view

NO

patients.identity.view
```

Medical Records

```text
All patient permissions
```

Administrator

```text
All
```

System configuration must remain editable.

---

# 5. Data Masking Rules

When permission is missing, mask data.

Phone

```text
0241234567

↓

024****567
```

Email

```text
john.doe@gmail.com

↓

jo****@gmail.com
```

National ID

```text
GHA123456789

↓

GHA******789
```

Insurance Number

```text
ABC123456789

↓

ABC******789
```

Address

```text
House 12,
Airport Residential Area

↓

Hidden
```

GPS

```text
Hidden
```

Never return NULL when data exists.

Return masked values.

This lets users identify records while protecting privacy.

---

# 6. Central Masking Service

Create reusable services.

Suggested:

```php
PatientPrivacyService

PatientMaskingService

PatientFieldAuthorizationService
```

Responsibilities

```text
Can view field?
Return masked value
Return full value
Permission checks
```

Do NOT duplicate masking logic across Blade files.

---

# 7. Patient Resource Layer

Review:

```text
API Resources

JSON Resources

Transformers
```

Ensure sensitive fields are masked before serialization.

Never rely only on Blade.

---

# 8. Search Behaviour

Patient search should remain usable.

Suggested search result

```text
Hospital Number
Patient Name
Gender
Age

024****567

jo****@gmail.com
```

Do not expose complete contact information.

---

# 9. Dashboard Review

Review all dashboards.

Examples

```text
Recent Patients
Today's Patients
Admissions
Discharges
Emergency
Appointment lists
```

Mask sensitive fields unless permission exists.

---

# 10. Reports

Review reports.

Examples

```text
Patient Register
Visit Register
Admissions
Discharges
Claims
Billing
```

Add masking where appropriate.

Only export full information when:

```text
patients.export_sensitive.view
```

exists.

---

# 11. Print/PDF

Review:

```text
Patient cards
Receipts
Reports
Invoices
PDF exports
```

Sensitive data should follow the same permission model.

---

# 12. Activity Logs

Do not store masked values.

Store actual values only where already required.

However:

Activity log UI should mask sensitive fields unless permission exists.

---

# 13. Audit

Every access to highly sensitive patient information should be auditable.

Suggested events

```text
PATIENT_PII_VIEWED
PATIENT_IDENTITY_VIEWED
PATIENT_ADDRESS_VIEWED
PATIENT_CLINICAL_SENSITIVE_VIEWED
```

Do not flood logs.

Only log meaningful access.

---

# 14. Helper Components

Create reusable Blade helpers/components.

Example

```php
<x-patient-phone />

<x-patient-email />

<x-patient-address />

<x-patient-identity />
```

These automatically:

```text
check permission

mask if needed

show full value if allowed
```

---

# 15. Localisation

Add translations.

Examples

```text
Hidden

Sensitive Information

Restricted

Masked

Insufficient Permission

Protected Patient Data
```

Maintain EN/FR parity.

---

# 16. Deliverables

Produce:

```text
PATIENT_DATA_PRIVACY_GAP_ANALYSIS_REPORT.md

PATIENT_FIELD_CLASSIFICATION.md

PATIENT_PRIVACY_IMPLEMENTATION_PLAN.md
```

Each must include:

* affected files
* exposed fields
* risk level
* recommended permission
* masking strategy
* rollout order

---

# 17. Rollout Plan

Do NOT modify every page in this phase.

Instead produce a prioritized rollout.

Priority:

```text
Patient search

Patient profile

Patient header

Visit pages

Appointment pages

Dashboards

Reports

Exports

PDFs

Notifications

Remaining modules
```

---

# 18. Acceptance Criteria

This phase is complete only when:

* Every patient field is classified.
* Every exposure point is documented.
* New permissions are designed.
* Masking services are introduced.
* No duplicate masking logic exists.
* Reports identify every affected module.
* Rollout order is documented.
* Existing workflows remain unchanged.
* No production behaviour is broken.

Proceed with the patient privacy gap analysis and infrastructure implementation now.
