You are working on the UHMS Laravel codebase.

We have completed:

* Phase 1: Specialist Consultation Profile Foundation
* Phase 2: Specialty Resolver
* Phase 3: Specialty Layout Engine
* Phase 4: Structured Specialist Forms
* Phase 5: Specialty Favorites and Smart Defaults
* Phase 6: Specialty Order Sets
* Phase 7: Specialty Completion Readiness
* Phase 8: Specialty Summary Builder

Phase 8 added preview-only specialty summary generation. Generated summaries are not auto-saved; they only insert into the existing final-note textarea after doctor action, and the doctor must still use the existing Save action.

Now implement:

# Consultation Specialist Extension — Phase 9: Doctor Personal Workspace

## Phase 9 Goal

Make the consultation workspace feel personal to the doctor and their active specialty.

When a doctor opens a consultation, UHMS should show a compact personal clinical workspace identity:

```text id="nmxx13"
Dr. Mensah
Eye Clinic Workspace

Today:
- 12 waiting
- 4 reviewed
- 3 pending results
- 2 follow-ups due

Quick actions:
Visual Acuity · IOP · Eye Examination · Generate Summary · Preview Order Set
```

This phase should not change clinical save behavior. It should personalize the workspace using existing specialty profile, department context, readiness, favorites, order sets, summary builder, and doctor preferences.

---

# Important Rules

Do not rewrite the consultation module.

Do not create separate doctor workspaces for physio, eye, dental, etc.

Do not replace existing department dashboards.

Do not implement billing/service mapping yet.

Do not build full admin configuration UI yet.

Do not run the full test suite yet.

Only run focused checks for this phase.

The personal workspace must be additive and compact. Do not make the consultation page heavy or crowded.

General medicine must remain clean and not overloaded.

If doctor workspace data fails to load, the consultation page must still open normally.

---

# Current Context

Phase 1 created:

```text id="e753sv"
doctor_consultation_preferences
```

Phase 2 added active specialty resolution.

Phase 3 added specialty layout and identity strip.

Phase 4 added structured specialist forms.

Phase 5 added specialty favorites and frequency defaults.

Phase 6 added specialty order sets.

Phase 7 added specialty readiness.

Phase 8 added specialty summary builder metadata and preview endpoint.

Phase 9 should bring those pieces together into a personalized doctor-facing workspace layer.

---

# Required Deliverables

## 1. Inspect Existing Doctor/User Context

Before coding, inspect existing code for:

```text id="i3b2ca"
auth user model
doctor/staff profile model if any
department assignment
primary department
active dashboard/department context
consultation route queue
visit consultation route status
today's consultations query
appointments query
pending investigation result query
pending prescription query
pending procedure/task query
doctor_consultation_preferences from Phase 1
current specialty identity strip from Phase 3
right-panel structure
page config JSON
consultation-show JavaScript
```

Do not guess model names.

Document findings in the phase report.

---

## 2. Add Doctor Workspace DTO

Create:

```text id="7uge37"
app/Data/Consultation/Specialty/DoctorSpecialtyWorkspace.php
```

Or follow the project’s DTO/data convention.

It should contain:

```php id="69tinc"
doctor
profile
department
specialty
metrics
quickActions
pinnedActions
alerts
preferences
todayContext
summaryBuilder
readiness
orderSets
isFallback
```

Provide:

```php id="gkzro3"
toArray(): array
```

Payload must be Blade/JSON safe.

Do not expose sensitive staff/private user data unnecessarily.

---

## 3. Add Doctor Workspace Service

Create:

```text id="i548g0"
app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceService.php
```

Main method:

```php id="47rz8y"
public function build(
    User $user,
    $consultation,
    ResolvedConsultationSpecialty|array $specialtyContext,
    array $workspacePayload = []
): DoctorSpecialtyWorkspace;
```

Responsibilities:

```text id="b0nmv9"
Resolve doctor identity
Resolve active specialty profile
Resolve active department/context
Load doctor consultation preferences
Build specialty-specific metrics
Build specialty-specific quick actions
Build lightweight alerts
Expose pinned actions
Expose summary-builder availability
Expose order-set availability
Expose readiness status
Return safe fallback when anything fails
```

Rules:

* Keep queries light.
* Scope metrics to the doctor, active department, active specialty, and current day where possible.
* If exact scoping is not available, use safe approximate counts and document it.
* Do not block page rendering if metrics fail.
* Cache only if project convention supports it. If not, keep queries simple.
* No patient-sensitive details in aggregate workspace metrics.

---

## 4. Add Quick Action Registry

Create:

```text id="v0675n"
app/Services/Consultation/Specialty/ConsultationSpecialtyQuickActionRegistry.php
```

Purpose:

Define quick actions per specialty.

Method:

```php id="z4dcxi"
public function actionsForProfile(ConsultationSpecialtyProfile $profile): array;
```

Each action should include:

```php id="bcut0i"
[
    'key' => 'visual_acuity',
    'label' => __('consultation_specialties.quick_actions.visual_acuity'),
    'type' => 'section_anchor',
    'target' => '#specialty-section-visual_acuity',
    'icon' => 'eye',
    'priority' => 10,
    'requires_section' => 'visual_acuity',
]
```

Supported action types:

```text id="v33tw8"
section_anchor
open_order_sets
generate_summary
insert_favorite
open_readiness
open_tasks
open_prescription
open_investigations
```

Do not create risky direct mutation actions in this phase. Quick actions should navigate, open UI panels, or prefill suggestions only.

---

# Quick Actions to Implement

## General Medicine

Keep light:

```text id="kdc691"
Complaints
Examination
Diagnosis
Prescription
Generate Summary
Readiness
```

## Physiotherapy

```text id="gdbias"
Presenting Problem
Pain Assessment
Physical Assessment
Treatment Plan
Therapy Session
Home Exercise Plan
Order Sets
Generate Summary
Readiness
```

## Ophthalmology

```text id="h7z3ee"
Eye Complaint
Visual Acuity
Refraction
IOP
Eye Examination
Prescription
Order Sets
Generate Summary
Readiness
```

## Dental

```text id="f7ij2u"
Dental Complaint
Tooth Chart
Oral Examination
Dental Diagnosis
Dental Procedure
Consent
Order Sets
Generate Summary
Readiness
```

---

## 5. Use Doctor Consultation Preferences

Use existing table:

```text id="uv4j4f"
doctor_consultation_preferences
```

Support:

```text id="k7ecau"
pinned_actions
preferred_layout
compact_mode
default_consultation_specialty_profile_id
default_department_id
metadata
```

Add service methods if needed:

```php id="6wrylv"
getOrCreatePreference(User $user): DoctorConsultationPreference;
updatePinnedActions(User $user, array $actions): DoctorConsultationPreference;
updateLayoutPreference(User $user, ?string $layout, bool $compactMode): DoctorConsultationPreference;
```

Suggested service:

```text id="o8h224"
app/Services/Consultation/Specialty/DoctorConsultationPreferenceService.php
```

If a service already exists, extend it.

---

## 6. Add Preference Controller and Routes

Create controller:

```text id="pgfrwf"
app/Http/Controllers/Doctor/Consultations/DoctorConsultationPreferenceController.php
```

Actions:

```php id="7sieib"
updatePinnedActions(Request $request)
updateLayout(Request $request)
```

Routes should follow existing authenticated doctor/consultation route conventions.

Suggested names:

```text id="iub8ng"
doctor.consultations.preferences.pinned-actions.update
doctor.consultations.preferences.layout.update
```

Validation:

```text id="8fqqvj"
pinned_actions nullable array max 12
pinned_actions.* string max 100
preferred_layout nullable in:default,compact,expanded
compact_mode boolean
```

Rules:

* A doctor can only update their own preferences.
* Do not allow arbitrary unsafe action keys. Validate action keys against quick action registry plus known workspace actions.
* Return JSON for Ajax requests.
* Redirect back with flash for normal post if project supports it.

---

## 7. Add Specialty Workspace Metrics

Add a lightweight metrics provider:

```text id="o5opcu"
app/Services/Consultation/Specialty/DoctorSpecialtyWorkspaceMetricService.php
```

Main method:

```php id="qnbjxy"
public function metricsFor(
    User $user,
    $consultation,
    ConsultationSpecialtyProfile $profile,
    mixed $department = null
): array;
```

Suggested metrics:

```text id="6vz17a"
waiting_today
reviewed_today
pending_completion
pending_readiness_blocks
pending_results
pending_tasks
followups_due
order_sets_available
summary_available
```

Rules:

* Use actual existing statuses/tables where available.
* Keep counts scoped to today and active doctor/department where possible.
* If a metric cannot be safely computed, return null and hide it.
* Do not expose patient names/details in this compact header.
* Do not add heavy joins that slow the consultation page.

Specialty-specific labels:

## Physiotherapy

```text id="ko9ibo"
Active therapy sessions
Pending rehab plans
Follow-ups due
Readiness blocks
```

## Ophthalmology

```text id="snx77c"
Eye cases waiting
Pending eye results
Follow-ups due
Readiness blocks
```

## Dental

