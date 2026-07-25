You are working inside the UHMS Laravel project.

Phase 14R.3 implemented the Obstetrics stage-aware Consultation ↔ Maternity integration, dark by default.

Current implementation includes:

- Two feature flags:
  - CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
  - CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false
- `pregnancy_profiles.dating_method`.
- `PregnancyDatingMethod`.
- `ObstetricConsultationContextService`.
- `ObstetricWorkspaceViewModel`.
- `ConsultationMaternitySpecialtyWriteGuard`.
- `ConsultationMaternityContextController`.
- Seven bridge/context actions:
  - link
  - confirm
  - relink
  - unlink
  - create profile
  - record ANC
  - start labor
- Maternity context ribbon partial.
- Stage-aware Maternity Context panel partial.
- Dual-permission enforcement.
- EN/FR localisation.
- Targeted Phase 14R.3 feature coverage.

Current verification:

- Phase 14R.3: 22 passed, 1 skipped.
- Consultation suite: 230 passed, 22 documented pre-existing failures.
- Maternity regression group: 47 passed, 1 documented pre-existing ANC failure.
- Zero new failures introduced.
- Maternity billing remains preview-only and disabled by default.

Important remaining gaps:

K1:
The Maternity ribbon and context panel exist, but are not included in the real `consultations/show.blade.php` workspace. Therefore, the clinician-facing feature is not yet reachable through the actual Consultation page.

K2:
All seven bridge/context actions currently bypass `consultationMutationContext()`.

That is acceptable for pure context-link operations that must remain possible after consultation completion:

- link
- confirm
- relink
- unlink

It is not the desired default for clinical record creation:

- create pregnancy profile
- record ANC
- start labor

Those three actions create or mutate longitudinal clinical records and must require an active/editable consultation when launched from the Consultation workspace.

K3:
The real query-count delta has not yet been measured at the actual consultation include point.

Now implement Phase 14R.3.1: Pilot Wiring and Clinical Mutation Boundary Hardening.

Goal:

Complete the real Obstetrics workspace integration, preserve dark-by-default rollout, enforce the correct completed-consultation mutation boundaries, and measure the actual workspace performance before beginning Gynaecology reconciliation.

This is a narrow closure/hardening phase.

Do not change Gynaecology.
Do not retarget order sets.
Do not add summary projection.
Do not add completion snapshots.
Do not change readiness.
Do not enable Maternity billing posting.
Do not run historical reconciliation.
Do not run `composer test:wide`.
Do not touch `docs/prompt.md`.

1. Wire the context view model into the actual Consultation workspace

Audit the real Consultation show-data assembly path.

Confirm whether workspace data is assembled through:

- `HandlesConsultationWorkspace`
- the Consultation show controller
- `DoctorSpecialtyWorkspaceService`
- another workspace composer/service

Use the existing architecture.

Do not resolve Maternity context inside Blade.

Required behavior:

- Determine the active specialty profile through the existing resolver.
- Only consider the Obstetrics Maternity context when:
  - the resolved specialty profile is Obstetrics, and
  - `CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true`
- When the flag is false:
  - do not call `ObstetricConsultationContextService`
  - do not call `ConsultationMaternityContextResolver`
  - do not add Maternity queries
  - preserve current view data exactly
- When enabled:
  - call `ObstetricConsultationContextService` once
  - pass the prepared `ObstetricWorkspaceViewModel` into the real consultation view
  - reuse that same instance for the ribbon and panel

Do not instantiate or call the context service separately for each partial.

2. Include the ribbon and panel in the real workspace

Wire the existing partials into the actual Consultation view.

Recommended placement:

A. Maternity context ribbon

Place:

- after the patient/visit/specialty workspace header
- before the main consultation specialty section content

The ribbon should not replace:

- patient banner
- specialty profile banner
- admission context
- visit status
- consultation session controls

B. Maternity Context panel

Place it within the established Consultation workspace panel/tab structure.

Preferred placement:

- after encounter/history sections
- before or near specialty maternity-shaped sections
- before the final summary/completion area

Do not create a second full-width page above all Consultation content.

Do not duplicate the patient banner.

Do not move existing sections during this narrow phase unless required to prevent a broken layout.

3. Preserve all three rollout modes

Mode A — disabled

