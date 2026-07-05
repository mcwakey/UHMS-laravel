You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms
* Phase 5: Specialty Favorites and Smart Defaults
* Phase 6: Specialty Order Sets
* Phase 7: Specialty Completion Readiness

Phase 7 added specialty-aware completion readiness using the existing completion system. General medicine continues to use the existing readiness logic, while physiotherapy, ophthalmology, and dental now add specialist blocking/warning items from core consultation records and `consultation_specialty_entries`.

Now implement:

# Consultation Specialist Extension — Phase 8: Specialty Summary Builder

## Phase 8 Goal

Create a specialty-aware consultation summary builder that generates clean clinical summaries based on the active specialty profile.

The summary builder must use:

```text id="o9g16l"
existing core consultation data
specialty structured entries
diagnoses
investigations
procedures
prescriptions
tasks/follow-ups
completion readiness result
order-set applied patches where relevant
```

The goal is to help doctors produce a good final summary faster without overwriting their notes or forcing automatic text into the medical record.

Examples:

```text id="5nv2xg"
Physiotherapy summary:
- Presenting problem
- Pain assessment
- Functional limitation
- Physical findings
- Treatment plan
- Therapy session details
- Home exercise plan
- Progress and next review

Ophthalmology summary:
- Eye complaint
- Visual acuity
- Refraction
- IOP
- Eye examination
- Diagnosis
- Treatment / prescription
- Follow-up warning signs

Dental summary:
- Dental complaint
- Tooth/tooth chart findings
- Oral examination
- Dental diagnosis
- X-ray findings
- Procedure planned/performed
- Consent status
- Post-procedure instructions
```

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate Physio, Eye, or Dental consultation modules.

Do not overwrite doctor notes.

Do not merge notes and summary. Notes and summary must remain decoupled.

Do not auto-save generated summaries into the final medical record without doctor action.

Do not remove or weaken existing general consultation summary behavior.

Do not implement doctor personal workspace yet. That comes later.

Do not implement admin configuration UI yet.

Do not implement billing/service mapping yet.

Do not run the full test suite yet.

Only run focused checks for this phase.

If specialty summary generation fails, the existing summary/notes workflow must continue working.

---

# Current Context

Phase 7 report confirms:

* Existing readiness is implemented in `ConsultationCompletionReadinessService`.
* General readiness is config-driven through `config/consultation.php`.
* Route completion, visit completion, and complete-and-open-next call the existing readiness assertion.
* The readiness card is `resources/views/consultations/partials/right-panel.blade.php`.
* Specialty readiness now evaluates core records and `consultation_specialty_entries`.
* The workspace passes `specialtyReadiness` to Blade and page config JSON.
* Prescription safety remains separate from completion readiness.

Phase 8 should use those same sources to generate specialty summaries.

---

# Required Deliverables

## 1. Inspect Existing Notes and Summary Flow

Before coding, inspect how the current consultation workspace handles:

```text id="lkfgmu"
notes
summary
final note
consultation completion summary
visit summary
disposition/plan
print/export summary if any
medical record entry logs
activity logs
Ajax save behavior
Blade textarea/form structure
```

Identify:

```text id="0y943x"
models/tables used
controller actions
routes
request fields
validation rules
where summary is stored
where notes are stored
how notes and summary are displayed
how completion reads plan/summary
how audit logs are written
```

Important:

* Notes and summary must remain decoupled.
* If the current implementation still mixes notes and summary anywhere, do not do a risky rewrite in this phase. Add the builder safely around the current structure and document follow-up.

Document findings in the phase report.

---

## 2. Add Summary Source Collector

Create:

```text id="ukd8i7"
app/Services/Consultation/Specialty/ConsultationSpecialtySummarySourceCollector.php
```

Responsibilities:

```php id="0lg40x"
collect(
    $consultation,
    ResolvedConsultationSpecialty|array $specialtyContext,
    array $workspacePayload = []
): array;
```

It should collect a normalized source array:

```php id="268jzc"
[
    'profile' => [...],
    'core' => [
        'complaints' => [...],
        'hopc' => [...],
        'examination' => [...],
        'diagnoses' => [...],
        'investigations' => [...],
        'procedures' => [...],
        'prescriptions' => [...],
        'tasks' => [...],
        'notes' => ...,
        'summary' => ...,
        'plan' => ...,
        'disposition' => ...,
    ],
    'specialty_entries' => [
        'visual_acuity' => [...],
        'pain_assessment' => [...],
        ...
    ],
    'readiness' => [...],
    'order_set_applications' => [...],
]
```

Rules:

* Follow existing model relationships.
* Avoid N+1 queries where practical.
* Do not expose sensitive user data unnecessarily.
* Return empty arrays safely when data does not exist.
* Keep collector read-only.

---

## 3. Add Summary Template Registry

Create:

```text id="1504gr"
app/Services/Consultation/Specialty/ConsultationSpecialtySummaryTemplateRegistry.php
```

Purpose:

Define specialty-specific summary sections and formatting rules.

Method:

```php id="cmm6a4"
public function templateForProfile(ConsultationSpecialtyProfile $profile): array;
```

Template structure:

```php id="jossxi"
[
    'profile_code' => 'ophthalmology',
    'title' => 'Ophthalmology Consultation Summary',
    'sections' => [
        [
            'key' => 'eye_complaint',
            'label' => __('consultation_specialties.summary.eye_complaint'),
            'source' => 'core.complaints',
            'formatter' => 'complaints',
            'include_if_empty' => false,
        ],
    ],
]
```

Do not add admin UI yet. Code registry is enough for this phase.

---

## 4. Add Summary Result DTO

Create:

```text id="j2rl4v"
app/Data/Consultation/Specialty/ConsultationSpecialtySummaryResult.php
```

Suggested fields:

```php id="9dhml0"
profile
title
status
sections
plainText
html
warnings
generatedAt
isFallback
sourceCompleteness
```

Each summary section should include:

```php id="wgarxb"
[
    'key' => ...,
    'label' => ...,
    'content' => ...,
    'is_empty' => true/false,
    'source' => ...,
    'warnings' => [...],
]
```

Methods:

```php id="uj3de8"
toArray(): array
plainText(): string
html(): string
```

Use Blade/JSON-safe output.

Do not store generated summary automatically unless the doctor explicitly saves/applies it.

---

## 5. Add Summary Builder Service

Create:

```text id="6p5wrk"
app/Services/Consultation/Specialty/ConsultationSpecialtySummaryBuilder.php
```

Responsibilities:

```php id="qq0gjm"
build(
    $consultation,
    ResolvedConsultationSpecialty|array $specialtyContext,
    array $workspacePayload = [],
    array $options = []
): ConsultationSpecialtySummaryResult;
```

Rules:

* Resolve active profile.
* Use source collector.
* Use template registry.
* Format each section cleanly.
* Skip empty sections unless template says include.
* Include warning when important readiness items are still missing.
* Do not claim information that is not present.
* Do not invent clinical findings.
* Do not overwrite clinician text.
* Fall back to general summary behavior if profile is invalid or unsupported.
* Keep generated summary concise but useful.

---

# Specialty Summary Templates to Implement

## General Medicine

Preserve existing summary behavior as much as possible.

If there is already a generated/general summary source, wrap or reuse it.

Suggested sections only if aligned with current behavior:

```text id="f6sx68"
Chief complaint
History
Examination
Diagnosis
Investigations
Treatment / Prescription
Plan / Follow-up
```

Do not make general consultation noisier than it currently is.

---

## Physiotherapy Summary

Sections:

```text id="xukbx4"
Presenting problem
Pain assessment
Functional limitation
Physical assessment
Treatment plan
Therapy session
Home exercise plan
Progress / next review
Tasks / follow-up
Readiness warnings
```

Source mapping:

```text id="1gt60i"
presenting_problem       -> specialty_entries.presenting_problem
pain_assessment          -> specialty_entries.pain_assessment
functional_limitation    -> specialty_entries.functional_limitation
physical_assessment      -> specialty_entries.physical_assessment
treatment_plan           -> specialty_entries.treatment_plan
therapy_session          -> specialty_entries.therapy_session
home_exercise_plan       -> specialty_entries.home_exercise_plan
progress_notes           -> specialty_entries.progress_notes
tasks                    -> core.tasks
readiness warnings       -> readiness.warningItems
```

Example output style:

```text id="nh1c6j"
Presenting problem: Lower back pain after lifting heavy object.
Pain assessment: Pain score 7/10, located in the lower back, worse with bending.
Physical assessment: Reduced lumbar range of motion; gait stable.
Treatment plan: Therapeutic exercises three times weekly for 6 sessions.
Home exercise plan: Continue stretching and strengthening exercises as instructed.
Follow-up: Review pain score at next session.
```

---

## Ophthalmology Summary

Sections:

```text id="cl1n32"
Eye complaint
Visual acuity
Refraction
Intraocular pressure
Eye examination
Diagnosis
Investigations
Treatment / prescription
Follow-up and warning signs
Readiness warnings
```

Source mapping:

```text id="pn2tai"
eye_complaint            -> core.complaints
visual_acuity            -> specialty_entries.visual_acuity
refraction               -> specialty_entries.refraction
iop                      -> specialty_entries.iop
eye_examination          -> specialty_entries.eye_examination
diagnosis                -> core.diagnoses
investigations           -> core.investigations
prescriptions            -> core.prescriptions
follow_up                -> specialty_entries.follow_up
readiness warnings       -> readiness.warningItems
```

Example output style:

```text id="zu0s4l"
Eye complaint: Redness and discharge.
Visual acuity: Right eye 6/9, left eye 6/6.
IOP: Right 16 mmHg, left 15 mmHg by tonometry.
Eye examination: Conjunctival injection noted; cornea clear.
Diagnosis: Conjunctivitis.
Treatment: Antibiotic eye drops QDS.
Follow-up: Review in 3 days. Return immediately if vision worsens or severe pain develops.
```

---

## Dental Summary

Sections:

```text id="mxtzdo"
Dental complaint
Tooth chart
Oral examination
Dental diagnosis
X-ray / investigation
Procedure plan / procedure performed
Consent
Prescription / medication
Post-procedure instructions
Readiness warnings
```

Source mapping:

```text id="zftmy6"
dental_complaint         -> core.complaints
tooth_chart              -> specialty_entries.tooth_chart
oral_examination         -> specialty_entries.oral_examination
dental_diagnosis         -> specialty_entries.dental_diagnosis + core.diagnoses
dental_xray              -> specialty_entries.dental_xray + core.investigations
dental_procedures        -> specialty_entries.dental_procedures + core.procedures
consent                  -> specialty_entries.consent
prescriptions            -> core.prescriptions
follow_up/instructions   -> core.tasks or available instruction fields
readiness warnings       -> readiness.warningItems
```

Example output style:

```text id="nk966u"
Dental complaint: Tooth pain.
Tooth chart: Tooth 36, caries noted.
Oral examination: Gingival swelling present.
Diagnosis: Dental caries with suspected pulpitis.
Procedure plan: Extraction planned.
Consent: Dental extraction consent obtained.
Medication: Oral analgesic prescribed.
Instructions: Do not rinse mouth vigorously for 24 hours after extraction. Return if bleeding persists.
```

---

## 6. Add Formatter Helpers

Inside the summary builder or a dedicated formatter class, add small formatters for:

```text id="vveyc3"
core complaints
diagnoses
investigations
procedures
prescriptions
tasks/follow-ups
specialty entry key-value fields
boolean fields
dates
arrays
readiness warnings
```

Rules:

* Human-friendly labels.
* Skip empty/null values.
* Format booleans clearly: Yes / No.
* Format arrays as comma-separated or bullet list.
* Do not output raw JSON.
* Avoid duplicated content.
* Keep summaries concise.

Suggested file if separate:

```text id="0zw2kh"
app/Services/Consultation/Specialty/ConsultationSpecialtySummaryFormatter.php
```

---

## 7. Add Summary Preview Endpoint

Create controller or extend existing specialist controller:

```text id="6k43m5"
app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtySummaryController.php
```

Required action:

```php id="8zzyy6"
preview(Request $request, Consultation $consultation)
```

Optional action if safe:

```php id="w2fk3h"
apply(Request $request, Consultation $consultation)
```

## Preview

* Builds the summary.
* Returns JSON for Ajax requests.
* Can also redirect back with flash if normal post is used.
* Must use existing consultation mutation/read guard.
* Must ensure user can access the consultation.

## Apply

Only implement apply if there is a safe existing summary/final-note save path.

