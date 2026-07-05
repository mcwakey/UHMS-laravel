You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver

Phase 2 added `specialtyContext` as read-only data to the consultation workspace payload and confirmed that the visible consultation UI remained unchanged.

Now implement:

# Consultation Specialist Extension — Phase 3: Specialty Layout Engine

## Phase 3 Goal

Make the consultation workspace consume the resolved `specialtyContext` and render consultation sections according to the active specialty profile.

This phase introduces the **layout engine** only.

The goal is to support:

* section ordering by specialty
* section visibility by specialty
* section labels by specialty
* required badges by specialty
* safe fallback for unknown specialty sections
* unchanged general consultation behavior
* no duplication of the consultation module

Do not implement full structured physiotherapy, ophthalmology, or dental forms yet. That comes in Phase 4.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate consultation pages/modules for physio, eye, dental, etc.

Do not change backend save behavior for existing core consultation sections unless absolutely necessary.

Do not implement specialty completion readiness yet. That comes later.

Do not implement specialty summary builder yet. That comes later.

Do not implement billing mapping yet. That comes later.

Do not run the full test suite yet.

Run only focused checks for this phase.

The general medicine profile must preserve the existing consultation workspace behavior and section order as much as possible.

If `specialtyContext` is missing, invalid, or incomplete, the page must fall back to the current general consultation layout.

---

# Current Context

Phase 2 report confirms:

* `ConsultationSpecialtyProfileResolver` exists.
* `ResolvedConsultationSpecialty` DTO exists.
* `specialtyContext` is passed read-only into `HandlesConsultationWorkspace::show`.
* The UI does not consume `specialtyContext` yet.
* The visible consultation UI was unchanged.

Now consume this context safely.

---

# Required Deliverables

## 1. Inspect Current Consultation View Structure

Before coding, inspect the current consultation workspace Blade/Inertia/Vue structure.

Identify:

```text id="ket9nq"
Main consultation workspace view
Existing section partials/components
Current section order
Current section save endpoints/actions
Current section IDs/anchors
Current accordion/hidden compartment behavior
Current frontend JavaScript used by consultation sections
Current payload variable names
```

Do not guess. Follow the existing project style.

Document any key findings in the phase report.

---

## 2. Add Consultation Section Component Registry

Create a service/registry:

```text id="b3mr7o"
app/Services/Consultation/Specialty/ConsultationSpecialtySectionComponentRegistry.php
```

Purpose:

Map specialty section keys to renderable components/partials.

The registry should expose methods like:

```php id="oxnnqw"
public function resolveComponent(string $sectionKey, ?string $configuredComponent = null): string;
public function isCoreSection(string $sectionKey): bool;
public function fallbackComponent(): string;
public function coreSectionKeys(): array;
```

Use the project’s Blade/Inertia conventions.

Suggested core mappings:

```text id="p4ry6r"
patient_summary        -> existing patient summary section
complaints             -> existing complaints section
hopc                   -> existing HOPC/history section
examination            -> existing examination section
diagnosis              -> existing diagnosis section
investigations         -> existing investigations section
prescription           -> existing prescription section
procedures             -> existing procedures section
tasks                  -> existing tasks section
notes                  -> existing notes section
summary                -> existing summary section
completion_readiness   -> existing completion readiness section
```

Specialty aliases for Phase 3:

```text id="mavtuz"
eye_complaint          -> existing complaints section if safe, otherwise generic specialty section shell
dental_complaint       -> existing complaints section if safe, otherwise generic specialty section shell
presenting_problem     -> existing complaints section if safe, otherwise generic specialty section shell
progress_notes         -> existing notes section if safe, otherwise generic specialty section shell
follow_up              -> existing tasks/follow-up section if safe, otherwise generic specialty section shell
```

Unknown/new specialty sections must render through a safe generic section shell.

Do not crash because a configured component does not exist.

---

## 3. Add Specialty Layout Service

Create:

```text id="yfl410"
app/Services/Consultation/Specialty/ConsultationSpecialtyLayoutService.php
```

Responsibilities:

```php id="w89x4i"
buildLayout(array|ResolvedConsultationSpecialty $specialtyContext, array $existingWorkspacePayload = []): array
```

It should return a normalized layout array:

```php id="pcpv3f"
[
    'profile' => [
        'id' => ...,
        'code' => ...,
        'name' => ...,
        'translated_name' => ...,
        'icon' => ...,
        'color' => ...,
    ],
    'is_fallback' => true/false,
    'source' => ...,
    'sections' => [
        [
            'key' => ...,
            'label' => ...,
            'translated_label' => ...,
            'component' => ...,
            'display_order' => ...,
            'is_required' => true/false,
            'is_visible' => true/false,
            'is_core' => true/false,
            'config' => [...]
        ],
    ],
]
```

Rules:

* Only visible sections should be returned.
* Sections must be ordered by `display_order`.
* If no valid sections exist, return general medicine layout.
* If the selected specialty has duplicate section keys, normalize defensively and keep the first ordered occurrence.
* If a component is missing, use generic fallback component.
* Required sections should be marked, but not enforced yet.
* Do not mutate `specialtyContext`.

---

## 4. Add Generic Specialty Section Shell

Create a reusable generic section partial/component for specialty sections that do not yet have structured forms.

Suggested file path, adapt to project convention:

```text id="kqkhg4"
resources/views/doctor/consultations/partials/specialty-generic-section.blade.php
```

or equivalent under the current consultation partials directory.

The generic section shell should:

* render the section label
* render a small “Specialist section” badge
* show required badge if `is_required = true`
* display a clean empty-state/help text
* not break form submission
* not require new save behavior in this phase
* not claim structured fields exist yet

Suggested text:

```text id="hwpvbe"
This specialist section is enabled for this consultation profile. Structured fields for this section will be added in the next phase.
```

If the project prefers no “coming next phase” UI text, use a neutral empty-state like:

```text id="2na0j0"
No structured data has been configured for this specialist section yet.
```

Do not add noisy development wording visible to hospital users.

---

## 5. Add Specialty Workspace Header / Identity Strip

Add a small, non-intrusive specialty identity strip at the top of the consultation workspace.

It should show:

```text id="wzgiku"
Specialty name
Profile source/fallback indicator only if useful
Icon/color if available
```

Examples:

```text id="y2nuwk"
General Medicine Workspace
Physiotherapy Workspace
Eye Clinic Workspace
Dental Workspace
```

Rules:

* Keep it visually consistent with current UHMS design.
* Do not make it too large.
* Do not disturb the main consultation workflow.
* For general medicine, it should be subtle and not make the screen feel newly bloated.
* If `specialtyContext.is_fallback = true`, do not show alarming warning text. General fallback is normal.

---

## 6. Render Sections Through Layout Engine

Update the main consultation workspace view to render sections from the normalized layout.

Important:

* Existing core section partials must still receive the same variables they currently receive.
* Existing section forms/actions must keep working.
* Existing JavaScript selectors/classes/data attributes should remain stable where possible.
* Existing anchors/IDs should remain stable for core sections.
* Do not break hidden compartment behavior.
* Do not break modals/dropdowns/search inputs.
* Do not break current complaint/HOPC/diagnosis/investigation/prescription/procedure/task behavior.

Suggested approach:

```php id="cp0hny"
@foreach($specialtyLayout['sections'] as $section)
    @include($section['component'], [
        'section' => $section,
        // existing consultation variables remain available
    ])
@endforeach
```

Adapt to actual project style.

If the current view is too complex for a full switch in one step, use a safer wrapper:

```text id="rxdpnl"
- Keep current general layout untouched for general_medicine.
- Use dynamic specialty layout only for non-general profiles.
```

But the preferred result is one layout engine that also renders general medicine correctly.

---

## 7. Preserve Current General Consultation Layout

This is critical.

For the `general_medicine` profile:

* section order must match current consultation order
* labels should match current labels
* core components should be the same existing components
* behavior should remain unchanged

Add a regression test specifically for this.

If exact snapshot testing is not available, test that:

```text id="35nhyv"
general_medicine layout contains the expected current core section keys
core sections resolve to existing components
generic fallback is not used for normal general sections
```

---

## 8. Specialist Layout Behavior for Seeded Profiles

For seeded non-general profiles, the screen should now reflect their section order and names.

Expected profile sections:

### Physiotherapy

```text id="9ep1kj"
patient_summary
presenting_problem
pain_assessment
functional_limitation
physical_assessment
treatment_plan
therapy_session
home_exercise_plan
tasks
progress_notes
summary
completion_readiness
```

### Ophthalmology

```text id="ysn9mg"
patient_summary
eye_complaint
visual_acuity
refraction
iop
eye_examination
diagnosis
investigations
procedures
prescription
follow_up
summary
completion_readiness
```

