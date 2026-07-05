You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms
* Phase 5: Specialty Favorites and Smart Defaults
* Phase 6: Specialty Order Sets

Phase 6 added preview-first specialty order sets with safe apply behavior, profile scoping, task creation, structured-entry patches, application audit records, and conservative catalogue suggestions.

Now implement:

# Consultation Specialist Extension — Phase 7: Specialty Completion Readiness

## Phase 7 Goal

Make the consultation completion/readiness system specialty-aware.

The current consultation workflow already has completion/readiness behavior. Phase 7 must extend that system so the required clinical checks change depending on the active specialty profile.

Examples:

```text id="r8w6nx"
General Medicine:
- Complaint recorded
- Examination recorded
- Diagnosis recorded
- Plan / summary present

Physiotherapy:
- Presenting problem recorded
- Pain assessment recorded
- Physical assessment recorded
- Treatment plan recorded
- Session frequency or number of sessions recorded

Ophthalmology:
- Eye complaint recorded
- Visual acuity recorded
- Eye examination recorded
- Diagnosis recorded
- Follow-up / plan recorded

Dental:
- Dental complaint recorded
- Tooth chart or oral examination recorded
- Dental diagnosis recorded
- Procedure plan or clinical plan recorded
- Consent obtained if consent is required
```

The goal is not just blocking completion. The doctor should see exactly what is complete, missing, optional, warning-only, or blocking.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate completion logic for separate Physio/Eye/Dental modules.

Do not remove the existing general consultation readiness behavior.

Do not make general consultation stricter than it currently is unless existing behavior already requires it.

Do not implement specialty summary builder yet. That comes in Phase 8.

Do not implement doctor personal workspace yet. That comes later.

Do not implement billing/service mapping yet. That comes later.

Do not build the admin configuration UI yet.

Do not run the full test suite yet.

Only run focused checks for this phase.

If specialty readiness fails or cannot resolve, fall back safely to the existing general readiness behavior.

---

# Current Context

Phase 3 introduced specialty layout sections and required badges but did not enforce them.

Phase 4 introduced structured specialist forms stored in:

```text id="af1v02"
consultation_specialty_entries
```

Phase 5 added specialty favorites and smart defaults.

Phase 6 added order sets that can patch specialty entries and create safe tasks.

Now readiness should read both:

```text id="n4csh8"
existing core consultation records
specialty structured entries
```

and produce a single readiness result for the active consultation.

---

# Required Deliverables

## 1. Inspect Existing Completion Readiness

Before coding, inspect the current consultation completion/readiness implementation.

Identify:

```text id="s7j8fo"
existing readiness service(s)
route completion logic
visit completion logic
complete-and-open-next logic
readiness card/partial/view
blocking reasons
warning reasons
prescription safety integration
diagnosis/prescription/investigation/task requirements
existing tests
existing translation keys
existing Ajax/refresh behavior
```

Do not guess. Extend existing services where appropriate.

Document findings in the phase report.

---

## 2. Add Specialty Readiness Rule Registry

Create:

```text id="0xpl8p"
app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessRuleRegistry.php
```

Purpose:

Define readiness rules per specialty profile.

The registry should return a normalized rule set for a profile code.

Example method:

```php id="d6j6wm"
public function rulesForProfile(ConsultationSpecialtyProfile $profile): array;
```

Each rule should include:

```php id="rsu4bl"
[
    'key' => 'visual_acuity_recorded',
    'label' => __('consultation_specialties.readiness.visual_acuity_recorded'),
    'section_key' => 'visual_acuity',
    'source' => 'specialty_entry',
    'severity' => 'blocking', // blocking | warning | optional
    'fields' => ['right_eye_unaided', 'left_eye_unaided', 'right_eye_corrected', 'left_eye_corrected'],
    'mode' => 'any', // any | all | custom
    'message' => __('consultation_specialties.readiness.visual_acuity_missing'),
]
```

Supported source types:

```text id="4iaz5y"
core_complaint
core_hopc
core_examination
core_diagnosis
core_prescription
core_investigation
core_procedure
core_task
core_summary
specialty_entry
specialty_entry_field
specialty_entry_any
custom
```

Keep this registry code/config-based for now. Do not add an admin UI in this phase.

---

## 3. Add Specialty Readiness Result DTO

Create:

```text id="7v1sve"
app/Data/Consultation/Specialty/ConsultationSpecialtyReadinessResult.php
```

Or follow the project’s DTO convention.

It should contain:

```php id="lwmkus"
profile
status
score
blockingItems
warningItems
optionalItems
completedItems
items
canComplete
isFallback
summary
```

