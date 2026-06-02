````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

The Blood Bank module has already been implemented, but some critical features are missing/incomplete.

We need to refine and complete the Blood Bank module with:

1. WHO-compliant donor screening process
2. Recipient details
3. Blood compatibility logic
4. Blood request compatibility validation
5. Crossmatch workflow improvements
6. Safer blood issue/transfusion rules
7. Better reports, logs, and UI updates

Do not rebuild the Blood Bank module from scratch.

First inspect the current implementation, identify the gaps, preserve what works, and implement the missing features.

Do not break:
- patients
- visits
- emergency
- admission
- theatre
- blood donors
- blood donations
- blood units
- blood requests
- crossmatching
- blood issue/transfusion
- billing
- notifications
- logs
- visit preview
- reports

---

# 1. Main Problems to Fix

The Blood Bank module is missing:

1. Proper WHO-style donor screening process.
2. Structured donor eligibility assessment.
3. Donor deferral workflow.
4. Infectious disease screening.
5. Recipient clinical details.
6. Recipient blood group and transfusion requirement details.
7. Blood group compatibility logic.
8. Rh compatibility logic.
9. Component-specific compatibility checks.
10. Safer crossmatch rules.
11. Safer issue/transfusion validation.
12. Better UI for donor screening and recipient matching.

---

# 2. WHO Donor Screening Process

Implement a structured blood donor screening workflow inspired by WHO blood donor selection principles.

The system must support:

- donor registration
- donor questionnaire
- donor medical history screening
- donor physical assessment
- donor eligibility decision
- temporary/permanent deferral
- donation collection
- infectious disease screening
- blood unit approval/rejection
- audit trail

Important:

Do not allow blood units to become AVAILABLE unless donor screening and blood screening are complete and passed.

---

# 3. Donor Screening Stages

Add or update donor screening stages:

```text
REGISTERED
QUESTIONNAIRE_PENDING
QUESTIONNAIRE_COMPLETED
PHYSICAL_ASSESSMENT_PENDING
PHYSICAL_ASSESSMENT_COMPLETED
ELIGIBLE
TEMPORARILY_DEFERRED
PERMANENTLY_DEFERRED
DONATION_COLLECTED
LAB_SCREENING_PENDING
LAB_SCREENING_PASSED
LAB_SCREENING_FAILED
APPROVED_FOR_USE
REJECTED
````

The UI should clearly show the donor’s current screening status.

---

# 4. Donor Questionnaire

Create a donor questionnaire form.

The questionnaire should include at least:

## Identity / Basic Information

* donor name
* age/date of birth
* gender
* phone number
* address
* occupation
* donor type
* previous donation history

## General Health

* feeling well today
* current fever
* recent illness
* recent medication
* recent surgery
* recent hospitalization
* unexplained weight loss
* chronic disease history
* history of fainting during donation

## Infection Risk

* history of jaundice/hepatitis
* HIV risk exposure
* sexually transmitted infection history
* recent tattoo/piercing
* recent blood transfusion
* recent needle-stick exposure
* recent malaria symptoms or treatment
* tuberculosis symptoms
* recent travel to high-risk area

## Women-specific Questions where applicable

* currently pregnant
* recently delivered
* breastfeeding
* recent miscarriage/abortion
* heavy menstrual bleeding

## Lifestyle / Risk Factors

* alcohol use before donation
* drug injection history
* high-risk sexual exposure
* contact with infectious disease

## Consent

* donor consent to donate
* consent for testing blood
* consent to be contacted if abnormal result

Each questionnaire answer should be stored.

Do not store questionnaire only as plain text if structured storage is possible.

---

# 5. Donor Physical Assessment

Add donor physical assessment.

Capture:

* weight
* temperature
* pulse
* blood pressure
* hemoglobin level
* general appearance
* venous access suitability
* donation fitness notes
* assessed_by
* assessed_at

Eligibility rules should check:

* donor age range
* minimum weight
* acceptable hemoglobin
* acceptable temperature
* acceptable pulse
* acceptable blood pressure
* questionnaire risk answers

Make thresholds configurable.

Suggested default config:

```text
minimum_age = 18
maximum_age = 65
minimum_weight_kg = 50
minimum_hb_male = 13.0
minimum_hb_female = 12.5
maximum_temperature = 37.5
```

Adapt thresholds to hospital policy.

---

# 6. Donor Eligibility Decision

After questionnaire and physical assessment, the system should calculate/suggest eligibility.

Possible decisions:

```text
ELIGIBLE
TEMPORARILY_DEFERRED
PERMANENTLY_DEFERRED
NEEDS_REVIEW
```

For deferral, require:

* deferral type
* reason
* deferral_until nullable
* reviewed_by
* reviewed_at
* notes

Temporary deferral examples:

* low hemoglobin
* low weight
* fever
* recent illness
* recent surgery
* recent malaria treatment
* pregnancy/recent delivery
* recent tattoo/piercing

Permanent deferral examples:

* confirmed HIV
* confirmed hepatitis B/C where policy requires
* high-risk permanent exclusion
* severe chronic illness where applicable

Do not allow donation collection if donor is not eligible unless override permission exists.

Override must require reason and log.

---

# 7. Infectious Disease / Laboratory Screening

After blood donation collection, implement blood screening tests.

Screening should include configurable tests such as:

```text
HIV
Hepatitis B
Hepatitis C
Syphilis
Malaria
Blood grouping
Rh typing
Other local screening tests
```

For each screening test, capture:

* test name
* result
* performed_by
* performed_at
* verified_by nullable
* verified_at nullable
* notes

Results:

```text
NEGATIVE
POSITIVE
REACTIVE
NON_REACTIVE
INCONCLUSIVE
NOT_DONE
```

Blood unit approval rules:

* if required tests are negative/non-reactive, unit can be approved
* if any mandatory test is positive/reactive, unit must be rejected/quarantined/discarded
* inconclusive result should keep unit quarantined/pending review
* screening result must be verified if verification workflow exists

Do not mark unit AVAILABLE before screening passes.

---

# 8. Blood Unit Screening Status

Update blood unit status handling.

Blood unit should support:

```text
COLLECTED
QUARANTINED
SCREENING_PENDING
SCREENING_PASSED
SCREENING_FAILED
AVAILABLE
RESERVED
CROSSMATCHED
ISSUED
TRANSFUSED
EXPIRED
DISCARDED
REJECTED
```

Rules:

* newly collected unit starts as COLLECTED or QUARANTINED
* unit remains unavailable until screening passes
* screening failed units become REJECTED or DISCARDED based on workflow
* only AVAILABLE or properly RESERVED/CROSSMATCHED units can be issued
* expired units cannot be issued
* discarded/rejected units cannot be issued

---

# 9. Recipient Details

Implement recipient details for blood requests.

A blood request must capture recipient clinical details, not only patient_id.

Create or update recipient details fields.

Recommended table:

```text
blood_recipients
- id
- blood_request_id
- patient_id
- visit_id nullable
- admission_id nullable
- emergency_case_id nullable
- theatre_case_id nullable
- patient_blood_group nullable
- patient_rh_factor nullable
- diagnosis nullable
- clinical_indication
- hemoglobin_level nullable
- pregnancy_status nullable
- previous_transfusion_reaction boolean default false
- previous_transfusion_reaction_notes nullable
- transfusion_history nullable
- special_requirements nullable
- requested_component_type
- units_required
- urgency
- requested_by
- created_at
- updated_at
```

If a separate table is too much, add these fields to `blood_requests`, but keep the data structured.

---

# 10. Recipient Clinical Information

Blood request form should capture:

* patient blood group
* Rh factor
* component requested
* units required
* clinical indication
* diagnosis
* current hemoglobin level
* urgency
* pregnancy status if applicable
* history of transfusion reaction
* special requirements
* requesting doctor
* department/source
* admission/emergency/theatre context

Clinical indications examples:

```text
Severe anemia
Acute bleeding
Surgery preparation
Postpartum hemorrhage
Trauma
Exchange transfusion
Thrombocytopenia
Coagulopathy
Burns
Massive transfusion protocol
```

Urgency:

```text
ROUTINE
URGENT
EMERGENCY
MASSIVE_TRANSFUSION
```

---

# 11. Blood Compatibility Logic

Implement BloodCompatibilityService.

This service must determine whether a blood unit is compatible with a recipient.

Create:

```text
BloodCompatibilityService
```

Suggested methods:

```php
public function isCompatible(
    string $recipientGroup,
    string $recipientRh,
    string $donorGroup,
    string $donorRh,
    string $componentType
): bool;

public function compatibilityDetails(...): array;

public function compatibleGroupsFor(string $recipientGroup, string $recipientRh, string $componentType): array;