### Dental

```text id="256x7v"
patient_summary
dental_complaint
tooth_chart
oral_examination
dental_diagnosis
dental_xray
dental_procedures
consent
prescription
follow_up
summary
completion_readiness
```

For sections that do not have structured forms yet, use the generic specialty section shell.

---

## 9. Add Localisation Keys If Needed

If new UI labels are introduced, add EN/FR keys.

Suggested namespace can remain:

```text id="xkjozv"
consultation_specialties.php
```

Possible keys:

```text id="tzx4v1"
workspace.title
workspace.specialist_section
workspace.required
workspace.no_structured_data
workspace.fallback_general
```

Do not introduce untranslated strings in Blade/PHP where the project expects localisation.

Run localisation parity/lock check if the project has one.

---

## 10. Add Focused Tests

Create:

```text id="ib7n5u"
tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
```

Suggested tests:

### General layout preserves core sections

Assert the general medicine layout includes:

```text id="us56rf"
patient_summary
complaints
hopc
examination
diagnosis
investigations
prescription
procedures
tasks
notes
summary
completion_readiness
```

in correct order.

### Core section registry resolves known sections

Assert known general sections resolve to non-generic components.

### Unknown section uses generic fallback

Add or simulate an unknown section and assert it resolves to generic shell.

### Physiotherapy layout order

Assert physiotherapy layout returns the seeded physio section order.

### Ophthalmology layout order

Assert ophthalmology layout returns the seeded ophthalmology section order.

### Dental layout order

Assert dental layout returns the seeded dental section order.

### Required badge metadata

Mark a section required and assert layout includes `is_required = true`.

### Invalid/missing context fallback

If layout receives missing/invalid context, assert it falls back to general medicine layout.

### Consultation workspace smoke

Where feasible, request the consultation workspace as a user with a mapped specialty and assert:

```text id="08auvh"
specialty workspace title/strip appears
expected specialty section labels appear
general core behavior still renders
```

If the consultation workspace requires heavy fixtures, create a minimal fixture following existing project patterns. Do not build massive test data in this phase.

---

## 11. Keep Existing Consultation Actions Working

After rendering through the layout engine, manually/focused-test the existing core actions if there are existing tests for them:

```text id="r7trhm"
complaints
HOPC
diagnosis
investigations
prescription
procedures
tasks
notes
summary
```

Do not run the full consultation suite unless required by project convention. Prefer targeted tests only.

---

## 12. Minimal Checks to Run

Run:

```bash id="6xfg3b"
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php
php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php
php artisan route:list
```

Also run PHP lint on new/modified PHP files.

If views were touched and the project has a view compile command/test, run the focused view compilation check.

If localisation keys were added and the project has a localisation lock/parity command, run it.

Do not run the wide full-suite yet.

---

## 13. Phase Report

Create:

```text id="7s5r0x"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_3_LAYOUT_ENGINE_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="l7jv6v"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_3_LAYOUT_ENGINE_REPORT.md
```

The report must include:

```text id="gp4owj"
# Consultation Specialist Extension — Phase 3 Layout Engine Report

## Summary
Explain what was implemented.

## Current Consultation View Findings
List the existing view/partial structure discovered before implementation.

## Files Added
List new files.

## Files Modified
List modified files.

## Layout Engine Design
Explain registry, layout service, fallback behavior, and generic section shell.

## Specialty Layouts
List the resulting section order for:
- General Medicine
- Physiotherapy
- Ophthalmology
- Dental

## UI Changes
Describe the specialty identity strip and dynamic section rendering.

## Backward Compatibility
Confirm existing general consultation behavior is preserved.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List items for Phase 4 structured forms.
```

---

# Acceptance Criteria

Phase 3 is complete only when:

* The consultation workspace consumes `specialtyContext`.
* A section component registry exists.
* A specialty layout service exists.
* General medicine renders the expected current consultation section order.
* Physiotherapy, ophthalmology, and dental render their seeded section orders.
* Unknown/specialist-only sections render safely through a generic section shell.
* Required section metadata is visible in the layout but not enforced yet.
* A specialty identity strip appears in the consultation workspace.
* Existing core consultation section behavior remains working.
* Focused layout tests pass.
* Phase 1 and Phase 2 tests still pass.
* Visible general consultation behavior remains stable.
* Phase 3 report is created.

Stop after Phase 3. Do not implement structured specialist forms yet.