Suggested statuses:

```text id="066v5j"
ready
needs_attention
blocked
fallback
```

Each item should include:

```php id="0io3w0"
[
    'key' => ...,
    'label' => ...,
    'section_key' => ...,
    'severity' => ...,
    'status' => 'complete|missing|warning|optional',
    'message' => ...,
    'anchor' => ...,
    'source' => ...,
    'metadata' => ...,
]
```

Provide:

```php id="j1xo1h"
toArray(): array
```

Use a shape safe for Blade/page config JSON.

---

## 4. Add Specialty Readiness Service

Create:

```text id="vmf8u2"
app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php
```

Responsibilities:

```php id="3lopvr"
evaluate(
    $consultation,
    ResolvedConsultationSpecialty|array $specialtyContext,
    array $workspacePayload = []
): ConsultationSpecialtyReadinessResult;
```

It must:

* Resolve active specialty profile.
* Load visible specialty sections.
* Load existing specialty entries for the active profile.
* Inspect existing core consultation data.
* Evaluate the active profile’s readiness rules.
* Return normalized readiness result.
* Fall back to existing general readiness if profile context is missing or invalid.
* Never crash the consultation page.

---

## 5. Readiness Rules to Implement

## General Medicine

General medicine should preserve existing readiness logic.

If the project already has a general readiness service, use it as the source of truth.

Do not duplicate or weaken existing readiness checks.

The specialty readiness result for general medicine can wrap existing readiness output into the new normalized format.

Minimum if no existing structured output is available:

```text id="5sv70s"
Complaint recorded
Examination recorded
Diagnosis recorded
Plan/summary recorded
```

But only use this minimum if it matches the existing behavior.

---

## Physiotherapy Rules

Blocking rules:

```text id="sv8et4"
presenting_problem_recorded
pain_assessment_recorded
physical_assessment_recorded
treatment_plan_recorded
```

Recommended field logic:

### presenting_problem_recorded

Section:

```text id="6t1syu"
presenting_problem
```

Complete if any of:

```text id="ayraho"
problem_description
affected_area
referral_reason
mechanism_of_injury
```

### pain_assessment_recorded

Section:

```text id="ii8l5b"
pain_assessment
```

Complete if any of:

```text id="9uoe25"
pain_score
pain_location
pain_character
```

### physical_assessment_recorded

Section:

```text id="cf1c5p"
physical_assessment
```

Complete if any of:

```text id="mviwdk"
range_of_motion
muscle_strength
posture
gait
balance
assessment_notes
```

### treatment_plan_recorded

Section:

```text id="wv6yoz"
treatment_plan
```

Complete if any of:

```text id="7pqrta"
treatment_goals
modalities
session_frequency
number_of_sessions
expected_duration
```

Warning rules:

```text id="437kev"
session_schedule_missing
home_exercise_plan_missing
progress_notes_missing for follow-up visits if detectable
```

### session_schedule_missing

Warn if treatment plan exists but both are empty:

```text id="wvy8uv"
session_frequency
number_of_sessions
```

---

## Ophthalmology Rules

Blocking rules:

```text id="3qmdko"
eye_complaint_recorded
visual_acuity_recorded
eye_examination_recorded
diagnosis_recorded
```

### eye_complaint_recorded

Use core complaint if `eye_complaint` aliases to the complaints section.

Complete if the consultation has at least one complaint or an eye complaint entry if present.

### visual_acuity_recorded

Section:

```text id="qz0wx4"
visual_acuity
```

Complete if any of:

```text id="8g5vl0"
right_eye_unaided
left_eye_unaided
right_eye_corrected
left_eye_corrected
right_eye_pinhole
left_eye_pinhole
```

### eye_examination_recorded

Section:

```text id="pv6x2d"
eye_examination
```

Complete if any of:

```text id="rxnnw6"
lids
conjunctiva
cornea
anterior_chamber
pupil
lens
fundus
retina
optic_disc
examination_notes
```

### diagnosis_recorded

Use existing core diagnosis records.

Warning rules:

```text id="85lvh0"
iop_missing
follow_up_missing
refraction_missing if diagnosis/favorite suggests refractive error where detectable
```

Keep conditional warnings simple and safe in this phase.

---

## Dental Rules

Blocking rules:

```text id="t1s7az"
dental_complaint_recorded
oral_or_tooth_exam_recorded
dental_diagnosis_recorded
procedure_or_plan_recorded
```

### dental_complaint_recorded

Use core complaint if `dental_complaint` aliases to the complaints section.

