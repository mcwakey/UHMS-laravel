You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine

Phase 3 added the specialty layout engine. The consultation workspace now consumes `specialtyLayout`, renders specialty-specific section ordering/labels, shows a compact specialty identity strip, and safely renders specialist-only sections through a generic shell.

Now implement:

# Consultation Specialist Extension — Phase 4: Structured Specialist Forms

## Phase 4 Goal

Replace the generic specialist-only section shell with real structured clinical forms for the first three specialist profiles:

```text
physiotherapy
ophthalmology
dental
```

This phase must store structured specialist data using the Phase 1 table:

```text
consultation_specialty_entries
```

The goal is to make specialist consultations feel clinically useful while still preserving the shared consultation engine.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate Physio Consultation, Eye Consultation, or Dental Consultation modules.

Do not change the existing general consultation behavior.

Do not implement specialty completion readiness yet. That comes in a later phase.

Do not implement specialty summary builder yet. That comes in a later phase.

Do not implement billing/service mapping yet. That comes later.

Do not implement order sets yet unless absolutely necessary; that is a later phase.

Do not run the full test suite yet.

Only run focused checks for this phase.

The existing core consultation forms, endpoints, IDs, anchors, badges, Ajax refresh, and JavaScript selectors must remain stable.

If a specialist structured form fails to load, the page must fall back to the generic specialist shell instead of breaking the consultation workspace.

---

# Current Context

Phase 3 report confirms:

* Main workspace view: `resources/views/consultations/show.blade.php`
* Sidebar: `resources/views/consultations/partials/workflow-sidebar.blade.php`
* Core panes are inline in `show.blade.php`
* Existing JS depends on stable IDs, badge IDs, form `data-refresh-section` values, and list IDs
* Generic specialist shell currently renders specialist-only sections
* Existing save behavior for core sections remains unchanged

Use this architecture carefully.

---

# Required Deliverables

## 1. Inspect Existing Save Patterns

Before coding, inspect how the current consultation workspace saves:

```text
complaints
HOPC
examination
diagnosis
investigations
prescriptions
procedures
tasks
notes
summary
```

Identify:

```text
routes
controllers
request validation style
Ajax/form conventions
response format
section refresh behavior
error rendering
audit logging patterns
ActivityLog usage
author attribution patterns
```

Follow the existing project convention. Do not invent a separate frontend pattern if the consultation workspace already has one.

Document findings in the phase report.

---

## 2. Add Specialist Entry Service

Create:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyEntryService.php
```

Responsibilities:

```php
getEntriesForConsultation($consultation, ?ConsultationSpecialtyProfile $profile = null): Collection;

getEntry($consultation, ConsultationSpecialtyProfile $profile, string $sectionKey): ?ConsultationSpecialtyEntry;

upsertEntry(
    $consultation,
    ConsultationSpecialtyProfile $profile,
    string $sectionKey,
    array $entry,
    User $user
): ConsultationSpecialtyEntry;

deleteEntry(
    ConsultationSpecialtyEntry $entry,
    User $user
): bool;

