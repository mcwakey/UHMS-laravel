You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms
* Phase 5: Specialty Favorites and Smart Defaults

Phase 5 added `consultation_specialty_favorites`, specialty-aware suggestions, favorite-first frequency defaults, and safe workspace integration without breaking global ICD/service/procedure/drug search.

Now implement:

# Consultation Specialist Extension — Phase 6: Specialty Order Sets

## Phase 6 Goal

Add **specialty order sets**: reusable clinical bundles that help doctors apply common specialty workflows quickly.

An order set is a prepared bundle of actions/suggestions such as:

```text id="lvqlog"
Diagnosis suggestion
Investigation request
Procedure suggestion
Prescription suggestion
Task creation
Follow-up instruction
Specialty structured-form patch
Clinical note/instruction insertion
```

Examples:

```text id="u9nbxj"
Eye Clinic — Conjunctivitis Bundle
- Diagnosis suggestion: Conjunctivitis
- Drug suggestion: Antibiotic eye drops
- Frequency: Four times daily
- Follow-up instruction: Return immediately if vision worsens
- Task: Review in 3 days
```

```text id="a1jmhq"
Physiotherapy — Low Back Pain Rehab Bundle
- Structured entry patch: pain assessment / functional limitation / treatment plan hints
- Task: Perform therapy session
- Task: Review pain score
- Frequency: Three times weekly
- Follow-up instruction: Continue home exercises as instructed
```

```text id="zh15ge"
Dental — Extraction Preparation Bundle
- Investigation suggestion: Periapical X-ray
- Structured entry patch: consent required = true
- Procedure suggestion: Tooth extraction
- Drug suggestion: Oral analgesic
- Follow-up instruction: Do not rinse mouth vigorously for 24 hours after extraction
```

Order sets should make the consultation feel specialist-aware, but they must not become unsafe automation.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate Physio, Eye, or Dental consultation modules.

Do not silently create billable procedure/service records from label-only order set items.

Do not silently prescribe drugs unless the item is safely linked to an existing drug and the existing prescription workflow supports programmatic creation.

Do not bypass existing consultation mutation guards, permissions, route context checks, validation, audit logging, or author attribution.

Do not implement specialty completion readiness yet. That comes later.

Do not implement specialty summary builder yet. That comes later.

Do not implement billing/service mapping yet. That comes later.

Do not build the admin UI yet unless a very small read-only/index page is already required by project convention. Full admin configuration comes later.

Do not run the full test suite yet.

Only run focused checks for this phase.

Order sets must use **preview first, then apply**. The doctor should see what will be applied before it is saved.

If an order set item cannot be safely applied, it should appear as a suggestion/warning in the preview instead of breaking the action.

---

# Current Context

Phase 5 report confirms:

* `specialtyFavorites` reaches the Blade view and page config JSON.
* Diagnosis, task, and follow-up instruction favorites show as opt-in chips/suggestions.
* Investigation/procedure/drug favorites show as safe hints while preserving required catalogue selection.
* Prescription/task frequency dropdowns use favorite-first merged defaults.
* Global ICD/service/procedure/drug search remains available.
* Existing mutation forms use `data-ajax-form`, `data-consultation-form`, `data-refresh-section`, and route-context preservation.
* Core consultation saves use existing controller guards and conventions.

Phase 6 must build on that safely.

---

# Required Deliverables

## 1. Inspect Existing Mutation Services Again

Before coding, inspect actual code paths for creating or updating:

```text id="m0qdk2"
diagnoses
investigation requests
procedure requests
prescriptions
consultation tasks
follow-up instructions / clinical instructions
specialty entries
medical record entry logs
activity logs
```

Identify:

```text id="h3w8kp"
existing service/controller method to reuse
required route context
required fields
author fields
audit/logging behavior
JSON vs redirect response conventions
refresh section behavior
validation rules
permission/middleware rules
```

Do not guess. Use existing services where possible.

Document findings in the phase report.

---

## 2. Add Order Set Tables