Complete if consultation has at least one complaint or relevant dental complaint entry if present.

### oral_or_tooth_exam_recorded

Complete if either `tooth_chart` or `oral_examination` has meaningful data.

Tooth chart fields:

```text id="fb6fug"
tooth_number
condition
mobility
percussion
notes
```

Oral exam fields:

```text id="xxgq25"
oral_hygiene
gingiva
mucosa
occlusion
swelling
bleeding
examination_notes
```

### dental_diagnosis_recorded

Complete if either:

```text id="b1l2fn"
specialty entry dental_diagnosis.diagnosis_text exists
or core diagnosis exists
```

### procedure_or_plan_recorded

Complete if any of:

```text id="nl1e1z"
dental_procedures.procedure_planned
dental_procedures.procedure_performed
existing core procedure request exists
summary/plan exists where project supports it
```

Consent conditional blocking rule:

```text id="a6b9nk"
consent_obtained_if_required
```

If `consent.consent_required = true`, then `consent.consent_obtained` must be true before readiness is complete.

Warning rules:

```text id="q4qysq"
xray_missing_if_extraction_planned
follow_up_missing
```

For extraction warning, if `procedure_planned` or `procedure_performed` contains extraction and no `dental_xray.xray_requested` or `xray_findings`, show a warning.

---

## 6. Integrate With Existing Completion Readiness Card

Update the existing readiness card/section so it can display specialty readiness.

Rules:

* Preserve existing general readiness display.
* For specialist profiles, show a compact checklist grouped by:

  * Blocking
  * Warnings
  * Completed
  * Optional
* Show a clear status:

  * Ready
  * Needs attention
  * Blocked
* Each item should link/scroll to the relevant section anchor where possible.
* Do not make the readiness card noisy.
* Required badges from Phase 3 should align with blocking readiness where possible.

Suggested UI labels:

```text id="bbm59g"
Specialty readiness
Ready to complete
Needs attention
Blocked
Missing required specialist details
Warnings
Completed
```

---

## 7. Integrate With Completion Blocking

Where the existing consultation route/visit completion is blocked today, extend it safely:

* Existing general blocks remain.
* Prescription safety blocks remain.
* Specialty blocking items should block completion for specialist profiles.
* Warning-only items should not block completion.
* Optional items should not block completion.
* If specialty readiness cannot evaluate, fall back to existing general completion behavior.

Important:

Do not create double-blocking messages. Merge duplicate reasons.

---

## 8. Add Readiness Refresh After Specialist Saves

After saving a specialty structured form section, the readiness card should refresh or the page should return updated readiness data.

Use existing section refresh conventions:

```text id="69fvjl"
data-refresh-section
Ajax response sections
page reload fallback
```

At minimum, after save/reload, readiness state must reflect the saved specialist entry.

Do not break existing Ajax behavior.

---

## 9. Localisation

Add EN/FR keys for all new readiness labels/messages.

Suggested namespace:

```text id="x5xdd9"
consultation_specialties.php
```

Suggested keys:

```text id="z60ocq"
readiness.title
readiness.ready
readiness.needs_attention
readiness.blocked
readiness.blocking_items
readiness.warning_items
readiness.completed_items
readiness.optional_items
readiness.missing_required_details
readiness.specialty_ready
readiness.specialty_blocked
readiness.specialty_warnings
readiness.presenting_problem_recorded
readiness.presenting_problem_missing
readiness.pain_assessment_recorded
readiness.pain_assessment_missing
readiness.physical_assessment_recorded
readiness.physical_assessment_missing
readiness.treatment_plan_recorded
readiness.treatment_plan_missing
readiness.session_schedule_missing
readiness.home_exercise_plan_missing
readiness.eye_complaint_recorded
readiness.eye_complaint_missing
readiness.visual_acuity_recorded
readiness.visual_acuity_missing
readiness.eye_examination_recorded
readiness.eye_examination_missing
readiness.iop_missing
readiness.follow_up_missing
readiness.dental_complaint_recorded
readiness.dental_complaint_missing
readiness.oral_or_tooth_exam_recorded
readiness.oral_or_tooth_exam_missing
readiness.dental_diagnosis_recorded
readiness.dental_diagnosis_missing
readiness.procedure_or_plan_recorded
readiness.procedure_or_plan_missing
readiness.consent_required_missing
readiness.xray_missing_if_extraction_planned
```

No hardcoded visible strings.

---

## 10. Add Focused Tests

Create:

```text id="4e3o0z"
tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php
```

Suggested tests:

### General readiness fallback

