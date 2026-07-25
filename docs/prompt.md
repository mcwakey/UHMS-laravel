You are working inside the UHMS Laravel project.

Phase 14R.2 is complete.

The Consultation ↔ Maternity bridge now includes:

- `consultation_maternity_links`.
- Explicit nullable foreign keys for the seven supported Maternity contexts.
- `ConsultationMaternityContextType`.
- `ConsultationMaternityLinkRole`.
- `ConsultationMaternityLink`.
- `ConsultationMaternityContext`.
- `ConsultationMaternityContextResolver`.
- `ConsultationMaternityLinkService`.
- Link/relink/unlink activity logging.
- Additive permissions:
  - `consultation.maternity_context.view`
  - `consultation.maternity_context.link`
  - `consultation.maternity_context.unlink`
- EN/FR bridge localisation.
- 22 passing bridge tests.

The resolver order is:

1. Explicit active consultation link.
2. Same visit.
3. Same admission.
4. A single active pregnancy profile.
5. None.

Multiple candidates return `ambiguous`.
The resolver never persists a link, creates a profile, starts a maternity workflow, or infers pregnancy from sex, complaints, diagnoses, specialty or pregnancy-test results.

Important existing baseline:

- Bridge tests: 22 passed, 63 assertions.
- Bridge + Maternity Foundation + Labor/Delivery: 36 passed, zero failures.
- `tests/Feature/Consultations`: 230 passed and 22 pre-existing failures.
- `AntenatalCarePhase9Test`: 4 passed and 1 pre-existing failure.
- These failures were reproduced after physically removing every Phase 14R.2 change.
- Phase 14R.3 must introduce zero new failures beyond those documented baselines.

Now implement Phase 14R.3: Obstetrics Stage-Aware Workspace Integration.

Goal:

Make the Obstetrics consultation workspace aware of the longitudinal Maternity context and stop new duplicate maternity-data writes when explicitly enabled, while preserving the existing Consultation workflow, Maternity workflow, historical specialty entries, consultation completion, readiness, orders and billing posture.

This is the first clinician-facing reconciliation phase.

The rollout must be reversible through feature flags.

Do not modify the Gynaecology workspace in this phase.
Do not retarget order sets in this phase.
Do not implement consultation summary projections or completion snapshots.
Do not implement historical reconciliation or backfills.
Do not enable Maternity billing posting.
Do not run `composer test:wide`.
Do not touch `docs/prompt.md`.

1. Add two independent feature flags

Add safe configuration, following the existing consultation config structure.

Recommended configuration:

```php
'maternity_context' => [
    'obstetrics_workspace_enabled' => env(
        'CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED',
        false
    ),

    'obstetrics_write_guard_enabled' => env(
        'CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED',
        false
    ),
],


Required behavior:

A. Both flags false

The Obstetrics workspace behaves exactly as it does today.
No Maternity ribbon or contextual panel is shown.
Existing specialty sections remain unchanged.
The context resolver should not be called unnecessarily.
There must be no additional hot-path query cost.

B. Workspace enabled, write guard disabled

Show the Maternity context ribbon and read-only source projections.
Existing specialty fields remain editable.
This is the safe pilot mode for clinician review.
Clearly label the new information as coming from Maternity.
Do not silently replace or hide existing fields.

C. Workspace enabled, write guard enabled

Show the ribbon and projections.
When an explicit Maternity context is linked, prevent new writes to fields owned by Maternity according to the approved matrix.
Preserve all Consultation-owned fields.
Preserve all existing specialty entries as historical evidence.
Do not delete or rewrite old specialty entries.

The write guard must never be active unless the workspace feature is also enabled.

Both flags must default to false.

Audit the actual Obstetrics workspace integration point

Before changing the workspace, confirm:

How the active specialty profile is supplied to the consultation view.
Where HandlesConsultationWorkspace or the equivalent workspace composer assembles view data.
How ConsultationSpecialtyLayoutService controls section ordering and presentation.
How specialty section forms submit to ConsultationSpecialtyEntryService.
How canonical section aliases are applied.
How the doctor workspace renders specialty sections.
How the simplified and full consultation views differ, if both exist.
How workspace dirty-state or unsaved-data warnings currently work.
How the current URL/department prefix is preserved.

Use the existing consultation workspace architecture.
Do not create a second Obstetrics page or parallel consultation route.

Add Pregnancy Profile dating method

The approved source-of-truth decision makes dating_method Pregnancy-Profile-owned.

Add an additive nullable column to pregnancy_profiles:

dating_method

Add a typed enum following project convention:

PregnancyDatingMethod

Recommended values:

lmp
early_ultrasound
late_ultrasound
assisted_reproduction
clinical_estimate
unknown

Requirements:

Existing pregnancy profiles remain valid.
Do not backfill historical records automatically.
Update PregnancyProfile casting/fillable behavior.
Update PregnancyProfileService so create/update actions can accept a dating method.
Show the dating method on the Maternity pregnancy profile and Obstetrics projection where appropriate.
Do not implement the Gynaecology “Use this LMP for pregnancy dating” action yet. That belongs to Phase 14R.4.
Do not change current LMP/EDD calculation behavior except where required to store the selected method.
Add ObstetricConsultationContextService

Create an orchestration service such as:

app/Services/Consultation/Maternity/ObstetricConsultationContextService.php

Its responsibility is to prepare a complete, bounded view model for the Obstetrics workspace.

It should consume:

ConsultationMaternityContextResolver
ConsultationMaternityLinkService
PregnancyProfileService
MaternityOverviewService
AntenatalOverviewService
LaborOverviewService
NewbornOverviewService
PostnatalOverviewService
Existing admission/ward context services where available

It must not recreate Maternity business logic.

The service should return a typed view model containing:

Feature-flag state.
Resolver status.
Resolution source.
Whether context is explicit or inferred.
Pregnancy profile.
Pregnancy dating source/method.
Gestational age and its source.
EDD.
Risk level.
Latest ANC visit.
Next ANC date.
Current maternity case.
Current admission.
Ward and bed.
Active labor episode.
Latest labor observation.
Delivery record.
Delivery status.
Newborn records.
Newborn pending/completion state.
Postnatal case.
Postnatal readiness.
Context warnings.
Available actions.
Field/section write policy.
Candidate pregnancy profiles for ambiguity.
Return URLs to the originating consultation.

Request performance requirements:

Resolve the Maternity context once per workspace request.
Memoise the result for the request.
Eager-load all required relationships.
Blade components must not independently query the database.
Capture and document the consultation workspace query count before and after enabling the feature.
Do not add one resolver invocation per component or per section.
Define explicit, inferred, ambiguous, none and invalid UI behavior

A. Explicit context

When active bridge links exist:

Show the full Maternity context ribbon.
Enable relevant Maternity actions when permissions allow.
The write guard may apply when its feature flag is enabled.
Label the context as explicitly linked.

B. Inferred context

When the resolver finds context through same visit, same admission or one active profile but no explicit bridge link exists:

Show a clearly labelled “Suggested Maternity Context”.
Show the resolution source.
Provide an explicit “Confirm and Link” action.
Do not silently persist the link.
Do not apply the specialty write guard yet.
Do not allow ANC or labor mutations through the Consultation bridge until the clinician confirms the link.
Existing Obstetrics sections remain editable.

C. Ambiguous context

When multiple candidate pregnancy profiles exist:

Show a warning.
Show an explicit profile selector.
Display sufficient identifying context:
profile status
LMP
EDD
gestational age
created date
linked visit/admission where available
Never choose the latest automatically.
Do not display a full stage projection until the clinician selects and links a profile.
Do not apply the write guard.
Do not start ANC or labor.

D. No context

When no context exists:

Do not show the full Maternity ribbon.
Do not display an intrusive pregnancy prompt.
Preserve the current Obstetrics workspace.
Provide a discreet, permission-controlled “Link or Create Pregnancy Profile” action in the appropriate workspace action menu.
Do not create a profile automatically.

E. Invalid context

When an explicit link is inconsistent or broken:

Show a clear warning.
Disable Maternity mutation actions.
Do not fall through silently to another inferred context.
Existing consultation work must remain usable.
Provide an admin/authorised relink action where appropriate.
Add Maternity context ribbon

Add a reusable read-only component such as:

resources/views/consultations/partials/maternity/context-ribbon.blade.php

The ribbon should display, when available:

Maternity context source:
explicit
same visit
same admission
single active profile
Pregnancy profile status.
Gestational age.
Gestational-age source.
Dating method.
EDD.
Risk level.
Latest ANC date.
Next ANC date.
Admission status.
Ward and bed.
Current labor stage.
Delivery status.
Newborn records pending/complete.
Postnatal status/readiness.

Use compact, responsive badges/cards.
Do not overload the primary patient banner.
Do not conflate department, specialty and Maternity stage.

Add a dedicated Maternity context panel

Use existing consultation workspace panel/tab conventions.

Do not replace the general Consultation tabs.

Add one dedicated “Maternity Context” panel with stage-aware subsections:

Pregnancy
ANC
Labor and Delivery
Newborn
Postnatal

Only show subsections relevant to the resolved context.

The default behavior is projection plus explicit action, not another set of duplicate forms.

Shared components should include, where practical:

pregnancy summary card
ANC summary card
labor summary card
delivery summary card
newborn summary card
postnatal summary card
context selector
context warning
Maternity quick actions

Components must consume the prepared view model.
They must not implement their own Maternity calculations or queries.

Add explicit link, confirm and relink actions

Add controller/routes using the existing Consultation route conventions.

Required actions:

Link an existing pregnancy profile.
Confirm an inferred pregnancy profile.
Relink to a different profile.
Unlink the active pregnancy-profile context.
View/select candidate profiles.

All writes must use ConsultationMaternityLinkService.

Rules:

Only same-patient pregnancy profiles may be offered.
Relink and unlink require a reason.
Same-target linking remains idempotent.
Multiple profiles require explicit selection.
Preserve the existing consultation route and workspace.
Return to the originating consultation after the action.
Respect existing workspace dirty-state behavior; linking must not silently discard unsaved consultation data.
Use link_role=primary for an explicitly selected existing active profile.
Use link_role=reviewed when confirming a profile for review context.
Do not invent new link-service behavior outside the existing service.
Add explicit pregnancy profile creation from Obstetrics

Add permission:

consultation.maternity_context.create_profile

The action also requires:

maternity.pregnancy.create

Both permissions must pass.

Implementation requirements:

Reuse the existing Maternity pregnancy-profile validation and service.
Do not maintain a second pregnancy-profile form schema in Consultation.
Prefer reusing or sharing the existing Maternity form/request/view partial.
Preserve the consultation return URL.
On successful profile creation:
create the Pregnancy Profile through PregnancyProfileService
link it through ConsultationMaternityLinkService
use link_role=created
Coordinate creation and linking transactionally where the existing services allow.
Do not create a profile merely because the Obstetrics workspace was opened.
Do not infer profile values from specialty JSON automatically.
Do not migrate old specialty entries.
Do not use Gynaecology LMP in this phase.
Add explicit ANC recording from Obstetrics

Add permission:

consultation.maternity_context.record_anc

The action also requires:

maternity.anc.record

Both permissions must pass.

Required behavior:

An explicit linked Pregnancy Profile is required.
Inferred but unconfirmed context is insufficient.
Reuse the existing ANC form, request validation and AntenatalVisitService.
Do not duplicate ANC validation or risk logic inside Consultation.
Preserve the consultation route as the return context.
Carry available visit, admission and department context safely.
Creating ANC from Consultation must create exactly one AntenatalVisit.
It must not create:
antenatal_vitals specialty entries
fetal_assessment specialty entries
duplicated risk-assessment specialty data
After creation:
link the ANC visit to the consultation using link_role=created
refresh the Maternity projection
keep the Pregnancy Profile link active
Do not add an automatic billing action.
Do not introduce a billing card into the doctor consultation workspace.
Use existing request-idempotency conventions where available.
Do not add a database uniqueness rule that would block legitimate repeat ANC visits.
Add explicit Labor start/open action

Add permission:

consultation.maternity_context.start_labor

The action also requires:

maternity.labor.start

Both permissions must pass.

Required behavior:

An explicit linked Pregnancy Profile is required.
If an active Labor Episode already exists:
do not create another
navigate to/open the existing Labor workspace
Otherwise:
start labor through LaborEpisodeService
carry visit/admission/department/ANC context where safe
link the created Labor Episode using link_role=created
Never start labor automatically from:
danger signs
diagnosis
gestational age
pregnancy profile status
emergency flag
Do not duplicate labor fields in specialty entries.
Add read-only Labor, Delivery, Newborn and Postnatal projections

When source records exist, show:

Labor:

Labor stage.
Status.
Latest observation time.
Cervical dilation if available.
Fetal heart rate.
Maternal observations.
Escalation flags.
Link to Labor workspace.

Delivery:

Delivery date/time.
Delivery mode.
Outcome.
Maternal condition.
Newborn count.
Link to Delivery record.

Newborn:

Newborn count.
Birth order.
Sex.
Birth weight.
APGAR.
Outcome/status.
Records pending/complete.
Links to Newborn records.

Postnatal:

Case status.
Mother readiness.
Newborn readiness.
Ready-for-discharge state.
Latest mother/newborn observation time.
Referral/follow-up status.
Link to Postnatal workspace.

These are projections only.

Do not create or update Delivery, Newborn or Postnatal records from this phase’s Consultation workspace.

Convert Obstetrics fields according to the approved matrix

The conversion must be field-level, not a blunt section disable.

Only apply this behavior when:

Obstetrics specialty profile is active.
Workspace flag is enabled.
Write-guard flag is enabled.
An explicit valid Pregnancy Profile context is linked.

When any of those conditions are false, preserve existing behavior.

A. obstetric_history

Read-only Maternity projections:

gravida
para
abortions
living_children
previous_c_section → map explicitly to previous_caesarean

Remain Consultation-owned and writable:

previous_complications

Do not overwrite or delete historical specialty-entry values.

B. lmp_edd_gestational_age

Read-only Pregnancy Profile projections:

lmp
edd
gestational_age_weeks
gestational_age_days
dating_method

No independent Consultation writes when explicitly linked and the guard is enabled.

C. antenatal_vitals

Replace write fields with:

Latest ANC read-only summary.
“Record ANC Visit” action.

Guard all direct specialty writes for:

blood_pressure
weight
temperature
pulse
urine_protein
urine_glucose

D. fetal_assessment

Read-only ANC projections:

fundal_height
fetal_heart_rate
fetal_movement
presentation

Remain Consultation-owned and writable for now:

lie

Reason:
AntenatalVisit currently has no lie column and ownership remains unresolved. Do not silently add it to ANC in this phase.

E. risk_assessment

Read-only Maternity projections:

risk_level
risk_factors

Remain Consultation-owned and writable:

action_plan

Risk changes should be performed through the Maternity Pregnancy Profile/ANC workflows, not a duplicate Consultation field.

F. Deferred sections

Do not convert these in Phase 14R.3:

current_pregnancy
birth_plan
Gynaecology obstetric_history
Order-set patch targets

They remain for Phase 14R.4 because order-set actions currently target them.

Add a server-side specialty write guard

UI read-only fields are not enough.

Create a policy/service such as:

ConsultationMaternitySpecialtyWriteGuard

It should be called by the actual specialty-entry write path.

It must:

Apply only to the Obstetrics profile.
Apply only when both flags permit it.
Apply only with an explicit valid Pregnancy Profile link.
Use the approved field-level ownership matrix.
Reject attempts to write Maternity-owned fields through direct HTTP/API manipulation.
Return localised validation/domain errors.
Preserve allowed Consultation-owned fields in mixed sections.
Never silently discard a submitted blocked field.
Never delete historical entries.
Never modify non-Obstetrics profiles.

Mixed-section behavior:

obstetric_history.previous_complications remains writable.
fetal_assessment.lie remains writable.
risk_assessment.action_plan remains writable.
Maternity-owned siblings are not submitted by the UI and are rejected server-side if manually supplied.

Existing historical entries:

Continue to render in a clearly labelled, read-only “Legacy Consultation Entry” area where appropriate.
Do not use them as the current Maternity source of truth.
Do not overwrite them.
Do not delete them.
Keep current Consultation behavior unchanged

This phase must not change:

Complaints.
HOPC.
General or specialty examination narrative.
Diagnoses.
Consultation plan.
Investigations.
Prescriptions.
Procedures.
Clinical tasks.
Clinician notes.
Existing consultation completion.
Existing readiness rules.
Existing final summary behavior.
Existing consultation billing.
Any non-Obstetrics specialty profile.

No new readiness blocker is allowed in this phase.

No Maternity Context summary projection or completion snapshot is allowed yet.

Do not change billing behavior

Phase 14.1 remains preview-only.

Confirm:

MATERNITY_BILLING_ENABLED=false by default.
MaternityBillingPostingService::postForSource() remains non-posting.
No invoice item is created by:
linking a profile
creating a profile
recording ANC
starting labor
viewing Maternity projections
No billing card appears inside the normal doctor Consultation workspace.
Do not continue Phase 14.2 manual posting.
Add permissions additively

Add:

consultation.maternity_context.create_profile
consultation.maternity_context.record_anc
consultation.maternity_context.start_labor

Retain:

consultation.maternity_context.view
consultation.maternity_context.link
consultation.maternity_context.unlink

Dual-permission requirements:

Create profile:

bridge create_profile
Maternity maternity.pregnancy.create

Record ANC:

bridge record_anc
Maternity maternity.anc.record

Start labor:

bridge start_labor
Maternity maternity.labor.start

The bridge must never grant access to a Maternity operation the user otherwise lacks.

Suggested roles:

Admin/Super Admin: all.
Obstetrics doctors/appropriately configured doctors:
view
link
create profile where permitted
record ANC where permitted
start labor where permitted
Maternity clinicians receive actions only where their Consultation and Maternity permissions already overlap.
Reception receives no clinical ANC/labor actions.

Do not remove existing permissions.

Add EN/FR localisation

Extend consultation_maternity.php in strict parity.

Include keys for:

Maternity context ribbon.
Suggested Maternity context.
Explicitly linked context.
Confirm and link.
Select pregnancy profile.
Create pregnancy profile.
Multiple active pregnancy profiles.
No active pregnancy profile.
Invalid Maternity context.
Source: Maternity.
Source: same visit.
Source: same admission.
Source: active profile.
Gestational-age source.
Dating methods.
Pregnancy summary.
ANC summary.
Record ANC visit.
Labor and delivery.
Start labor episode.
Open labor workspace.
Delivery summary.
Newborn summary.
Postnatal summary.
Legacy Consultation entry.
Read-only because Maternity is the source of truth.
Direct specialty write blocked.
Underlying Maternity permission required.
Workspace feature disabled.
Write guard disabled/pilot mode.
Action success/error messages.
Activity logging

The existing link service already logs link/relink/unlink.

Add orchestration-level logs where useful:

PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION
ANC_VISIT_RECORDED_FROM_CONSULTATION
LABOR_EPISODE_STARTED_FROM_CONSULTATION

Metadata should include only:

consultation route ID
pregnancy profile ID
ANC/labor record ID
visit/admission IDs
actor
timestamps

Do not place clinical measurements or notes into activity metadata.

Tests

Add:

tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php

Required tests:

Feature flags:

Both flags off preserve the current Obstetrics workspace.
Workspace flag off does not render a ribbon.
Workspace enabled/write guard disabled renders projections but preserves editable sections.
Write guard never activates unless workspace flag is active.

Context states:

No context loads the Obstetrics workspace normally.
No context does not auto-create a profile.
Inferred context is labelled suggested and is not persisted.
Inferred context does not activate the write guard.
Confirming inferred context creates an explicit link.
Ambiguous context shows candidate selection.
Ambiguous context never auto-selects.
Invalid explicit context disables Maternity actions.

Ribbon/projections:

Explicit linked profile renders LMP, EDD, GA, dating method and risk.
Latest ANC and next-visit data render.
Admission/ward/bed render when admitted.
Labor, delivery, newborn and postnatal summaries render from source records.
Projection rendering creates no specialty entries.

Link/profile actions:

Existing same-patient profile can be linked.
Mismatched patient profile cannot be linked.
Relink requires a reason.
Unlink requires a reason.
Pregnancy profile can be created by a user with both permissions.
Profile creation without either required permission is blocked.
Created profile is linked with role created.
Opening Obstetrics alone never creates a profile.

ANC action:

ANC recorded from Consultation creates exactly one AntenatalVisit.
ANC action links the ANC visit to the consultation.
ANC action creates no antenatal_vitals specialty entry.
ANC action creates no fetal_assessment specialty entry.
ANC action creates no duplicate risk entry.
Inferred but unconfirmed context cannot record ANC.
Missing underlying Maternity ANC permission blocks the action.

Labor action:

Start Labor creates exactly one LaborEpisode.
Existing active Labor Episode is opened/reused rather than duplicated.
Labor action links the episode to the consultation.
No labor specialty entry is created.
Missing underlying Maternity labor permission blocks the action.

Write guard:

Linked Obstetrics consultation cannot write gravida/para/abortions/living children/previous caesarean through specialty JSON when guard is enabled.
previous_complications remains writable.
Linked consultation cannot write LMP/EDD/GA/dating method through specialty JSON.
Linked consultation cannot write ANC vitals through specialty JSON.
Linked consultation cannot write fundal height/FHR/fetal movement/presentation through specialty JSON.
lie remains writable.
Linked consultation cannot write risk level/risk factors through specialty JSON.
action_plan remains writable.
Existing historical entries remain in the database.
Non-Obstetrics specialty entry writing remains unchanged.
Guard disabled preserves current write behavior.

Regression:

Existing Consultation completion still works.
Existing readiness remains unchanged.
Existing summary remains unchanged.
Existing investigation/prescription/procedure/task behavior remains unchanged.
Maternity billing stays preview-only.
No billing card is rendered in the doctor workspace.
EN/FR key parity passes.
Resolver is invoked only once per workspace request where practical to assert.
No Blade component performs direct queries.

Run targeted checks:

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

Run PHP syntax checks on all changed PHP and localisation files.

Known baseline treatment:

tests/Feature/Consultations may retain the documented 22 pre-existing failures.
AntenatalCarePhase9Test may retain its one documented pre-existing failure.
Re-run and compare exact counts.
Do not claim those failures were introduced by this phase if the baseline remains identical.
Phase 14R.3 must introduce zero new failures.

Do not run composer test:wide.

Performance verification

Because the resolver may query six Maternity tables during fallback:

Measure the Obstetrics workspace before and after query counts using a stable fixture.
Confirm the context resolver is called once.
Confirm the context service preloads all projection data.
Confirm individual cards do not query.
Document the query delta.
If the resolver is measurably expensive, add request-scope memoisation or safe per-request caching.
Do not introduce persistent cross-request caching that could show stale clinical information in this phase.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_OBSTETRICS_WORKSPACE_PHASE_14R_3_REPORT.md

The report must include:

What was implemented.
Files changed.
Feature flags and rollout modes.
Pregnancy dating-method implementation.
Context service/view model.
Resolver-state UI behavior.
Ribbon behavior.
Maternity context panel behavior.
Explicit link/create/confirm behavior.
ANC action behavior.
Labor action behavior.
Read-only projection behavior.
Field-level write-guard matrix.
Historical-entry behavior.
Dual-permission behavior.
Localisation.
Activity logging.
Query-count/performance results.
Tests/checks run.
Existing baselines and whether counts changed.
Existing workflows protected.
Known risks.
Intentionally deferred items.
Rollback instructions.
Next phase recommendation.

Update:

docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Mark Phase 14R.3 implemented only after all targeted tests pass and no new baseline failures are introduced.

Rollout and rollback

Initial deployment:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Pilot projection mode:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Guarded source-of-truth mode:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=true
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=true

Do not enable the write guard in an environment until:

The count of existing Maternity-owned specialty entries has been measured.
Existing duplicate entries have been reviewed or accepted as preserved legacy data.
Clinicians have reviewed projection mode.
Required Maternity permissions are assigned.
The ANC and Labor actions have been manually tested.

Rollback:

Disable the write-guard flag first.
If needed, disable the workspace flag.
Existing Consultation fields and entries remain intact.
Existing Maternity records remain intact.
Bridge links remain historical/auditable.
No destructive migration rollback should be required.
Boundaries

Do not change the Gynaecology workspace.
Do not auto-sync Gynaecology LMP.
Do not retarget order sets.
Do not convert current_pregnancy or birth_plan.
Do not implement summary projection.
Do not implement immutable completion snapshots.
Do not implement readiness warnings/enforcement.
Do not implement historical reconciliation/backfill.
Do not enable Maternity billing posting.
Do not change Consultation billing.
Do not add a doctor-workspace billing card.
Do not change order, prescription, procedure or task engines.
Do not rewrite Maternity services.
Do not rename existing routes.
Do not modify default seeders with manual test data.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide a concise completion report with:

Summary.
Files changed.
Feature flags.
UI behavior.
Profile/ANC/Labor actions.
Write-guard behavior.
Permissions/localisation.
Query-count change.
Tests/checks run.
Baseline comparison.
Existing workflows protected.
Known risks.
Deferred work.
Rollback procedure.
Next phase recommendation.

Recommended next phase:

Phase 14R.4 — Gynaecology Separation, Explicit Pregnancy Transition and Order-Set Retargeting.