Create migrations for:

```text id="z1hfo8"
consultation_specialty_order_sets
consultation_specialty_order_set_items
consultation_specialty_order_set_applications
consultation_specialty_order_set_application_items
```

## Table: consultation_specialty_order_sets

Fields:

```text id="q4a0tp"
id
consultation_specialty_profile_id foreign key cascade delete
code string
name string
description nullable text
category nullable string
icon nullable string
color nullable string
is_active boolean default true
sort_order unsigned integer default 0
metadata nullable json
created_at
updated_at
```

Constraints/indexes:

```text id="aim8po"
unique consultation_specialty_profile_id + code
index consultation_specialty_profile_id
index category
index is_active
index sort_order
```

## Table: consultation_specialty_order_set_items

Fields:

```text id="gu93j0"
id
consultation_specialty_order_set_id foreign key cascade delete
item_type string
label string
description nullable text
target_section nullable string
target_field nullable string
favoritable_type nullable string
favoritable_id nullable unsignedBigInteger
code nullable string
payload nullable json
apply_mode string default 'suggest'
is_required boolean default false
sort_order unsigned integer default 0
is_active boolean default true
metadata nullable json
created_at
updated_at
```

Suggested `item_type` values:

```text id="p2f7tj"
diagnosis
investigation
procedure
drug
prescription
frequency
task
follow_up_instruction
clinical_instruction
specialty_entry_patch
note
```

Suggested `apply_mode` values:

```text id="sykr6h"
suggest
insert_text
create_task
patch_specialty_entry
create_diagnosis_if_supported
create_investigation_if_linked
create_procedure_if_linked
create_prescription_if_linked
```

Important:

* `suggest` means show in preview only; doctor manually uses it.
* `insert_text` means insert into an existing textarea/client-side or append server-side to an instruction field only where safe.
* `create_task` can create consultation tasks if existing task workflow supports it.
* `patch_specialty_entry` can update `consultation_specialty_entries.entry`.
* Linked catalogue apply modes must only apply when linked model exists and existing workflow supports it.

## Table: consultation_specialty_order_set_applications

Purpose: audit what order set was applied to a consultation.

Fields:

```text id="zb9aji"
id
consultation_id foreign key
consultation_specialty_order_set_id nullable foreign key nullOnDelete
consultation_specialty_profile_id nullable foreign key nullOnDelete
applied_by foreign key users
status string default 'applied'
preview_payload nullable json
applied_payload nullable json
warnings nullable json
metadata nullable json
created_at
updated_at
```

Suggested statuses:

```text id="zz2fuh"
previewed
applied
partially_applied
failed
cancelled
```

## Table: consultation_specialty_order_set_application_items

Purpose: item-level audit.

Fields:

```text id="y64n7x"
id
consultation_specialty_order_set_application_id foreign key cascade delete
consultation_specialty_order_set_item_id nullable foreign key nullOnDelete
item_type string
label string
apply_mode string
status string
target_type nullable string
target_id nullable unsignedBigInteger
payload nullable json
message nullable text
warnings nullable json
created_at
updated_at
```

Suggested statuses:

```text id="31updo"
suggested
applied
skipped
failed
unsupported
```

Use the actual existing consultation table/model name for the consultation FK.

If the existing consultation FK pattern differs, adapt safely and document it.

---

## 3. Add Eloquent Models

Create:

```text id="6ladh9"
app/Models/ConsultationSpecialtyOrderSet.php
app/Models/ConsultationSpecialtyOrderSetItem.php
app/Models/ConsultationSpecialtyOrderSetApplication.php
app/Models/ConsultationSpecialtyOrderSetApplicationItem.php
```

Relationships:

### ConsultationSpecialtyOrderSet

```php id="fua65f"
profile()
items()
activeItems()
applications()
```

Scopes:

```php id="r8cy4x"
active()
ordered()
forProfile(ConsultationSpecialtyProfile $profile)
ofCategory(?string $category)
```