public function explainIncompatibility(...): string;
```

Do not hardcode compatibility only in Vue.

Backend must enforce compatibility before issuing blood.

---

# 12. Red Cell / Whole Blood Compatibility

For red cell / whole blood transfusion, use standard ABO compatibility:

Recipient O can receive:

* O

Recipient A can receive:

* A
* O

Recipient B can receive:

* B
* O

Recipient AB can receive:

* AB
* A
* B
* O

Rh rule:

Rh negative recipient should receive Rh negative blood unless emergency override is allowed.

Rh positive recipient can receive Rh positive or Rh negative blood.

Examples:

```text
O- recipient: O- only
O+ recipient: O+, O-
A- recipient: A-, O-
A+ recipient: A+, A-, O+, O-
B- recipient: B-, O-
B+ recipient: B+, B-, O+, O-
AB- recipient: AB-, A-, B-, O-
AB+ recipient: AB+, AB-, A+, A-, B+, B-, O+, O-
```

Emergency override:

* allow incompatible or Rh-positive emergency issue only with special permission
* require reason
* log heavily
* mark as emergency release / uncrossmatched if applicable

---

# 13. Plasma Compatibility

Plasma compatibility is different from red cells.

For plasma:

Recipient O can receive:

* O
* A
* B
* AB depending local policy, but standard plasma donor compatibility differs.

Implement configurable plasma compatibility.

Recommended default plasma donor-to-recipient compatibility:

```text
AB plasma can be given to all ABO recipients.
A plasma can be given to A and O recipients.
B plasma can be given to B and O recipients.
O plasma can be given to O recipients.
```

Confirm local policy before production.

Make compatibility matrix configurable in config/blood_bank.php.

---

# 14. Platelet Compatibility

Platelets are often ABO-compatible preferred but may allow broader compatibility based on policy.

Implement:

* preferred ABO-compatible platelet matching
* allow non-identical platelet issue with override permission
* require reason if not ABO-compatible
* mark compatibility status as compatible, compatible_with_caution, or incompatible

Make platelet compatibility configurable.

---

# 15. Crossmatch Workflow Improvement

Blood request workflow should be:

```text
Blood Request Created
↓
Recipient Details Captured
↓
Compatible Available Units Suggested
↓
Blood Bank Selects Units
↓
Crossmatch Performed
↓
Compatible Units Reserved
↓
Blood Issued
↓
Transfusion Recorded
```

Crossmatch should capture:

* blood_request_id
* blood_unit_id
* recipient blood group/Rh
* donor blood group/Rh
* component type
* compatibility_status
* crossmatch_result
* performed_by
* performed_at
* verified_by nullable
* verified_at nullable
* notes

Compatibility status:

```text
COMPATIBLE
COMPATIBLE_WITH_CAUTION
INCOMPATIBLE
EMERGENCY_OVERRIDE
```

Crossmatch result:

```text
PENDING
COMPATIBLE
INCOMPATIBLE
CANCELLED
```

Do not issue incompatible blood unless emergency override permission exists.

---

# 16. Compatible Unit Suggestion

On Blood Request page, show compatible units.

The UI should show:

* unit number
* blood group/Rh
* component type
* expiry date
* days to expiry
* screening status
* current status
* compatibility status
* storage location
* donor type if useful

Sort suggestions by:

1. exact match
2. compatible match
3. earliest expiry
4. location priority

Example:

Patient A+ needs packed red cells.

Suggested units:

* A+ PRBC Unit 001 — exact compatible
* A- PRBC Unit 002 — compatible
* O+ PRBC Unit 003 — compatible
* O- PRBC Unit 004 — compatible universal donor

Do not show incompatible units as selectable unless user has override permission.

---

# 17. Recipient Compatibility UI

On blood request / crossmatch page, show compatibility clearly.

Example:

```text
Recipient: A+
Requested Component: Packed Red Cells

Compatible groups:
A+, A-, O+, O-