```env
CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Expected:

No ribbon.
No Maternity panel.
No resolver call.
No additional Maternity workspace queries.
Existing specialty sections retain current behavior.

Mode B — projection pilot

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Expected:

Ribbon and context panel become visible.
Existing specialty fields remain editable.
Suggested/inferred context is visibly distinguished from explicit context.
Clinicians can review the projections safely before write protection is activated.

Mode C — guarded source-of-truth mode

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=true

Expected:

Ribbon and context panel are visible.
Write guard applies only to explicitly linked, valid Pregnancy Profile context.
Maternity-owned fields are blocked server-side.
Consultation-owned mixed-section fields remain writable.
Historical specialty entries remain visible and preserved.

The guard must remain impossible to activate while the workspace flag is false.

Separate context-link actions from clinical mutation actions

Introduce a clear action classification.

A. Context-link actions

These may remain usable on completed consultations, subject to permissions and existing domain validation:

link
confirm inferred profile
relink
unlink

Reason:

These actions maintain or correct the consultation’s relationship to an existing longitudinal record. Phase 14R.2 requires bridge links to survive and remain manageable after consultation completion.

Requirements:

Relink requires reason.
Unlink requires reason.
Completed consultation actions remain audited.
Link-role/history behavior remains unchanged.
No maternity clinical record is created by these actions.

B. Clinical mutation actions

These must require the Consultation to be actively editable when launched from the Consultation workspace:

create pregnancy profile
record ANC
start labor

Use the existing consultationMutationContext() or the narrowest reusable equivalent.

Requirements:

Started/active/unpaused consultation: allowed, subject to dual permissions.
Completed consultation: blocked from the Consultation workspace.
Paused consultation: follow existing consultation mutation policy.
Cancelled/invalid consultation: blocked.
User receives a localised explanation.
No Maternity record is created when blocked.
No bridge link is created when blocked.
No partial transaction remains.

Do not weaken the existing Consultation mutation guard.

Completed-consultation behavior matrix

Implement and document this matrix:

Action	Active consultation	Paused consultation	Completed consultation
View Maternity context	allowed	allowed	allowed
Link existing profile	allowed	allowed if current bridge policy permits	allowed
Confirm inferred profile	allowed	allowed if current bridge policy permits	allowed
Relink profile	allowed with reason	allowed with reason	allowed with reason
Unlink profile	allowed with reason	allowed with reason	allowed with reason
Create Pregnancy Profile	allowed with permissions	blocked according to consultation mutation policy	blocked
Record ANC	allowed with permissions	blocked according to consultation mutation policy	blocked
Start Labor	allowed with permissions	blocked according to consultation mutation policy	blocked
Open existing Maternity record	allowed	allowed	allowed

For a completed consultation, clinicians may still navigate to existing Maternity records.

Creating a new Pregnancy Profile, ANC Visit, or Labor Episode after completion should be done from the Maternity module or a new active consultation—not through the completed Consultation workspace.

Do not add a historical clinical-mutation override in this phase.

Protect against unsaved Consultation data loss

The link/create/ANC/Labor actions may navigate or submit outside the main specialty-entry form.

Audit the current workspace dirty-state behavior.

Required behavior:

Do not silently discard unsaved Consultation form changes.
Use the project’s existing dirty-form/navigation warning if one exists.
Preserve the originating Consultation URL in return parameters.
After a successful action, return to the same consultation and appropriate Maternity panel anchor/tab.
Validation failures should return to the same action form with inputs/errors.
Do not create duplicate browser history loops.

Do not invent a second JavaScript navigation framework.

Confirm read-only projection behavior in the live view

With an explicit profile and the write guard enabled, verify the real page behavior.

The workspace should show Maternity projections for:

gravida
para
abortions
living children
previous caesarean
LMP
EDD
gestational age
dating method
latest ANC vitals
fetal assessment
risk level/factors
labor state
delivery state
newborn state
postnatal state

Consultation-owned fields must remain available:

previous complications
fetal lie
risk action plan
complaints
HOPC
examination narrative
diagnoses
consultation plan
orders
notes

Do not remove historical specialty-entry displays.

Where a historical specialty entry conflicts with Maternity, label it clearly as:

Legacy Consultation Entry
not the current source of truth

Do not attempt reconciliation in this phase.

Add a pilot-mode visual marker

When:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

show a discreet admin/clinician-facing marker such as:

“Maternity Context pilot mode”
“Source projections are visible; legacy Obstetrics fields remain editable”

Do not show this marker to users who cannot view Maternity context.

When the write guard is enabled, replace it with:

“Maternity is the source of truth for linked pregnancy fields”

Keep this copy localised.

Performance measurement

Measure the real Consultation workspace query counts.

Use one stable fixture for each case:

Obstetrics consultation with workspace flag off.
Obstetrics consultation with workspace enabled and no context.
Obstetrics consultation with one inferred active profile.
Obstetrics consultation with an explicit profile + ANC + Labor/Delivery/Newborn/Postnatal records.
Non-Obstetrics consultation with workspace enabled globally.

Record:

total query count
Maternity-related query count if measurable
resolver invocation count
repeated/duplicate queries
view/component queries
response/render time if easy to measure consistently

Requirements:

Flag-off mode must add zero Maternity resolver calls.
Non-Obstetrics profiles must add zero Maternity resolver calls.
Enabled Obstetrics workspace must resolve once per request.
Ribbon and panel must not trigger their own queries.
No N+1 should be introduced by newborn collections or context candidates.
Candidate profile display must be eager-loaded and bounded.

Do not add persistent cross-request caching in this phase.

If the enabled query delta is high:

optimise eager loading
reuse overview results
collapse duplicate queries
retain request-level memoisation

Document the exact before/after counts.

Routes and middleware

Keep all seven existing Phase 14R.3 routes.

Do not rename routes.

Review middleware/action guards so that:

Context-link actions require:

authentication
Consultation access
bridge link/unlink permission
ability to view the target Pregnancy Profile

Clinical mutation actions require:

authentication
Consultation access
active/editable Consultation mutation context
bridge action permission
underlying Maternity permission

Do not allow the bridge permission to bypass underlying Maternity policies.

Activity logging

Retain existing activity logs.

For blocked clinical mutation attempts, follow existing project conventions.

Do not create noisy activity logs for every validation failure unless the project already does so.

Required successful logs remain:

PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION
ANC_VISIT_RECORDED_FROM_CONSULTATION
LABOR_EPISODE_STARTED_FROM_CONSULTATION

Context link/relink/unlink logs remain unchanged.

No clinical measurements or notes should be copied into log metadata.

Localisation

Extend EN/FR in strict parity for:

Maternity Context pilot mode
Legacy fields remain editable
Maternity source-of-truth mode
Clinical action unavailable on completed consultation
Clinical action unavailable while consultation is paused
Open existing Maternity record
Start a new consultation to record clinical data
Return to consultation
Unsaved consultation changes
Context linking remains available
Completed consultation context review
Workspace placement/section labels if required

Verify full recursive key parity.

Tests

Add or extend:

tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php

or create:

tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php

Required tests:

Real workspace integration:

Real consultation show page does not contain ribbon when flag is off.
Real consultation show page does not call resolver when flag is off.
Real non-Obstetrics consultation does not call resolver.
Real Obstetrics consultation renders ribbon when workspace flag is enabled.
Real Obstetrics consultation renders context panel when enabled.
Pilot marker renders when workspace enabled and guard disabled.
Source-of-truth marker renders when both flags are enabled.
Ribbon and panel consume the same view-model instance.
Components issue no independent database queries.

Context states on real page:

Explicit context renders full projection.
Inferred context renders suggested context and confirm action.
Ambiguous context renders candidate selector.
None preserves the current workspace.
Invalid context renders warning without breaking Consultation.

Mutation boundary:

Completed consultation can view linked context.
Completed consultation can link an existing same-patient profile.
Completed consultation can relink with reason.
Completed consultation can unlink with reason.
Completed consultation cannot create a Pregnancy Profile from Consultation.
Completed consultation cannot record ANC from Consultation.
Completed consultation cannot start Labor from Consultation.
Blocked actions create no maternity records.
Blocked actions create no bridge links.
Active editable consultation can still create profile/record ANC/start Labor.
Paused consultation follows the project’s existing mutation rules.

Data ownership:

Guarded live page does not render editable inputs for blocked Maternity-owned fields.
Guarded direct POST still rejects blocked fields.
previous_complications, lie, and action_plan remain writable.
Historical entries remain visible and unchanged.
Projection mode leaves legacy inputs editable.

Navigation:

Return URL preserves the same Consultation.
Successful ANC action returns to the Maternity context panel.
Unsaved-data protection is not bypassed where the current UI supports it.

Regression:

Existing Consultation completion remains unchanged.
Existing readiness remains unchanged.
Existing summary remains unchanged.
Existing orders remain unchanged.
Existing Maternity pages remain unchanged.
Billing remains preview-only.
No invoice item is created.

Run:

php artisan test tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php
php artisan test tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php
php artisan test tests/Feature/ConsultationMaternityBridgePhase14R2Test.php
php artisan test tests/Feature/MaternityFoundationPhase8Test.php
php artisan test tests/Feature/AntenatalCarePhase9Test.php
php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php
php artisan test tests/Feature/PostnatalCarePhase12Test.php
php artisan test tests/Feature/Consultations
php artisan route:list --name=consultation
php artisan route:list --name=maternity
php artisan view:clear
php artisan config:clear
git diff --check -- . ':!docs/prompt.md'

Run PHP syntax checks on all changed PHP, Blade and localisation files.

Baseline handling:

tests/Feature/Consultations may retain exactly 22 documented pre-existing failures.
AntenatalCarePhase9Test may retain exactly one documented pre-existing failure.
Record exact counts.
Phase 14R.3.1 must introduce zero new failures.

Do not run composer test:wide.

Manual pilot checks

Perform or document these manual checks:

A. Flags off

Open Obstetrics consultation.
Confirm no Maternity ribbon/panel.
Confirm current layout is unchanged.

B. Pilot mode

Enable workspace, leave guard off.
Open consultation with linked pregnancy.
Confirm ribbon placement is clinically useful.
Confirm context panel does not overcrowd the workspace.
Confirm current fields remain editable.
Confirm pilot marker is visible.

C. Guarded mode

Enable both flags.
Confirm Maternity-owned fields are projections/read-only.
Confirm allowed Consultation fields remain editable.
Record an ANC visit and confirm Maternity page/report sees the same record.
Start Labor and confirm no duplicate episode.

D. Completed consultation

Confirm Maternity context can be reviewed.
Confirm link correction is possible with permissions.
Confirm clinical creation actions are blocked.
Confirm links and historical entries remain visible.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md

The report must include:

Real include point selected.
Files changed.
Flag-off behavior.
Pilot-mode behavior.
Guarded-mode behavior.
Completed-consultation action matrix.
Mutation-boundary implementation.
Unsaved-data behavior.
Actual query counts for all measured fixtures.
Resolver invocation counts.
Layout placement rationale.
Permissions/middleware behavior.
Localisation.
Tests/checks run.
Baseline comparison.
Existing workflows protected.
Known risks.
Pilot rollout instructions.
Rollback instructions.
Next recommended phase.

Update:

docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md
docs/maternity/OBGYN_MATERNITY_OBSTETRICS_WORKSPACE_PHASE_14R_3_REPORT.md

Mark K1 closed only after the real consultation view renders the components in pilot mode.

Rollout

Initial state remains:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Pilot:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Only enable guarded mode after clinician review:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=true

Rollback order:

Disable write guard.
Disable workspace.
Clear config cache.
Confirm existing Consultation fields are editable again.

No destructive migration rollback should be required.

Boundaries

Do not change Gynaecology.
Do not auto-sync Gynaecology LMP.
Do not retarget order sets.
Do not convert current_pregnancy.
Do not convert birth_plan.
Do not add summary projection.
Do not add immutable completion snapshots.
Do not add readiness rules.
Do not run historical reconciliation.
Do not enable Maternity billing posting.
Do not add a billing card to Consultation.
Do not modify consultation billing.
Do not rewrite Maternity services.
Do not rename routes.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Include point and UI placement.
Mutation-boundary behavior.
Completed-consultation action matrix.
Feature-flag modes.
Query counts.
Tests and baseline comparison.
Existing workflows protected.
Known risks.
Rollout and rollback.
Next phase recommendation.

Next phase after Phase 14R.3.1 passes:

Phase 14R.4 — Gynaecology Separation, Explicit Pregnancy Transition and Order-Set Retargeting.