### ConsultationSpecialtyOrderSetItem

```php id="jvbq9a"
orderSet()
favoritable()
```

Scopes:

```php id="n1uiwh"
active()
ordered()
ofType(string $type)
```

### ConsultationSpecialtyOrderSetApplication

```php id="4zrn0e"
consultation()
orderSet()
profile()
appliedBy()
items()
```

### ConsultationSpecialtyOrderSetApplicationItem

```php id="6fgog3"
application()
orderSetItem()
```

Use casts for JSON fields and booleans.

---

## 4. Add Order Set Service

Create:

```text id="q61qn1"
app/Services/Consultation/Specialty/ConsultationSpecialtyOrderSetService.php
```

Responsibilities:

```php id="g5c41e"
getOrderSetsForProfile(ConsultationSpecialtyProfile $profile): Collection;

getWorkspaceOrderSets(ResolvedConsultationSpecialty|array $context): array;

previewOrderSet(
    $consultation,
    ConsultationSpecialtyOrderSet $orderSet,
    User $user,
    array $options = []
): array;

applyOrderSet(
    $consultation,
    ConsultationSpecialtyOrderSet $orderSet,
    User $user,
    array $selectedItemIds = [],
    array $options = []
): ConsultationSpecialtyOrderSetApplication;
```

## Preview behavior

Preview should return:

```php id="gmzaot"
[
    'order_set' => [
        'id' => ...,
        'code' => ...,
        'name' => ...,
        'description' => ...,
    ],
    'items' => [
        [
            'id' => ...,
            'type' => ...,
            'label' => ...,
            'apply_mode' => ...,
            'can_apply' => true/false,
            'will_create' => ...,
            'will_update' => ...,
            'requires_manual_action' => true/false,
            'warnings' => [...],
            'payload' => [...],
        ],
    ],
    'warnings' => [...],
]
```

## Apply behavior

Rules:

* Use DB transaction.
* Use existing consultation mutation guard/authorization before applying.
* Apply only selected items if `selectedItemIds` are provided.
* If no selected item IDs are provided, apply safe auto-applicable items only.
* Suggestions remain suggestions and should be logged as `suggested`, not forced.
* Unsupported items should be logged as `unsupported`.
* Failed items should not crash the whole order set unless they are required.
* If required item fails, mark application as `partially_applied` or `failed`.
* Return item-level statuses.
* Log application in order set application tables.
* Log clinical/audit activity using existing project convention.
* Do not duplicate existing records where dedupe can be safely detected.

---

## 5. Add Safe Applicators

Inside the order set service or separate small classes, implement safe applicators for:

```text id="j4x8xl"
task
follow_up_instruction / clinical_instruction
specialty_entry_patch
diagnosis suggestion
investigation suggestion
procedure suggestion
drug/prescription suggestion
```

## Task applicator

Use existing consultation task creation workflow if available.

Safe payload example:

```json id="f3tq2t"
{
  "title": "Review pain score",
  "frequency": "Review in 1 week",
  "priority": "normal",
  "notes": "Generated from physiotherapy order set"
}
```

Do not create repeated task frequencies yet unless existing task frequency logic already does this reliably. Repeated sessions can be a later enhancement.

## Specialty entry patch applicator

Use `ConsultationSpecialtyEntryService::upsertEntry`.

Payload example:

```json id="v1ybh1"
{
  "section_key": "consent",
  "merge": {
    "consent_required": true,
    "consent_type": "Dental procedure consent"
  }
}
```

Rules:

* Merge into existing section JSON.
* Do not wipe existing doctor-entered values unless the doctor selected an explicit overwrite option.
* Default behavior is merge missing values only or append text safely.
* Document the chosen strategy.

## Instruction/text applicator

For follow-up/clinical instructions:

* If there is a safe existing clinical instruction field, append text.
* Otherwise return as manual suggestion and let UI insert it into textarea.
* Do not overwrite existing notes.

## Diagnosis/investigation/procedure/drug applicators

For Phase 6, be conservative:

* If item has no linked catalogue model, show it as suggestion only.
* If item is linked but existing create workflow is complex/risky, show it as suggestion only.
* Only create actual records where the existing workflow is straightforward, guarded, validated, and already has a reusable service.
* Document what was enabled and what remained suggestion-only.

This is important. Safety first.

---

## 6. Seed Starter Order Sets

Create:

```text id="g6iq2t"
database/seeders/ConsultationSpecialtyOrderSetSeeder.php
```

Call it from `ConsultationSpecialtySeeder` if safe configuration pattern already exists.

Use `updateOrCreate` idempotently.

Seed starter order sets for:

```text id="lpmxok"
physiotherapy
ophthalmology
dental
```

Do not seed patient data.

Do not require linked catalogue records. Use label-only suggestions when linked records are not available.

---

# Starter Order Sets

## Physiotherapy

### Code: `physio_low_back_pain`

Name:

```text id="t1082s"
Low Back Pain Rehab
```

Items:

```text id="c8298b"
diagnosis suggestion: Low back pain
specialty_entry_patch: presenting_problem.problem_description hint
specialty_entry_patch: pain_assessment.pain_location = Lower back
specialty_entry_patch: treatment_plan.session_frequency = Three times weekly
specialty_entry_patch: treatment_plan.number_of_sessions = 6
task: Perform therapy session
task: Review pain score
follow_up_instruction: Continue home exercises as instructed.
follow_up_instruction: Avoid activities that worsen pain.
```

### Code: `physio_stroke_rehab`

Name:

```text id="50jx6b"
Stroke Rehabilitation Review
```

Items:

```text id="yvmq85"
diagnosis suggestion: Stroke rehabilitation
specialty_entry_patch: functional_limitation.functional_goal hint
specialty_entry_patch: physical_assessment.gait hint
task: Review gait and balance
task: Reassess functional goal
follow_up_instruction: Attend all scheduled therapy sessions.
```

---

## Ophthalmology

### Code: `eye_conjunctivitis`

Name:

```text id="h2el6q"
Conjunctivitis Care
```

Items:

```text id="nsew8w"
diagnosis suggestion: Conjunctivitis
drug suggestion: Antibiotic eye drops
frequency suggestion: Four times daily
specialty_entry_patch: follow_up.follow_up_reason = Review eye redness/discharge
specialty_entry_patch: follow_up.warning_signs = Return immediately if vision worsens or severe pain develops.
follow_up_instruction: Avoid rubbing the eye.
follow_up_instruction: Use eye drops as prescribed.
task: Review in 3 days
```

### Code: `eye_glaucoma_review`

Name:

```text id="gx7owx"
Glaucoma Review
```

Items:

```text id="mso8ot"
diagnosis suggestion: Glaucoma
investigation suggestion: Intraocular pressure measurement
investigation suggestion: Visual field test
investigation suggestion: OCT
specialty_entry_patch: iop.method = Tonometry
task: Review eye pressure
follow_up_instruction: Attend follow-up for eye pressure or vision review.
```

---

## Dental

### Code: `dental_extraction_prep`

Name:

```text id="wf1ixc"
Dental Extraction Preparation
```

Items:

```text id="zg1ut2"
diagnosis suggestion: Dental caries
investigation suggestion: Periapical X-ray
procedure suggestion: Tooth extraction
specialty_entry_patch: consent.consent_required = true
specialty_entry_patch: consent.consent_type = Dental extraction consent
drug suggestion: Oral analgesic
follow_up_instruction: Do not rinse mouth vigorously for 24 hours after extraction.
follow_up_instruction: Return if bleeding persists.
task: Dental review in 1 week
```

### Code: `dental_abscess`

Name:

```text id="l2kj03"
Dental Abscess Care
```

Items:

```text id="l0dksh"
diagnosis suggestion: Dental abscess
investigation suggestion: Periapical X-ray
procedure suggestion: Incision and drainage
drug suggestion: Antibiotic
drug suggestion: Oral analgesic
follow_up_instruction: Return if swelling or fever develops.
task: Review in 3 days
```