* General medicine uses or wraps existing readiness logic.
* General consultation behavior remains stable.

### Physio blocked when missing required entries

* Empty physio consultation returns blocking items for presenting problem, pain assessment, physical assessment, and treatment plan.

### Physio ready after required entries

* Save required physio entries.
* Readiness becomes ready or no longer blocked.

### Physio session schedule warning

* Treatment plan exists without session frequency/number of sessions.
* Warning appears but does not block if other required items are complete.

### Ophthalmology blocked when visual acuity missing

* Eye consultation with complaint/diagnosis but no visual acuity remains blocked.

### Ophthalmology ready after visual acuity and eye exam

* Save visual acuity and eye examination.
* Diagnosis exists.
* Readiness no longer blocks.

### Ophthalmology IOP warning

* IOP missing returns warning, not blocking.

### Dental blocked when oral/tooth exam missing

* Dental consultation without tooth chart/oral exam remains blocked.

### Dental diagnosis can come from specialty entry

* Dental diagnosis entry satisfies dental diagnosis readiness.

### Dental consent conditional block

* If consent_required = true and consent_obtained is false, readiness blocks.
* If consent_obtained = true, block clears.

### Dental X-ray extraction warning

* Extraction planned without X-ray creates warning only.

### Wrong profile isolation

* Physio entries do not satisfy dental readiness.
* Dental entries do not satisfy eye readiness.

### Order set patches affect readiness

* Apply dental extraction prep order set.
* Consent required appears in readiness logic.
* Apply physio low back pain order set.
* Treatment plan patch contributes to readiness but does not falsely complete unrelated required items.

### Workspace payload includes specialtyReadiness

* Consultation workspace has `specialtyReadiness` payload/config.

### Completion route blocks specialist missing requirements

* Existing route/visit completion action is blocked when specialist readiness has blocking items.
* Warning-only readiness does not block.

### Localisation keys exist

* EN/FR keys exist for new readiness messages.

---

## 11. Optional Browser Smoke Test

If the existing Playwright consultation fixture is stable, add a light smoke test:

```text id="yv7p8u"
Open specialist consultation
Observe readiness blocked
Save required specialty form section
Observe readiness update
```

Only do this if existing fixture setup is already stable.

Do not create a heavy browser suite in this phase.

---

## 12. Minimal Checks to Run

Run:

```bash id="vu6uo8"
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localisation keys were added and the project has a localisation parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 13. Phase Report

Create:

```text id="6r6q2k"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_7_COMPLETION_READINESS_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="6n7w10"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_7_COMPLETION_READINESS_REPORT.md
```

The report must include:

```text id="7eyyjv"
# Consultation Specialist Extension — Phase 7 Completion Readiness Report

## Summary
Explain what was implemented.

## Existing Readiness Findings
Document current completion readiness services, blockers, route/visit completion behavior, and UI card structure discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Specialty Readiness Design
Explain rule registry, result DTO, readiness service, source types, blocking/warning/optional statuses, and fallback behavior.

## Specialty Rules Implemented
List readiness rules for:
- General Medicine
- Physiotherapy
- Ophthalmology
- Dental

## Completion Blocking Integration
Explain how specialty blocking items affect route/visit completion and how warning-only items behave.

## UI Changes
Explain readiness checklist/card changes and anchors.

## Backward Compatibility
Confirm general consultation readiness and existing prescription safety blocks remain stable.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 8 specialty summary builder
- Phase 9 doctor personal workspace
- Later admin configuration UI
- Later billing/service mapping
```

---

# Acceptance Criteria

Phase 7 is complete only when:

* `ConsultationSpecialtyReadinessRuleRegistry` exists.
* `ConsultationSpecialtyReadinessResult` exists.
* `ConsultationSpecialtyReadinessService` exists.
* Specialty readiness evaluates core consultation data and specialty entries.
* General medicine preserves existing readiness behavior.
* Physiotherapy readiness blocks only on defined required specialist gaps.
* Ophthalmology readiness blocks only on defined required specialist gaps.
* Dental readiness blocks only on defined required specialist gaps, including consent when required.
* Warning-only items do not block completion.
* Specialist blocking items block existing completion actions safely.
* Existing prescription safety blocks remain active.
* Readiness result is passed to the workspace as `specialtyReadiness`.
* Readiness UI shows blocking/warning/completed status clearly.
* Readiness refreshes or updates after specialty entry saves.
* Focused readiness tests pass.
* Phase 1-6 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 7 report is created.

Stop after Phase 7. Do not implement summary builder, personal workspace, admin UI, or billing mapping yet.