entriesAsArray($consultation, ConsultationSpecialtyProfile $profile): array;
```

Rules:

* Use `updateOrCreate` or equivalent safe logic.
* One consultation should have one latest structured entry per specialty profile and section key.
* Store structured data in `entry` JSON.
* Set `created_by` and `updated_by`.
* Respect existing consultation ownership/authorization patterns.
* Add audit/activity logging if the project logs consultation clinical changes.
* Do not store empty useless entries unless the section intentionally has meaningful empty state.
* Use DB transactions when saving multiple related pieces.

---

## 3. Add Specialist Entry Controller

Create a controller following existing consultation controller conventions.

Suggested path:

```text
app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtyEntryController.php
```

or if the project has a different consultation controller namespace, follow it.

Required actions:

```php
store(Request $request, Consultation $consultation)
update(Request $request, Consultation $consultation, string $sectionKey)
destroy(Request $request, Consultation $consultation, string $sectionKey)
```

Adapt route model binding to the actual existing consultation model and route style.

Payload should include:

```text
specialty_profile_id
section_key
entry
```

Validation must be section-aware.

Do not allow arbitrary unsafe sections. The section key must exist in the active specialty profile layout or seeded specialty sections.

Do not allow saving to inactive profile.

Do not allow saving to a profile that is not the resolved current profile unless the existing project explicitly supports profile override by permission.

Return responses using the same convention as the existing consultation section saves:

```text
redirect back with flash
JSON response for Ajax
section refresh partial response
```

Use the current workspace pattern.

---

## 4. Add Routes

Add routes using existing consultation route naming conventions.

Suggested names, adapt to project convention:

```text
doctor.consultations.specialty-entries.store
doctor.consultations.specialty-entries.update
doctor.consultations.specialty-entries.destroy
```

Routes should be protected by the same auth/middleware/permission pattern as existing consultation clinical save routes.

Do not expose these routes to unauthorized staff.

---

## 5. Add Request Validation

Create request classes if the project uses Form Requests:

```text
app/Http/Requests/Consultation/StoreConsultationSpecialtyEntryRequest.php
app/Http/Requests/Consultation/UpdateConsultationSpecialtyEntryRequest.php
```

Or use controller validation if that is the existing pattern.

Validation must be section-aware.

---

# Section Validation Requirements

## Physiotherapy Sections

### presenting_problem

Fields:

```text
problem_description        nullable|string|max:2000
onset_date                 nullable|date
onset_type                 nullable|string|max:100
mechanism_of_injury        nullable|string|max:1000
affected_area              nullable|string|max:255
referral_reason            nullable|string|max:1000
```

### pain_assessment

Fields:

```text
pain_score                 nullable|integer|min:0|max:10
pain_location              nullable|string|max:255
pain_character             nullable|string|max:255
aggravating_factors        nullable|string|max:1000
relieving_factors          nullable|string|max:1000
pain_pattern               nullable|string|max:255
```

### functional_limitation

Fields:

```text
mobility_limitation        nullable|string|max:1000
work_limitation            nullable|string|max:1000
self_care_limitation       nullable|string|max:1000
walking_tolerance          nullable|string|max:255
standing_tolerance         nullable|string|max:255
functional_goal            nullable|string|max:1000
```

### physical_assessment

Fields:

```text
range_of_motion            nullable|string|max:1000
muscle_strength            nullable|string|max:1000
posture                    nullable|string|max:1000
gait                       nullable|string|max:1000
balance                    nullable|string|max:1000
special_tests              nullable|string|max:1000
assessment_notes           nullable|string|max:2000
```

### treatment_plan

Fields:

```text
treatment_goals            nullable|string|max:2000
modalities                 nullable|array
modalities.*               string|max:100
session_frequency          nullable|string|max:100
number_of_sessions         nullable|integer|min:1|max:100
expected_duration          nullable|string|max:100
precautions                nullable|string|max:1000
```

### therapy_session

Fields:

```text
session_number             nullable|integer|min:1|max:100
therapy_given              nullable|string|max:2000
patient_response           nullable|string|max:1000
post_session_pain_score    nullable|integer|min:0|max:10
next_session_plan          nullable|string|max:1000
```

### home_exercise_plan

Fields:

```text
exercises                  nullable|array
exercises.*                string|max:255
frequency                  nullable|string|max:100
instructions               nullable|string|max:2000
warnings                   nullable|string|max:1000
```

### progress_notes

Fields:

```text
progress_summary           nullable|string|max:2000
improvement_score          nullable|integer|min:0|max:100
barriers                   nullable|string|max:1000
next_review_date           nullable|date
```

---

## Ophthalmology Sections

### visual_acuity

Fields:

```text
right_eye_unaided          nullable|string|max:50
left_eye_unaided           nullable|string|max:50
right_eye_pinhole          nullable|string|max:50
left_eye_pinhole           nullable|string|max:50
right_eye_corrected        nullable|string|max:50
left_eye_corrected         nullable|string|max:50
notes                      nullable|string|max:1000
```

### refraction

Fields:

```text
right_sphere               nullable|numeric|min:-30|max:30
right_cylinder             nullable|numeric|min:-20|max:20
right_axis                 nullable|integer|min:0|max:180
right_add                  nullable|numeric|min:0|max:10
left_sphere                nullable|numeric|min:-30|max:30
left_cylinder              nullable|numeric|min:-20|max:20
left_axis                  nullable|integer|min:0|max:180
left_add                   nullable|numeric|min:0|max:10
refraction_notes           nullable|string|max:1000
```

### iop

Fields:

```text
right_eye_iop              nullable|numeric|min:0|max:80
left_eye_iop               nullable|numeric|min:0|max:80
method                     nullable|string|max:100
measured_at                nullable|date
notes                      nullable|string|max:1000
```

### eye_examination

Fields:

```text
lids                       nullable|string|max:1000
conjunctiva                nullable|string|max:1000
cornea                     nullable|string|max:1000
anterior_chamber           nullable|string|max:1000
pupil                      nullable|string|max:1000
lens                       nullable|string|max:1000
fundus                     nullable|string|max:1000
retina                     nullable|string|max:1000
optic_disc                 nullable|string|max:1000
examination_notes          nullable|string|max:2000
```

### follow_up

Fields:

```text
follow_up_date             nullable|date
follow_up_reason           nullable|string|max:1000
warning_signs              nullable|string|max:1000
patient_instructions       nullable|string|max:2000
```

---

## Dental Sections

### tooth_chart

Fields:

```text
tooth_number               nullable|string|max:20
tooth_surface              nullable|string|max:100
condition                  nullable|string|max:255
mobility                   nullable|string|max:100
percussion                 nullable|string|max:100
notes                      nullable|string|max:1000
```

Important:

* Keep this simple in Phase 4.
* Do not build a complex graphical odontogram yet.
* Use a structured table/list UI if possible.
* The graphical tooth chart can come later.

### oral_examination

Fields:

```text
oral_hygiene               nullable|string|max:255
gingiva                    nullable|string|max:1000
mucosa                     nullable|string|max:1000
occlusion                  nullable|string|max:1000
swelling                   nullable|string|max:1000
bleeding                   nullable|string|max:1000
examination_notes          nullable|string|max:2000
```

### dental_diagnosis

Fields:

```text
diagnosis_text             nullable|string|max:2000
tooth_involved             nullable|string|max:100
severity                   nullable|string|max:100
differential_diagnosis     nullable|string|max:1000
```

### dental_xray

Fields:

```text
xray_type                  nullable|string|max:100
xray_requested             nullable|boolean
xray_findings              nullable|string|max:2000
attachment_reference       nullable|string|max:255
```

Do not implement file upload in this phase unless the existing consultation document upload pattern already makes it trivial and safe.

### dental_procedures

Fields:

```text
procedure_planned          nullable|string|max:1000
procedure_performed        nullable|string|max:1000
anaesthesia_used           nullable|string|max:255
materials_used             nullable|string|max:1000
post_procedure_notes       nullable|string|max:2000
```

Important:

* Do not create billable procedure records yet.
* This is clinical structured documentation only.
* Procedure billing/service mapping comes later.

### consent

Fields:

```text
consent_required           nullable|boolean
consent_obtained           nullable|boolean
consent_type               nullable|string|max:255
consent_notes              nullable|string|max:1000
```

Do not enforce blocking completion yet.

---

## 6. Add Structured Form Partials

Create specialist section partials under the existing consultation partials convention.

Suggested structure:

```text
resources/views/consultations/partials/specialty/forms/physiotherapy/
resources/views/consultations/partials/specialty/forms/ophthalmology/
resources/views/consultations/partials/specialty/forms/dental/
```

Suggested files:

```text
physiotherapy/presenting-problem.blade.php
physiotherapy/pain-assessment.blade.php
physiotherapy/functional-limitation.blade.php
physiotherapy/physical-assessment.blade.php
physiotherapy/treatment-plan.blade.php
physiotherapy/therapy-session.blade.php
physiotherapy/home-exercise-plan.blade.php
physiotherapy/progress-notes.blade.php