---

## 7. Add Workspace Payload

Update the consultation workspace controller/provider to pass:

```text id="k08ezn"
specialtyOrderSets
```

as normalized array.

Each order set should include:

```php id="ugw98p"
[
    'id' => ...,
    'code' => ...,
    'name' => ...,
    'description' => ...,
    'category' => ...,
    'icon' => ...,
    'color' => ...,
    'items_count' => ...,
]
```

Only include active order sets for the resolved active specialty profile.

General medicine can receive an empty list for now.

---

## 8. Add Controller and Routes

Create controller:

```text id="nrxhb9"
app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtyOrderSetController.php
```

Use existing consultation namespace/middleware pattern.

Required actions:

```php id="j3073a"
index(Request $request, Consultation $consultation) // optional JSON list if needed
preview(Request $request, Consultation $consultation, ConsultationSpecialtyOrderSet $orderSet)
apply(Request $request, Consultation $consultation, ConsultationSpecialtyOrderSet $orderSet)
```

Suggested route names, adapt to project convention:

```text id="yu5lir"
doctor.consultations.specialty-order-sets.index
doctor.consultations.specialty-order-sets.preview
doctor.consultations.specialty-order-sets.apply
```

Rules:

* Same auth/permission/middleware as clinical consultation mutations.
* Use existing consultation mutation context guard.
* Ensure the order set belongs to the resolved active specialty profile.
* Reject inactive order sets.
* Return JSON for Ajax requests.
* Redirect back with flash only if normal form post is used.
* Include item-level statuses in response.

---

## 9. Add UI: Order Set Panel

Add a compact panel/card in the consultation workspace.

Suggested placement:

```text id="mllu2b"
near specialty identity strip
or inside right panel
or above specialty sections
```

Choose the least disruptive place.

Panel behavior:

* Shows active specialty order sets.
* Each order set has:

  * name
  * short description
  * item count
  * Preview button
* Preview opens:

  * modal, drawer, or inline reveal consistent with existing UI
  * list of items
  * badges: apply / suggest / manual
  * warnings for unsupported items
  * checkboxes for selectable items
  * Apply selected button
* Apply response:

  * shows success/partial/warning message
  * refreshes affected sections where possible
  * does not break current Ajax refresh behavior

Do not make the UI too heavy.

For general medicine, hide the panel if no order sets exist.

---

## 10. Client-Side Behavior

Use the existing JavaScript approach for the consultation workspace.

Add small JS only if needed.

Rules:

* Do not break existing `data-ajax-form`.
* Preserve route context.
* Use existing toast/flash conventions if available.
* Keep selectors scoped to order set panel.
* If preview/apply fails, show friendly error and do not crash page.
* Refresh or reload only affected sections where practical.
* If full section refresh is complex, show success and leave saved entries visible after reload.

---

## 11. Localisation

Add EN/FR keys for:

```text id="uabdaq"
order_sets.title
order_sets.preview
order_sets.apply
order_sets.apply_selected
order_sets.items
order_sets.no_order_sets
order_sets.suggestion
order_sets.manual_action
order_sets.can_apply
order_sets.unsupported
order_sets.partially_applied
order_sets.applied
order_sets.failed
order_sets.warnings
order_sets.preview_intro
order_sets.confirm_apply
order_sets.from_order_set
```

Use existing `consultation_specialties.php` if appropriate.

No new visible text should be left untranslated.

---

## 12. Add Focused Tests

Create:

```text id="p79wdf"
tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php
```

Suggested tests:

### Seeder creates order sets

* Physiotherapy has low back pain and stroke rehab order sets.
* Ophthalmology has conjunctivitis and glaucoma review order sets.
* Dental has extraction prep and abscess care order sets.

### Order sets are scoped by active profile

* Eye workspace receives eye order sets only.
* Dental workspace receives dental order sets only.
* General medicine receives none or only general order sets if seeded.