```text id="jva4hi"
Dental cases waiting
Procedures pending
Consent blocks
Follow-ups due
```

Keep implementation realistic based on available data. Do not invent records.

---

## 8. Add Doctor Workspace Alerts

Build small non-blocking alerts from existing payloads:

```text id="mzdbuw"
readiness blocking count
readiness warning count
order sets available
summary builder available
pending tasks
missing consent for dental
missing visual acuity for eye
missing treatment plan for physio
```

Rules:

* Alerts should be advisory.
* Do not duplicate the full readiness card.
* Max 3 visible alerts in the header.
* Link alerts to the related section if possible.
* Do not show alarming language unless completion is actually blocked.

---

## 9. Workspace UI Changes

Update the existing specialty identity strip from Phase 3 into a richer but compact **Doctor Specialty Workspace Header**.

It should include:

```text id="obq9d5"
Doctor display name
Specialty workspace name
Department/context name if available
Current patient/route context label if appropriate
Small metrics chips
Pinned quick actions
Compact alerts
Preference controls
```

Suggested layout:

```text id="ymkufd"
[Dr. Mensah] [Eye Clinic Workspace] [Department: Ophthalmology]
Waiting: 12 · Reviewed: 4 · Pending results: 3 · Readiness: 2 blocks

Quick actions:
[Visual Acuity] [IOP] [Eye Exam] [Order Sets] [Generate Summary]
```

Rules:

* Must be responsive.
* Must not dominate the page.
* Must not break existing patient card.
* General medicine should remain simple.
* If compact mode is on, show fewer metrics/actions.
* If no metrics are available, show only identity + quick actions.

---

## 10. Quick Action Behavior

Implement quick actions as safe UI/navigation actions:

```text id="xuu398"
section anchors scroll to section
order sets opens the order set panel/modal
generate summary opens summary preview flow
readiness scrolls to readiness card
prescription scrolls to prescription section
investigations scrolls to investigations section
```

Rules:

* No direct clinical save.
* No direct prescription/order/procedure creation.
* Preserve existing JavaScript.
* Keep selectors scoped.
* If target section does not exist, hide the action or disable it safely.

---

## 11. Add Workspace Payload

Update consultation workspace controller/provider to pass:

```text id="c418z3"
doctorSpecialtyWorkspace
```

to Blade and page config JSON.

Payload should include:

```php id="0j3b55"
[
    'doctor' => [
        'name' => ...,
        'display_name' => ...,
    ],
    'profile' => [...],
    'department' => [...],
    'metrics' => [...],
    'quick_actions' => [...],
    'pinned_actions' => [...],
    'alerts' => [...],
    'preferences' => [...],
]
```

Do not expose email/phone unless already visible elsewhere and needed.

---

## 12. Localisation

Add EN/FR keys.

Suggested keys:

```text id="gqxv5e"
workspace.doctor_workspace
workspace.department
workspace.today
workspace.quick_actions
workspace.pinned_actions
workspace.pin
workspace.unpin
workspace.compact_mode
workspace.default_layout
workspace.no_metrics
workspace.alerts
workspace.readiness_blocks
workspace.readiness_warnings
workspace.order_sets_available
workspace.summary_available

quick_actions.complaints
quick_actions.examination
quick_actions.diagnosis
quick_actions.prescription
quick_actions.investigations
quick_actions.procedures
quick_actions.tasks
quick_actions.readiness
quick_actions.generate_summary
quick_actions.order_sets
quick_actions.presenting_problem
quick_actions.pain_assessment
quick_actions.physical_assessment
quick_actions.treatment_plan
quick_actions.therapy_session
quick_actions.home_exercise_plan
quick_actions.eye_complaint
quick_actions.visual_acuity
quick_actions.refraction
quick_actions.iop
quick_actions.eye_examination
quick_actions.dental_complaint
quick_actions.tooth_chart
quick_actions.oral_examination
quick_actions.dental_diagnosis
quick_actions.dental_procedure
quick_actions.consent

metrics.waiting_today
metrics.reviewed_today
metrics.pending_completion
metrics.pending_results
metrics.pending_tasks
metrics.followups_due
metrics.readiness_blocks
metrics.order_sets_available
metrics.summary_available
```

No new visible text should be hardcoded.

---

## 13. Add Focused Tests

Create:

```text id="vykf15"
tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php
```

Suggested tests:

### Workspace payload exists

* Consultation workspace includes `doctorSpecialtyWorkspace`.

### General medicine remains light

* General workspace has only general quick actions and no specialist-only overload.

### Physiotherapy quick actions

* Physio profile includes pain assessment, treatment plan, therapy session, home exercise, order sets, summary, readiness.