Selected Unit:
O- Packed Red Cells
Compatibility: Compatible
Reason: O- red cells are compatible with A+ recipient.
```

If incompatible:

```text
Selected Unit:
B+ Packed Red Cells
Compatibility: Incompatible
Reason: B red cells are not compatible with A recipient.
```

---

# 18. Emergency Blood Release

Support emergency release where blood may be issued before full crossmatch.

Use carefully.

Emergency release types:

```text
UNCROSSMATCHED_O_NEGATIVE
UNCROSSMATCHED_GROUP_SPECIFIC
EMERGENCY_INCOMPATIBLE_OVERRIDE
```

Rules:

* requires permission
* requires reason
* requires doctor authorization
* logs critical event
* notification to blood bank supervisor
* clearly marks issue as emergency release
* later crossmatch should still be recorded if required

Suggested permission:

```text
blood_bank.emergency_release
blood_bank.compatibility.override
```

---

# 19. Blood Issue Validation

Before issuing blood, validate:

* request exists and is approved
* recipient details exist
* unit exists
* unit is not expired
* unit is not discarded/rejected
* unit screening passed
* unit status is AVAILABLE / RESERVED / CROSSMATCHED
* component type matches requested component or allowed substitute
* compatibility passed or emergency override exists
* crossmatch compatible if required
* unit is not already issued/transfused
* user has permission

Do not issue unsafe blood silently.

---

# 20. Transfusion Record

Improve transfusion record.

Capture:

* blood_issue_id
* patient_id
* visit/admission/emergency/theatre context
* unit_number
* component_type
* blood_group/Rh
* transfusion_started_at
* transfusion_completed_at nullable
* transfused_by
* witnessed_by nullable
* pre_transfusion_vitals nullable/json
* post_transfusion_vitals nullable/json
* reaction_occurred boolean
* reaction_type nullable
* reaction_notes nullable
* outcome
* notes

Reaction types:

```text
FEVER
CHILLS
RASH
BREATHING_DIFFICULTY
HYPOTENSION
HEMOLYTIC_REACTION_SUSPECTED
ANAPHYLAXIS
OTHER
```

Outcome:

```text
COMPLETED
STOPPED_DUE_TO_REACTION
PARTIALLY_TRANSFUSED
CANCELLED
```

---

# 21. Donor and Recipient Link to Visit Preview

Update Visit Preview.

Show:

* blood request created
* recipient details
* compatible units suggested/selected
* crossmatch result
* blood issued
* transfusion started
* transfusion completed
* reaction if any
* emergency release if any

Do not show donor sensitive information unnecessarily in patient-facing printouts.

Internal clinical preview can show unit number and component.

---

# 22. Blood Bank Reports Updates

Update Blood Bank reports to include screening and compatibility.

Reports:

## Donor Screening Report

Columns:

* Donor
* Donor Number
* Screening Date
* Eligibility Decision
* Deferral Reason
* Assessed By
* Status

## Infectious Disease Screening Report

Columns:

* Donation No.
* Unit No.
* HIV
* Hepatitis B
* Hepatitis C
* Syphilis
* Malaria
* Screening Status
* Verified By

## Compatibility / Crossmatch Report

Columns:

* Request No.
* Patient
* Recipient Group
* Unit No.
* Donor Group
* Component
* Compatibility Status
* Crossmatch Result
* Performed By
* Verified By

## Transfusion Reaction Report

Columns:

* Patient
* Unit No.
* Component
* Reaction Type
* Notes
* Transfused By
* Date

---

# 23. Blood Bank Settings

Add settings/config for:

* donor minimum age
* donor maximum age
* minimum weight
* hemoglobin thresholds
* donation interval
* required screening tests
* component expiry days
* compatibility matrices
* emergency release permissions/rules
* require crossmatch before issue yes/no per component
* allow Rh override in emergency yes/no

Use config file or settings table depending project convention.

Suggested config:

```php
config/blood_bank.php
```

---

# 24. UI Pages to Update

Update or create:

## Donor Screening Page

Sections:

* donor information
* questionnaire
* physical assessment
* eligibility result
* deferral decision
* consent
* screening history

## Donation Screening Page

Sections:

* donation details
* screening tests
* verification
* approve/reject unit
* generated blood units

## Blood Request Page

Sections:

* recipient details
* requested component/units
* compatible unit suggestions
* selected units
* crossmatch records
* issue records
* transfusion records

## Crossmatch Modal/Page

Fields:

* selected blood unit
* recipient blood group/Rh
* donor blood group/Rh
* component
* compatibility result auto-calculated
* crossmatch result
* performed_by
* notes

## Blood Issue Modal

Show safety checks before issue.

Checklist:

* screening passed
* unit not expired
* compatibility passed
* crossmatch compatible
* request approved
* emergency override if applicable

---

# 25. Permissions

Add or verify:

```text
blood_bank.screening.view
blood_bank.screening.perform
blood_bank.screening.verify
blood_bank.donor.defer
blood_bank.donor.override_eligibility
blood_bank.compatibility.view
blood_bank.crossmatch.perform
blood_bank.crossmatch.verify
blood_bank.compatibility.override
blood_bank.emergency_release
blood_bank.recipient_details.manage
blood_bank.transfusion.record
blood_bank.transfusion.reaction_record
blood_bank.settings.manage
```

Existing permissions should remain:

```text
blood_bank.dashboard.view
blood_bank.donors.view
blood_bank.donors.create
blood_bank.donors.update
blood_bank.donations.view
blood_bank.donations.create
blood_bank.donations.screen
blood_bank.units.view
blood_bank.units.update
blood_bank.requests.view
blood_bank.requests.create
blood_bank.requests.approve
blood_bank.requests.reject
blood_bank.crossmatch.perform
blood_bank.issue
blood_bank.transfusion.record
blood_bank.discard
blood_bank.reports.view
```

---

# 26. Logs and Notifications

Use ActivityLogService and NotificationService.

Log:

* donor questionnaire completed
* donor physical assessment completed
* donor eligibility decision
* donor deferred
* donation screening result entered
* screening verified
* unit approved/rejected
* recipient details updated
* compatibility calculated
* crossmatch performed
* crossmatch verified
* emergency release
* incompatible override
* blood issued
* transfusion recorded
* transfusion reaction recorded

Notify:

* screening failed
* unit approved
* urgent blood request
* compatible unit found
* crossmatch completed
* blood issued
* transfusion reaction recorded
* emergency release performed

---

# 27. Data Integrity Rules

* Do not make unscreened blood available.
* Do not issue screening failed blood.
* Do not issue expired blood.
* Do not issue discarded/rejected blood.
* Do not issue incompatible blood without emergency override.
* Do not allow crossmatch without recipient details.
* Do not allow issue without approved request.
* Do not issue the same unit twice.
* Do not lose donor screening history.
* Do not overwrite recipient details without logs.
* Do not expose sensitive donor screening details unnecessarily.

---

# 28. Tests Required

Add or update tests.

## Donor Screening

1. Donor questionnaire can be completed.
2. Physical assessment can be recorded.
3. Eligible donor can donate.
4. Temporarily deferred donor cannot donate.
5. Permanently deferred donor cannot donate.
6. Override eligibility requires permission and reason.
7. Failed questionnaire risk creates deferral suggestion.
8. Low hemoglobin creates temporary deferral suggestion.

## Donation Screening

9. Donation creates quarantined/screening pending unit.
10. Unit does not become available before screening.
11. Passed screening makes unit available.
12. Failed screening rejects/quarantines unit.
13. Inconclusive screening keeps unit pending/quarantined.

## Recipient Details

14. Blood request requires recipient details.
15. Recipient blood group/Rh is stored.
16. Clinical indication is stored.
17. Previous reaction details are stored.

## Compatibility

18. A+ recipient can receive A+, A-, O+, O- red cells.
19. O- recipient can receive only O- red cells.
20. AB+ recipient can receive all ABO/Rh red cells.
21. Incompatible red cell unit is rejected.
22. Rh negative recipient cannot receive Rh positive without override.
23. Plasma compatibility uses configured matrix.
24. Platelet compatibility uses configured rules.

## Crossmatch

25. Compatible unit can be crossmatched.
26. Incompatible unit cannot be crossmatched as compatible.
27. Crossmatch requires recipient details.
28. Crossmatch stores performed_by and result.
29. Verified crossmatch stores verified_by.

## Issue / Transfusion

30. Compatible crossmatched unit can be issued.
31. Incompatible unit cannot be issued without override.
32. Expired unit cannot be issued.
33. Screening failed unit cannot be issued.
34. Already issued unit cannot be issued again.
35. Emergency release requires permission and reason.
36. Transfusion record can be created.
37. Transfusion reaction can be recorded.

## Reports / Preview

38. Donor screening report shows eligibility decisions.
39. Compatibility report shows crossmatch status.
40. Transfusion reaction report shows reactions.
41. Visit Preview shows blood request/crossmatch/issue/transfusion events.

---

# 29. Deliverables

Provide:

1. Gap analysis of current Blood Bank module.
2. WHO-style donor screening workflow.
3. Donor questionnaire.
4. Donor physical assessment.
5. Donor eligibility/deferral workflow.
6. Infectious disease screening.
7. Screening verification.
8. Recipient details implementation.
9. BloodCompatibilityService.
10. Component-specific compatibility rules.
11. Compatible unit suggestions.
12. Improved crossmatch workflow.
13. Emergency blood release/override workflow.
14. Improved blood issue safety validation.
15. Improved transfusion record.
16. Reports updated.
17. Visit Preview updated.
18. Permissions/seeders updated.
19. Logs/notifications integration.
20. Tests or verification notes.
21. Files modified.
22. Remaining TODOs.

---

# 30. Important Rules

Do not rebuild the Blood Bank module from scratch.

Do not mark unscreened units as available.

Do not issue incompatible blood without emergency override.

Do not issue expired, rejected, discarded, or already issued units.

Do not allow crossmatch without recipient details.

Do not allow issue without recipient compatibility validation.

Do not expose sensitive donor information unnecessarily.

Do not break existing blood bank requests, units, issue, transfusion, billing, visit preview, notifications, logs, or reports.

Now inspect the current UHMS Blood Bank implementation and complete the missing WHO-style screening, recipient details, and compatibility features described above.

```
```