### Preview returns item statuses

* Preview includes item list.
* Suggestion-only items are marked manual/suggested.
* Safe applicable items are marked can_apply.

### Apply creates application audit

* Applying an order set creates `consultation_specialty_order_set_applications`.
* Creates item-level application records.

### Apply patches specialty entry

* Dental extraction prep sets/merges consent required.
* Physio low back pain sets/merges treatment plan session frequency/number of sessions.
* Eye conjunctivitis sets/merges follow-up warning signs.

### Apply creates safe task items

* Task items create consultation tasks only if existing task workflow supports it.
* Otherwise they are logged as suggested/unsupported, not failed.

### Existing values are not overwritten

* Existing specialty entry field should not be overwritten unless explicit overwrite option is used.

### Inactive order set rejected

* Cannot preview/apply inactive order set.

### Wrong profile rejected

* Cannot apply dental order set while resolved profile is ophthalmology.

### General consultation unaffected

* General consultation core behavior remains stable.

### Workspace payload includes order sets

* `specialtyOrderSets` is present for relevant specialist workspace.

### Localisation keys exist

* New EN/FR keys exist.

---

## 13. Optional Browser Smoke Test

If existing Playwright consultation fixture is stable, add a light smoke test for one order set:

```text id="s3zogz"
Open specialist consultation
Preview an order set
Apply safe selected item
Confirm success message
Confirm patched specialist field/task appears
```

Only do this if the fixture is already stable.

Do not create a heavy browser suite in this phase.

---

## 14. Minimal Checks to Run

Run:

```bash id="lwkvqx"
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Also run PHP lint on new/modified PHP files.

If localisation keys were added and the project has a localisation parity/lock command, run it.

Do not run the wide full-suite yet.

---

## 15. Phase Report

Create:

```text id="lxrc7b"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_6_ORDER_SETS_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="tt4ym8"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_6_ORDER_SETS_REPORT.md
```

The report must include:

```text id="ldth8x"
# Consultation Specialist Extension — Phase 6 Order Sets Report

## Summary
Explain what was implemented.

## Existing Mutation Path Findings
Document diagnosis, investigation, procedure, prescription, task, instruction, specialty entry, and audit paths discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Database Changes
List migrations, tables, and key fields.

## Order Set Design
Explain order sets, items, apply modes, preview behavior, and application audit.

## Seeded Order Sets
List seeded order sets by specialty.

## Safe Applicator Behavior
Explain what is auto-applied, what remains suggestion-only, and why.

## Workspace Integration
Explain how `specialtyOrderSets` is passed to the workspace.

## UI Changes
Explain the order set panel, preview, apply selected behavior, warnings, and refresh behavior.

## Backward Compatibility
Confirm general consultation and existing core section behavior remain stable.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 7 specialty completion readiness
- Phase 8 specialty summary builder
- Phase 9 doctor personal workspace
- Later admin configuration UI
- Later billing/service mapping
```

---

# Acceptance Criteria

Phase 6 is complete only when:

* Order set tables exist.
* Order set models exist.
* `ConsultationSpecialtyOrderSetService` exists.
* Starter order sets are seeded idempotently for physiotherapy, ophthalmology, and dental.
* Workspace receives `specialtyOrderSets`.
* Specialist consultation UI shows a compact order set panel where relevant.
* Doctors can preview order set items before applying.
* Doctors can apply selected safe items.
* Application audit records are created.
* Item-level statuses are recorded.
* Specialty entry patch items work safely and do not overwrite existing values by default.
* Task items are safely applied only through existing task workflow or logged as suggestions/unsupported.
* Label-only diagnosis/investigation/procedure/drug items remain suggestions unless safely linked and supported.
* Wrong-profile and inactive order sets are rejected.
* General consultation behavior remains stable.
* Focused order set tests pass.
* Phase 1-5 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 6 report is created.

Stop after Phase 6. Do not implement completion readiness, summary builder, personal workspace, admin UI, or billing mapping yet.