Apply behavior:

* Doctor explicitly clicks “Use this summary”.
* Summary text is inserted into the summary/final note textarea or saved through existing summary save endpoint.
* Do not overwrite existing summary unless doctor confirms.
* If existing summary already has content, append or ask/require explicit replace option.
* Log activity/audit using existing convention.

If safe apply is too risky, implement preview only and let UI insert text client-side into the textarea with doctor still needing to save. Document this decision.

---

## 8. Add Routes

Add routes using existing consultation naming/middleware conventions.

Suggested names:

```text id="4j6u0e"
doctor.consultations.specialty-summary.preview
doctor.consultations.specialty-summary.apply
```

Use the same auth/permission/middleware pattern as existing consultation clinical read/mutation routes.

---

## 9. Add Workspace Payload

Update the consultation workspace controller/provider to pass:

```text id="q6pqv6"
specialtySummaryPreview
```

or a lightweight metadata payload:

```php id="zzb105"
[
    'available' => true,
    'profile_code' => ...,
    'preview_url' => ...,
    'apply_url' => ...,
]
```

Do not generate heavy summary payload on every page load unless cheap.

Prefer lazy preview generation through endpoint.

---

## 10. UI: Specialty Summary Builder Panel

Add a compact summary builder control near the existing notes/summary area.

Rules:

* Must not replace existing notes/summary UI.
* Must clearly say it is generated from recorded clinical data.
* Must allow doctor to preview before using.
* Must not auto-save.
* Must support “Insert into summary” or “Use this summary” only after doctor action.
* Must not overwrite existing text silently.
* If current summary textarea has content, confirm before replacing or append instead.
* Keep general medicine behavior stable.

Suggested UI:

```text id="5g4tcf"
[Generate specialty summary]

Preview modal/drawer:
- Generated summary
- Missing data warnings
- Copy / Insert into summary / Close
```

If modal infrastructure exists, use it. Otherwise use an inline reveal panel.

---

## 11. Readiness Integration

The generated summary should show warnings if key readiness items are missing.

Examples:

```text id="s2v60s"
This summary may be incomplete because visual acuity is missing.
This summary may be incomplete because consent is required but not obtained.
This summary may be incomplete because treatment plan is missing.
```

Do not block summary generation because readiness is incomplete.

---

## 12. Localisation

Add EN/FR keys for summary builder labels/messages.

Suggested keys:

```text id="pbu91a"
summary_builder.title
summary_builder.generate
summary_builder.preview
summary_builder.insert
summary_builder.copy
summary_builder.close
summary_builder.generated_from_recorded_data
summary_builder.may_be_incomplete
summary_builder.no_data_available
summary_builder.inserted
summary_builder.not_saved_yet
summary_builder.replace_existing_confirm
summary_builder.append_to_existing
summary_builder.sections.presenting_problem
summary_builder.sections.pain_assessment
summary_builder.sections.functional_limitation
summary_builder.sections.physical_assessment
summary_builder.sections.treatment_plan
summary_builder.sections.therapy_session
summary_builder.sections.home_exercise_plan
summary_builder.sections.progress
summary_builder.sections.eye_complaint
summary_builder.sections.visual_acuity
summary_builder.sections.refraction
summary_builder.sections.iop
summary_builder.sections.eye_examination
summary_builder.sections.diagnosis
summary_builder.sections.investigations
summary_builder.sections.treatment_prescription
summary_builder.sections.follow_up
summary_builder.sections.dental_complaint
summary_builder.sections.tooth_chart
summary_builder.sections.oral_examination
summary_builder.sections.dental_diagnosis
summary_builder.sections.dental_xray
summary_builder.sections.dental_procedures
summary_builder.sections.consent
summary_builder.sections.post_procedure_instructions
summary_builder.sections.readiness_warnings
```

No hardcoded visible strings.

---

## 13. Add Focused Tests

Create:

```text id="2qasg6"
tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php
```

Suggested tests:

### General summary fallback

* General medicine summary uses existing/general behavior or safe fallback.
* General consultation remains stable.

### Physiotherapy summary includes structured fields

* Save presenting problem, pain assessment, physical assessment, treatment plan.
* Build summary.
* Assert summary contains pain score, affected area, treatment plan, session frequency.