ophthalmology/visual-acuity.blade.php
ophthalmology/refraction.blade.php
ophthalmology/iop.blade.php
ophthalmology/eye-examination.blade.php
ophthalmology/follow-up.blade.php

dental/tooth-chart.blade.php
dental/oral-examination.blade.php
dental/dental-diagnosis.blade.php
dental/dental-xray.blade.php
dental/dental-procedures.blade.php
dental/consent.blade.php
```

Rules:

* Use current UHMS form styling.
* Keep forms compact and readable.
* Use the same save UX pattern as current consultation sections.
* Load existing saved `consultation_specialty_entries.entry` values back into the form.
* Show last updated information if the project convention supports it.
* Show validation errors using existing style.
* Add “Save section” button or equivalent.
* Do not introduce a modal unless existing consultation design requires it.
* Specialist section should feel like the existing hidden/reveal compartment style.

---

## 7. Update Section Component Registry

Update:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtySectionComponentRegistry.php
```

Map specialist section keys to real structured form partials.

### Physiotherapy mappings

```text
presenting_problem      -> physiotherapy presenting problem form
pain_assessment         -> physiotherapy pain assessment form
functional_limitation   -> physiotherapy functional limitation form
physical_assessment     -> physiotherapy physical assessment form
treatment_plan          -> physiotherapy treatment plan form
therapy_session         -> physiotherapy therapy session form
home_exercise_plan      -> physiotherapy home exercise plan form
progress_notes          -> physiotherapy progress notes form
```

### Ophthalmology mappings

```text
visual_acuity           -> ophthalmology visual acuity form
refraction              -> ophthalmology refraction form
iop                     -> ophthalmology IOP form
eye_examination         -> ophthalmology eye examination form
follow_up               -> ophthalmology follow-up form
```

### Dental mappings

```text
tooth_chart             -> dental tooth chart form
oral_examination        -> dental oral examination form
dental_diagnosis        -> dental diagnosis form
dental_xray             -> dental X-ray form
dental_procedures       -> dental procedures form
consent                 -> dental consent form
```