### Ophthalmology quick actions

* Eye profile includes visual acuity, refraction, IOP, eye examination, order sets, summary, readiness.

### Dental quick actions

* Dental profile includes tooth chart, oral examination, dental diagnosis, dental procedure, consent, order sets, summary, readiness.

### Quick actions only target available sections

* If section is missing from layout, action is hidden or disabled safely.

### Pinned actions persist

* Doctor can save pinned quick actions.
* Reload workspace returns same pinned actions.

### Invalid pinned actions rejected

* Unknown action keys are rejected.

### Compact mode preference persists

* Doctor can toggle compact mode.
* Workspace payload reflects compact mode.

### Metrics are safe

* Metrics return numeric/null values only.
* No patient names are exposed.

### Alerts reflect readiness

* Specialist readiness blocking count appears as alert.
* Warning-only alerts do not block page.

### Workspace degrades safely

* If metrics service throws or lacks data, consultation workspace still renders.

### Localisation keys exist

* EN/FR keys exist for new workspace/quick action labels.

### Existing Phase 1-8 behavior remains stable

* At minimum, run the focused specialty tests and workspace stabilisation test.

---

## 14. Optional Browser Smoke Test

If the existing Playwright consultation fixture is stable, add a light smoke test:

```text id="9rhsym"
Open specialist consultation
Confirm doctor workspace header appears
Click quick action Visual Acuity / Pain Assessment / Tooth Chart
Confirm page scrolls to the correct section
Toggle compact mode or save pinned action if UI supports it
```

Only do this if the fixture is already stable.

Do not create a heavy browser suite in this phase.

---

## 15. Minimal Checks to Run

Run:

```bash id="uzsbfy"
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
php artisan test tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php
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

```text id="u5n7fw"
docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_9_DOCTOR_PERSONAL_WORKSPACE_REPORT.md
```

If the repo convention has a consultation docs subfolder, use:

```text id="80hklr"
docs/consultation/CONSULTATION_SPECIALIST_EXTENSION_PHASE_9_DOCTOR_PERSONAL_WORKSPACE_REPORT.md
```

The report must include:

```text id="rm0qac"
# Consultation Specialist Extension — Phase 9 Doctor Personal Workspace Report

## Summary
Explain what was implemented.

## Existing Doctor/Context Findings
Document user/doctor profile, department context, queue/status, preferences, and workspace UI structures discovered.

## Files Added
List all new files.

## Files Modified
List all modified files.

## Doctor Workspace Design
Explain DTO, workspace service, quick action registry, metrics service, preference service, fallback behavior, and scoping.

## Quick Actions Implemented
List quick actions for:
- General Medicine
- Physiotherapy
- Ophthalmology
- Dental

## Metrics and Alerts
Explain available metrics, hidden/null metric behavior, and alert behavior.

## UI Changes
Explain the doctor specialty workspace header, pinned actions, compact mode, quick action navigation, and responsive behavior.

## Preferences
Explain how pinned actions/layout/compact mode are stored and updated.

## Backward Compatibility
Confirm consultation clinical save behavior, specialist forms, order sets, readiness, and summary builder remain stable.

## Tests Added
List focused tests.

## Checks Run
Include commands and pass/fail summary.

## Known Issues / Follow-up
List anything for:
- Phase 10 admin configuration UI
- Later billing/service mapping
- Later dashboard integration
- Later full-suite/browser testing
```

---

# Acceptance Criteria

Phase 9 is complete only when:

* `DoctorSpecialtyWorkspace` DTO exists.
* `DoctorSpecialtyWorkspaceService` exists.
* `ConsultationSpecialtyQuickActionRegistry` exists.
* Doctor preference service/controller/routes exist or existing equivalents are extended.
* Workspace payload includes `doctorSpecialtyWorkspace`.
* Doctor workspace header appears compactly in consultation workspace.
* Quick actions are specialty-aware and safe.
* Quick actions navigate/open UI only; they do not directly mutate clinical records.
* Pinned actions persist per doctor.
* Compact mode/layout preference persists per doctor.
* Metrics are lightweight and do not expose patient details.
* Alerts reflect readiness/order-set/summary state without duplicating the full readiness card.
* General medicine remains light and stable.
* Specialist profiles show relevant actions.
* Workspace degrades safely if metrics/preferences fail.
* Focused doctor workspace tests pass.
* Phase 1-8 focused tests still pass.
* Workspace stabilisation test still passes.
* View cache/build check passes.
* Phase 9 report is created.

Stop after Phase 9. Do not implement admin configuration UI, billing mapping, or dashboard integration yet.