### Ophthalmology summary includes eye fields

* Save visual acuity, IOP, eye examination, follow-up.
* Add diagnosis/prescription if fixture supports it.
* Build summary.
* Assert summary contains acuity, IOP, eye exam, follow-up warning signs.

### Dental summary includes dental fields

* Save tooth chart, oral exam, dental diagnosis, dental procedure, consent.
* Build summary.
* Assert summary contains tooth number, diagnosis, procedure, consent status.

### Empty fields are skipped

* Empty/null fields should not produce noisy labels.

### Booleans and arrays are formatted

* Consent true/false formats clearly.
* Modalities/exercises arrays format cleanly.

### Readiness warnings included

* Missing required specialist fields appear as summary warnings.
* Summary still generates.

### Notes are not overwritten

* Existing notes remain unchanged after preview.
* Existing summary remains unchanged after preview.

### Preview endpoint returns JSON

* Endpoint returns title, plain text/html, sections, warnings.

### Insert/apply behavior

Only if apply is implemented:

* Apply requires explicit doctor action.
* Existing summary is not overwritten unless replace option is passed.
* Audit/activity log is created if project convention supports it.

If apply is preview-only/client-side, test that preview does not persist.

### Workspace metadata exists

* Workspace payload includes summary builder metadata/preview URL.

### Wrong profile isolation

* Physio entries do not appear in dental summary.
* Dental entries do not appear in ophthalmology summary.

### Localisation keys exist

* EN/FR summary keys exist.

---

## 14. Optional Browser Smoke Test

If the existing Playwright consultation fixture is stable, add one light smoke test:

```text id="oa8heo"
Open specialist consultation
Save one structured form section
Click Generate specialty summary
Confirm preview contains saved data
Insert into summary textarea
Confirm text appears but is not saved until doctor saves
```

Only do this if the existing fixture is stable.

Do not create a heavy browser suite in this phase.

---

## 15. Minimal Checks to Run

Run:

```bash id="miz5zh"
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localisation keys were added and the project has a localisation parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 16. Phase Report

Create:

```text id="b6h5zf"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_8_SUMMARY_BUILDER_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="swp298"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_8_SUMMARY_BUILDER_REPORT.md
```

The report must include:

```text id="zr5l9o"
# Consultation Specialist Extension — Phase 8 Summary Builder Report

## Summary
Explain what was implemented.

## Existing Notes/Summary Findings
Document current notes, summary, final note, completion plan/disposition, storage, routes, save behavior, and audit patterns discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Summary Builder Design
Explain source collector, template registry, result DTO, builder service, formatter behavior, and fallback behavior.

## Specialty Templates Implemented
List summary sections for:
- General Medicine
- Physiotherapy
- Ophthalmology
- Dental

## UI Changes
Explain preview panel/modal, insert behavior, and how notes/summary remain decoupled.

## Persistence Behavior
Explain whether preview-only or apply/save was implemented.
Confirm generated summary is not auto-saved.

## Readiness Integration
Explain how missing readiness items appear as summary warnings.

## Backward Compatibility
Confirm general consultation summary/notes behavior remains stable.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 9 doctor personal workspace
- Later admin configuration UI
- Later billing/service mapping
- Later print/export formatting if needed
```

---

# Acceptance Criteria

Phase 8 is complete only when:

* `ConsultationSpecialtySummarySourceCollector` exists.
* `ConsultationSpecialtySummaryTemplateRegistry` exists.
* `ConsultationSpecialtySummaryResult` exists.
* `ConsultationSpecialtySummaryBuilder` exists.
* Specialty summaries build from core consultation data and active-profile specialty entries.
* General medicine behavior remains stable.
* Physiotherapy summary includes physio structured fields.
* Ophthalmology summary includes eye structured fields.
* Dental summary includes dental structured fields.
* Missing readiness items appear as warnings but do not block summary generation.
* Preview endpoint exists.
* Workspace exposes summary builder metadata/control.
* Generated summary is not auto-saved.
* Notes and summary remain decoupled.
* Existing summary is not overwritten silently.
* Focused summary builder tests pass.
* Phase 1-7 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 8 report is created.

Stop after Phase 8. Do not implement doctor personal workspace, admin UI, or billing mapping yet.