Important:

* Keep generic fallback for unknown future sections.
* Do not accidentally map core general sections to specialist forms.
* Do not break aliases already created in Phase 3.

---

## 8. Pass Specialist Entries to the View

Update the consultation workspace controller/provider to pass:

```text
specialtyEntries
```

as a normalized array keyed by section key.

Example:

```php
[
    'pain_assessment' => [
        'pain_score' => 7,
        'pain_location' => 'lower back',
    ],
]
```

Only include entries for the resolved active specialty profile.

Do not expose entries from another profile unless needed for audit/history later.

---

## 9. Add Reusable Form Helpers If Useful

If many partials become repetitive, add a small helper partial/component for:

```text
text input
textarea
select
number input
date input
checkbox/toggle
array chips/simple repeated input
```

But do not over-engineer.

Keep Phase 4 practical.

---

## 10. Add Localisation Keys

Add EN/FR keys for all new specialist form labels.

Use existing namespace if appropriate:

```text
consultation_specialties.php
```

Suggested groups:

```text
forms.physiotherapy.*
forms.ophthalmology.*
forms.dental.*
actions.save_section
messages.section_saved
messages.section_deleted
messages.no_entry_yet
```

Do not leave hardcoded visible text unless the project allows it.

Run localisation parity/lock check if available.

---

## 11. Add Focused Tests

Create:

```text
tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
```

Suggested tests:

### Service upserts entry

* Saves structured entry for a consultation/profile/section.
* Updates existing entry instead of creating duplicate.
* Sets created_by and updated_by.

### Entry validation rejects unknown section

* Cannot save a section not present in active specialty profile.

### Entry validation rejects inactive profile

* Cannot save to inactive profile.

### Physiotherapy pain assessment save

* Save `pain_score`, `pain_location`, etc.
* Assert JSON stored correctly.

### Ophthalmology visual acuity save

* Save right/left acuity values.
* Assert JSON stored correctly.

### Ophthalmology refraction validation

* Axis must be between 0 and 180.
* Numeric ranges respected.

### Dental tooth chart save

* Save tooth number/surface/condition.
* Assert JSON stored correctly.

### Consent section save

* Save consent required/obtained flags.
* Assert JSON booleans stored correctly.

### Workspace loads saved specialist entries

* Save an entry.
* Open consultation workspace.
* Assert saved value appears in the correct specialist form.

### General consultation unaffected

* General medicine workspace still renders existing core sections.
* Specialist form partials do not appear in general medicine.

---

## 12. Optional Browser Smoke Test

If Playwright consultation workspace smoke has a light fixture already, add or extend one smoke test for:

```text
physiotherapy pain assessment save
ophthalmology visual acuity save
dental tooth chart save
```

Only do this if the existing fixture is already stable.

Do not create a massive Playwright suite in this phase.

---

## 13. Minimal Checks to Run

Run:

```bash
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localisation keys were added and the project has a localisation parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 14. Phase Report

Create:

```text
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_4_STRUCTURED_FORMS_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_4_STRUCTURED_FORMS_REPORT.md
```

The report must include:

```text
# Consultation Specialist Extension — Phase 4 Structured Forms Report

## Summary
Explain what was implemented.

## Existing Save Pattern Findings
Document the consultation save/routes/Ajax/payload patterns discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Specialist Entry Storage Design
Explain how consultation_specialty_entries is used.

## Structured Forms Added
List forms by specialty:
- Physiotherapy
- Ophthalmology
- Dental

## Validation Rules
Summarize section-aware validation.

## UI Changes
Explain how generic specialist sections were replaced with structured forms.

## Backward Compatibility
Confirm general consultation behavior is unchanged.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 5 specialty favorites/smart defaults
- Phase 6 order sets
- Phase 7 completion readiness
- Phase 8 summary builder
```

---

# Acceptance Criteria

Phase 4 is complete only when:

* `ConsultationSpecialtyEntryService` exists.
* Specialist entry routes/controller exist and follow existing consultation save conventions.
* Structured specialist entries save into `consultation_specialty_entries.entry`.
* Entries are keyed by consultation, active specialty profile, and section key.
* Saved entries reload into the consultation workspace.
* Physiotherapy structured forms exist and save.
* Ophthalmology structured forms exist and save.
* Dental structured forms exist and save.
* Unknown/future sections still fall back to the generic shell.
* General consultation behavior remains unchanged.
* Section-aware validation prevents unsafe/unknown section saves.
* Focused entry tests pass.
* Foundation, resolver, and layout tests still pass.
* View cache/build check passes.
* Phase 4 report is created.

Stop after Phase 4. Do not implement favorites, order sets, completion readiness, summary builder, or billing mapping yet.
