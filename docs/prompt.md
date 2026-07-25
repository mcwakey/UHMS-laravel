You are working inside the UHMS Laravel project.

Completed reconciliation phases:

- Phase 14R.1:
  - O&G ↔ Maternity audit.
  - Field-level source-of-truth matrix.
  - Integration architecture.

- Phase 14R.2:
  - `consultation_maternity_links`.
  - Explicit-FK bridge.
  - Context resolver.
  - Link/relink/unlink service.
  - Bridge permissions and activity logging.

- Phase 14R.3:
  - Obstetrics stage-aware workspace.
  - Pregnancy dating method.
  - Context ribbon and stage-aware panel.
  - Explicit create-profile, record-ANC and start-labor actions.
  - Server-side field-level specialty write guard.

- Phase 14R.3.1:
  - Ribbon and panel wired into the real consultation workspace.
  - Context-link actions separated from clinical mutation actions.
  - Clinical mutations now use the existing consultation mutation guard.
  - Query counts measured and optimised.
  - K1, K2 and K3 closed.

Current rollout remains dark by default:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false

Current verification baseline:

- Phase 14R.3.1: 10 passed.
- Bridge + 14R.3 + 14R.3.1: 54 passed, 1 skipped.
- Maternity regression group: 78 passed, 1 documented pre-existing ANC failure.
- `tests/Feature/Consultations`: 230 passed, 22 documented pre-existing failures.
- Zero new failures introduced through Phase 14R.3.1.

Approved source-of-truth decisions:

R2:
- Gynaecology `menstrual_history.lmp` remains Consultation-owned.
- It must never automatically synchronise to
  `pregnancy_profiles.last_menstrual_period`.
- A one-way, explicit, clinician-confirmed adoption action is approved.

R4:
- Gynaecology `obstetric_history` remains writable encounter history when no
  Pregnancy Profile is explicitly linked.
- When a Pregnancy Profile is explicitly linked, gravida, para, abortions,
  living children and previous caesarean become read-only Maternity projections.
- Existing Consultation entries remain preserved.

R5:
- Order-set actions currently patching Maternity-shaped specialty fields must
  be retargeted in this phase.

R6:
- Immutable completion-time Maternity Context snapshots remain deferred to
  Phase 14R.6.

Now implement Phase 14R.4:

Gynaecology Separation, Explicit Pregnancy Transition and Order-Set Retargeting.

Goal:

Keep Gynaecology a non-pregnancy reproductive-health workspace by default,
while allowing a clinician to explicitly create or link a Pregnancy Profile
without changing the current Gynaecology consultation into Obstetrics.

At the same time:

- Implement the approved one-way LMP adoption workflow.
- Convert linked Gynaecology obstetric history into Maternity projections.
- Finish the unambiguous Obstetrics `current_pregnancy` and `birth_plan`
  source-of-truth conversion.
- Retarget seeded order-set actions that currently write Maternity-owned
  specialty JSON.
- Harden every runtime specialty-entry write path so order sets and other
  automations cannot bypass the Maternity write guard.

This phase must not:

- Automatically create a Pregnancy Profile.
- Automatically link an existing Pregnancy Profile.
- Automatically switch Gynaecology to Obstetrics.
- Automatically start ANC, Labor, Delivery or Postnatal.
- Rewrite the current Gynaecology session.
- Delete or rewrite historical specialty entries.
- Change consultation completion, readiness or summary behavior.
- Enable Maternity billing posting.
- Run historical reconciliation or backfills.
- Run `composer test:wide`.
- Touch `docs/prompt.md`.

1. Audit the actual Gynaecology workspace and order-set runtime first

Before implementation, inspect:

Gynaecology:

- The seeded Gynaecology specialty profile.
- All Gynaecology section keys and schemas.
- `menstrual_history`.
- `sexual_sti_history`.
- `obstetric_history`.
- Existing section aliases.
- Existing quick actions.
- Existing favourites.
- Existing order sets.
- Existing summary/readiness rules.
- The real Consultation show-data composer.
- The real specialty-entry persistence path.

Order sets:

- `ConsultationSpecialtyOrderSetSeeder`.
- Order-set models.
- Order-set action-type representation.
- Order-set application service.
- `patch_specialty_entry` execution.
- Application and application-item status handling.
- Existing manual/advisory action types, if any.
- Existing idempotency behavior.
- Existing order-set audit/history behavior.
- Every runtime call site capable of creating or updating a
  `ConsultationSpecialtyEntry`.

Produce an implementation-time inventory of every order-set item that targets:

- `current_pregnancy.pregnancy_confirmed`
- `current_pregnancy.danger_signs`
- `birth_plan.danger_signs_counseling`
- `birth_plan.next_visit_date`
- any field now guarded as Pregnancy Profile-owned or ANC-owned
- any other O&G Maternity-shaped field discovered during the audit

Do not assume the two already-known seeded items are the only bypasses.

2. Add independent Gynaecology feature flags

Extend `config/consultation.php` safely.

Recommended configuration:

```php
'maternity_context' => [
    // Existing Obstetrics flags remain unchanged.

    'gynaecology_context_enabled' => env(
        'CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED',
        false
    ),

    'gynaecology_write_guard_enabled' => env(
        'CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED',
        false
    ),
],

Required behavior:

A. Both flags false

Gynaecology behaves exactly as it does now.
No Maternity context card.
No Pregnancy Profile query.
No Maternity resolver invocation.
No changes to current section editability.
No new hot-path query cost.

B. Context enabled, write guard disabled

Explicitly linked Pregnancy Profiles may appear in a small context card.
Existing Gynaecology fields remain editable.
This is pilot mode.
No full Obstetrics ribbon or stage-aware panel is shown.

C. Context enabled, write guard enabled

The small context card remains.
Gynaecology obstetric_history Maternity-owned fields become projections
only when an explicit, valid Pregnancy Profile link exists.
Consultation-owned Gynaecology fields remain writable.

The Gynaecology write guard must be implemented as:

contextEnabled() && guardFlag

It must be impossible to activate the guard while the context feature is off.

The existing Obstetrics flags and behavior must remain independent.

Add explicit-only Gynaecology context resolution

Create:

app/Services/Consultation/Maternity/GynaecologyConsultationContextService.php

Create a typed view model such as:

GynaecologyWorkspaceViewModel

Gynaecology must not use the normal fallback chain automatically.

Required behavior:

Resolve explicit active bridge links only.
Do not infer context through:
same visit
same admission
one active Pregnancy Profile
patient sex
diagnosis
complaint
pregnancy test
If no explicit link exists, return a no-context view model.
Candidate Pregnancy Profiles are queried only when the clinician explicitly
opens the profile selector.
Reuse the existing bridge validation and explicit-link aggregation logic.
Do not duplicate explicit-link chain validation.

Preferred implementation:

Add a bounded resolveExplicitOnly() method to
ConsultationMaternityContextResolver, or
use a shared internal explicit-link resolution method already present.

Do not reimplement link consistency or patient ownership logic.

The Gynaecology view model should include:

feature-flag state
explicit-context status
Pregnancy Profile
profile status
LMP
EDD
gestational age
dating method
risk level
latest ANC date where useful
active Maternity stage badge where useful
Maternity profile URL
originating Consultation URL
action permissions
write policy
warnings
whether a saved Gynaecology LMP is available for explicit adoption
whether adoption is safe, idempotent or conflicting

Do not build the full Obstetrics stage-aware projection for Gynaecology.

Wire the Gynaecology context service into the real workspace

Use HandlesConsultationWorkspace, following the Phase 14R.3.1 pattern.

Requirements:

Identify the resolved Gynaecology specialty profile through the existing
specialty resolver.
When the Gynaecology context flag is false:
do not call the Gynaecology context service
do not query Maternity
When the profile is not Gynaecology:
do not call the Gynaecology context service
Build the Gynaecology view model once per request.
Pass it to Blade.
Do not resolve Maternity context inside Blade.
Do not call the full Obstetrics context service for Gynaecology.

Measure the actual query cost.

Add a small Gynaecology Pregnancy Context card

Add a reusable partial such as:

resources/views/consultations/partials/maternity/gynaecology-context-card.blade.php

Placement:

Within the existing Gynaecology specialty workspace.
Near specialty actions or obstetric history.
Not in place of the patient banner.
Not as a full Maternity ribbon.
Not as a full ANC/Labor/Postnatal panel.

When no explicit link exists:

Do not show the full card.
Show only a discreet permission-controlled action:
“Start or Link Pregnancy Workflow”
Do not display an intrusive pregnancy warning merely because the patient is
female or has a Gynaecology consultation.

When a saved positive pregnancy-test value exists:

A non-blocking action prompt may appear.
It must say that no Pregnancy Profile has been created.
It must never create a profile automatically.
It must never select an existing profile automatically.
It must never switch the specialty profile.

When an explicit link exists, show:

Explicitly linked Pregnancy Profile.
Profile status.
LMP.
EDD.
Gestational age.
Dating method.
Risk level.
Link to Maternity Pregnancy Profile.
Link/relink/unlink actions based on permission.
Explicit LMP adoption action when eligible.
A clearly labelled statement:
“This consultation remains Gynaecology.”

Do not show:

Record ANC action.
Start Labor action.
Delivery creation.
Newborn creation.
Postnatal mutation actions.

Those belong to Obstetrics/Maternity workflows.

Add explicit Start/Link Pregnancy Workflow actions

Use the existing Consultation–Maternity bridge and Pregnancy Profile service.

Required actions:

Select and link an existing same-patient Pregnancy Profile.
Create a new Pregnancy Profile explicitly.
Relink with reason.
Unlink with reason.
Open the linked Maternity Pregnancy Profile.

Rules:

The current consultation specialty remains Gynaecology.
Do not modify the profile mapping of the consultation.
Do not replace the current ConsultationSpecialtyProfile.
Do not move or delete any Gynaecology specialty entries.
Do not automatically start an Obstetrics consultation.
Do not automatically create an ANC visit.
Do not automatically create an admission request.
Preserve the originating Consultation URL.
Return to the Gynaecology Pregnancy Context card after success.
Respect the existing dirty-state/navigation protections.

Use existing permissions:

consultation.maternity_context.view
consultation.maternity_context.link
consultation.maternity_context.unlink
consultation.maternity_context.create_profile

Underlying Maternity permission remains required:

maternity.pregnancy.create

Clinical creation from Gynaecology must use mutableRoute() and therefore
requires an active/editable consultation.

Link/relink/unlink remain context-management actions and may remain available
after completion according to the approved Phase 14R.3.1 matrix.

Add explicit one-way Gynaecology LMP adoption

Add permission:

consultation.maternity_context.adopt_lmp

The action also requires:

maternity.pregnancy.update

Both permissions must pass.

Add a controller action such as:

adoptMenstrualLmp

This is a clinical mutation and must use mutableRoute().

Source rules:

The source LMP must be read server-side from the persisted
menstrual_history.lmp specialty entry for the same Consultation route.
Do not trust a client-submitted LMP value.
Use the actual Gynaecology schema/date format.
If there is no saved LMP, reject with a localised error.
An unsaved browser-form LMP cannot be adopted.
Preserve the Consultation LMP entry unchanged.

Target rules:

An explicit, valid Pregnancy Profile link is required.
If the Pregnancy Profile LMP is null:
update through PregnancyProfileService
set last_menstrual_period
set dating_method = lmp
allow the existing Maternity service to calculate EDD/GA as it normally does
If the Pregnancy Profile LMP already equals the Gynaecology LMP:
return idempotently
set dating_method = lmp only through explicit confirmation where safe
If the Pregnancy Profile has a different non-null LMP:
block adoption in this phase
do not overwrite
direct the clinician to review the Pregnancy Profile in Maternity
If the Pregnancy Profile dating method is:
early ultrasound
late ultrasound
assisted reproduction
then block LMP replacement in this phase
Do not implement an override path yet.
Do not recalculate or rewrite a scan-based dating record.

Creation flow:

When explicitly creating a new Pregnancy Profile from Gynaecology:

An optional unchecked checkbox may say:
“Use the saved Gynaecology LMP for pregnancy dating”
If selected:
derive the persisted LMP server-side
pass it to PregnancyProfileService
set dating_method = lmp
If not selected:
do not copy the Gynaecology LMP

Activity log:

GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING

Log identifiers and dates only where current privacy conventions permit.
Do not copy unrelated Gynaecology history into metadata.

Critical invariants:

Never sync Pregnancy Profile LMP back into menstrual_history.lmp.
Never update Gynaecology LMP when the Pregnancy Profile changes later.
Never adopt LMP merely because a profile was linked.
Never adopt from a completed/paused/unstarted consultation through this action.
Implement Gynaecology obstetric-history ownership

Extend the existing ConsultationMaternitySpecialtyWriteGuard.

Apply this Gynaecology behavior only when:

the active specialty profile is Gynaecology
Gynaecology context flag is enabled
Gynaecology write guard is enabled
an explicit, valid Pregnancy Profile is linked

When no explicit profile is linked:

obstetric_history remains editable encounter history.

When an explicit profile is linked:

Block direct specialty JSON writes for:

gravida
para
abortions
living_children
previous_c_section

Project read-only values from Pregnancy Profile:

gravida
para
abortions
living_children
previous_caesarean

Remain writable:

previous_complications

Rules:

menstrual_history.lmp always remains writable Consultation data.
No other Gynaecology section becomes Maternity-owned.
contraceptive_history remains unchanged.
sexual_sti_history remains unchanged.
pelvic_examination remains unchanged.
breast_examination remains unchanged.
A positive pregnancy test remains Consultation-owned.
Historical obstetric_history entries remain preserved and visible.
Blocked fields are rejected, never silently removed.
Pilot mode leaves the legacy fields editable.

Label linked projections as:

“Source: Maternity Pregnancy Profile”

Where historical data exists, label it:

“Legacy Gynaecology Consultation Entry”
Finish Obstetrics current_pregnancy conversion

Extend the Obstetrics write guard and projection UI.

Apply only under the existing Obstetrics guarded-mode conditions:

Obstetrics workspace flag enabled
Obstetrics write guard enabled
explicit valid Pregnancy Profile linked

A. pregnancy_confirmed

Target behavior:

Derived from the explicit Pregnancy Profile link.
Render as read-only:
profile linked / no profile linked
Block direct patch_specialty_entry or form writes.
The equivalent action is:
create/link Pregnancy Profile
Never create a profile because this field is true.

B. danger_signs

Target behavior:

When linked, show latest ANC danger signs as a Maternity projection.
Provide the existing Record ANC action.
Block direct specialty JSON writes when guarded.
Encounter-level complaints/assessment remain available for narrative capture.
Do not auto-create an ANC visit.

C. current_complaints

Remains Consultation-owned and writable.

D. high_risk_notes

Remains Consultation-owned narrative and writable.
Pregnancy risk level itself remains Maternity-owned.

E. booking_status

Keep Consultation-owned in this phase.
Do not add a Pregnancy Profile field without a separate approved schema decision.
Label it clearly as encounter/booking context rather than longitudinal truth.

Do not delete or rewrite existing current_pregnancy entries.

Finish Obstetrics birth_plan conversion

Apply only under existing Obstetrics guarded-mode conditions.

A. danger_signs_counseling

Latest ANC counselling may be shown as a read-only projection.
Equivalent mutation is Record ANC Visit/counselling through
AntenatalVisitService.
Block direct specialty JSON writes when guarded.
Do not silently create an ANC visit.

B. next_visit_date

Project from latest ANC next_visit_date.
Block direct specialty JSON writes when guarded.
Equivalent mutation is Record/Update ANC through Maternity.

C. planned_place

Keep Consultation-owned in this phase.
Label as “Consultation birth-plan intent”.
Do not synchronise it automatically to Labor or Delivery.

D. delivery_plan

Keep Consultation-owned in this phase.
Label as “Consultation delivery-plan intent”.
If a Labor Episode exists, show
labor_episodes.delivery_mode_planned separately as the Maternity/Labor value.
Never auto-sync either direction.

Do not delete or rewrite existing birth_plan entries.

Audit and close every specialty-entry write bypass

The current server-side guard is wired into the normal controller path.

That is not sufficient if other services write specialty entries directly.

Audit every runtime call site of:

ConsultationSpecialtyEntryService::createEntry()
ConsultationSpecialtyEntryService::updateEntry()
direct ConsultationSpecialtyEntry::create()
direct JSON updates to consultation_specialty_entries
order-set patch_specialty_entry
quick-action patching
favourite/template application
import/fixture application
any async Consultation specialty endpoint

Preferred architecture:

Move or expose the ownership check at a shared domain/service boundary so all
runtime writes pass through:

ConsultationMaternitySpecialtyWriteGuard

Possible implementation:

Add a common guarded persistence method in
ConsultationSpecialtyEntryService, or
add a required guard call in every sanctioned write executor

Requirements:

Normal form/controller writes remain guarded.
Order-set patch writes are guarded.
Quick-action or template patch writes are guarded.
Feature flags off preserve existing behavior.
Non-O&G profiles remain unchanged.
A stale/custom order set cannot write guarded fields when the guard applies.
No runtime path may bypass the source-of-truth policy.

Do not apply the guard to seed-time fixture creation unless the project
explicitly runs those fixtures as clinical runtime actions.

Document every audited call site and its final protection status.

Retarget Maternity-shaped order-set actions

Audit the actual order-set engine before adding a new action type.

Preferred order:

Reuse an existing manual/advisory/workflow-action type if one exists.
If none exists, add one minimal closed action type:
maternity_context_action

Supported action keys:

create_or_link_pregnancy_profile
record_anc_counselling

A maternity_context_action must:

never create a Pregnancy Profile automatically
never create an ANC visit automatically
never write a specialty entry
never change the consultation specialty
never post billing
create an explicit clinician-facing action/CTA only
retain order-set application audit and idempotency
require the clinician to perform the actual action with normal permissions

Retarget:

A. current_pregnancy.pregnancy_confirmed

From:

patch_specialty_entry

To:

maternity_context_action:
create_or_link_pregnancy_profile

Behavior:

If an explicit profile is already linked, show satisfied/already linked.
Otherwise present the explicit link/create action.
Do not persist pregnancy_confirmed=true.

B. birth_plan.danger_signs_counseling

From:

patch_specialty_entry

To:

maternity_context_action:
record_anc_counselling

Behavior:

If an explicit profile is linked and the clinician has ANC permission,
present Record ANC/counselling action.
If no explicit profile exists, present Create/Link Pregnancy Profile first.
Do not create an ANC visit automatically.
Do not patch the Consultation birth_plan.
The clinician may still document an encounter-level plan in the normal
Consultation plan/note fields.

If the existing engine cannot support an action_required state, use its
existing manual/skipped/advisory state with structured metadata rather than
inventing incompatible behavior.

Add an idempotent order-set revision

Do not mutate applied order-set history.

Create a dedicated idempotent revision/seeder following the project’s
configuration-seeder conventions.

Suggested name:

ConsultationSpecialtyOrderSetMaternityReconciliationSeeder

Requirements:

Identify system-seeded order sets/items by stable code/key.
Retarget only definitions that exactly match the known previous seeded
action payload.
Never overwrite an administrator-modified order-set item.
If an item differs from the old seeded payload:
leave it unchanged
classify it as needs review
include it in the Phase report
Preserve historical order-set applications and application items.
Do not rewrite past applications.
New applications use the retargeted action behavior.
Seeder is idempotent.
Seeder can run repeatedly without duplicating actions.
Do not run the manual Maternity test-data seeder.

Add a read-only audit service or command if practical, such as:

php artisan consultation:obgyn-order-set-audit

It should report:

order-set ID/code
item ID
profile
action type
target section/field
system-seeded vs modified where determinable
applied count
classification:
safe to retarget
already retargeted
custom/needs review
unsupported

The audit command must make no writes.

Preserve old order-set applications

Past order-set applications are medico-legal/audit history.

Requirements:

Do not modify old application rows.
Do not modify old application-item payloads.
Do not pretend an old patch_specialty_entry application used the new action.
Historical specialty entries created by old applications remain preserved.
New applications should record the new action type and result.
The UI should distinguish:
historical legacy patch
current explicit Maternity action
Positive pregnancy test behavior

Audit the actual sexual_sti_history.pregnancy_test options.

Do not assume its stored positive value.

When the persisted value represents positive:

Show a non-blocking action:
“Start or Link Pregnancy Workflow”
Do not:
create a Pregnancy Profile
link a Pregnancy Profile
switch to Obstetrics
create ANC
start Labor
create an admission request
change readiness
block consultation completion

The action is only an explicit clinician affordance.

When the test is negative/unknown/not recorded:

Do not show a pregnancy warning.
The standard discreet Start/Link action may remain available through the
normal specialty action menu.
No automatic specialty transition

After create/link/adopt-LMP actions:

The current consultation remains Gynaecology.
The current specialty profile remains Gynaecology.
Existing Gynaecology entries remain intact.
Existing Gynaecology readiness remains intact.
Existing final summary behavior remains intact.
No new Obstetrics consultation route is created automatically.

The context card may provide navigation to:

Maternity Pregnancy Profile.
Existing Obstetrics consultation if one already exists and is safe to link.
The normal Consultation referral/create workflow.

Creating a new Obstetrics route or a full referral/handoff belongs to Phase
14R.5 unless an existing service supports it with no new workflow design.

Permissions

Add:

consultation.maternity_context.adopt_lmp

Dual permission:

bridge permission above
maternity.pregnancy.update

Retain:

view
link
unlink
create_profile
record_anc
start_labor

Gynaecology must not expose ANC or Labor actions merely because the user holds
those permissions.

Suggested role behavior:

Admin/Super Admin: all.
Gynaecology clinician:
view
link/unlink where appropriate
create profile where underlying permission exists
adopt LMP where underlying update permission exists
Reception:
no LMP adoption
no clinical profile mutation unless current role policy already permits it
Clinical role grants must never escalate underlying Maternity access.

Do not remove any existing permission.

Activity logging

Add identifier-only activity events:

PREGNANCY_PROFILE_CREATED_FROM_GYNAECOLOGY_CONSULTATION
PREGNANCY_PROFILE_LINKED_FROM_GYNAECOLOGY_CONSULTATION
GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING
OBGYN_ORDER_SET_MATERNITY_ACTION_RETARGETED
OBGYN_ORDER_SET_MATERNITY_ACTION_PRESENTED
OBGYN_ORDER_SET_MATERNITY_ACTION_SATISFIED

Do not log:

sexual history
STI notes
full menstrual history
clinical notes
counselling text
test details beyond safe IDs/status where project policy permits

Do not produce a noisy log for every order-set audit read.

Localisation

Extend EN/FR in strict recursive parity.

Include keys for:

Gynaecology context:

Pregnancy workflow
Start or Link Pregnancy Workflow
This consultation remains Gynaecology
Explicitly linked Pregnancy Profile
No Pregnancy Profile linked
Positive pregnancy test recorded
No profile was created automatically
Open Maternity Pregnancy Profile
Consultation obstetric history
Maternity obstetric history
Legacy Gynaecology Consultation Entry
Source: Maternity Pregnancy Profile
Pilot mode
Source-of-truth mode

LMP adoption:

Use saved Gynaecology LMP for pregnancy dating
Adopt LMP
Confirm LMP adoption
Saved Consultation LMP
Pregnancy Profile LMP
LMP already matches
LMP conflict
Dating method prevents LMP replacement
Review Pregnancy Profile in Maternity
No saved LMP available
LMP adoption unavailable on completed consultation
LMP adopted successfully
One-way adoption warning
Later Maternity changes will not update this Consultation entry

Order sets:

Maternity workflow action required
Create or link Pregnancy Profile
Record ANC counselling
Already satisfied
Legacy specialty patch
Retargeted Maternity action
Custom order set needs review
No specialty entry was created
Underlying Maternity permission required

Obstetrics converted fields:

Pregnancy confirmed by linked profile
Latest ANC danger signs
Latest ANC counselling
Latest ANC next visit
Consultation booking context
Consultation birth-plan intent
Labor delivery plan
Tests

Add:

tests/Feature/ConsultationGynaecologyMaternityPhase14R4Test.php

Add:

tests/Feature/ConsultationObgynOrderSetRetargetingPhase14R4Test.php

Required Gynaecology tests:

Feature flags/performance:

Flags off preserve the current Gynaecology workspace.
Flags off add zero Maternity resolver calls.
Non-Gynaecology profile adds zero Gynaecology context calls.
Context enabled/unlinked shows no full Maternity card.
Explicit linked profile shows the small card.
Gynaecology uses explicit-only context resolution.
A same-visit or single-active profile is not shown automatically.
Write guard cannot activate while context flag is off.

No automatic transition:

Positive pregnancy test creates no Pregnancy Profile.
Positive pregnancy test creates no bridge link.
Positive pregnancy test does not change specialty profile.
Positive pregnancy test may render the explicit action.
Linking a profile does not change the consultation specialty.
Creating a profile does not change the consultation specialty.
No ANC or Labor record is created by link/create actions.

Gynaecology obstetric history:

Unlinked Gynaecology obstetric history remains writable.
Linked + pilot mode remains writable.
Linked + guarded mode blocks gravida/para/abortions/living children/
previous caesarean.
previous_complications remains writable.
menstrual_history.lmp remains writable when linked.
Other Gynaecology sections remain unchanged.
Direct HTTP manipulation is rejected.
Historical entries remain unchanged and visible.

LMP adoption:

Source LMP is read from persisted menstrual_history.
Client cannot substitute another LMP.
Adoption requires an explicit linked profile.
Adoption requires both permissions.
Adoption requires an editable consultation.
Completed consultation blocks adoption.
No saved LMP blocks adoption.
Null profile LMP is populated and dating method becomes LMP.
Same LMP is idempotent.
Different existing profile LMP blocks adoption.
Ultrasound/assisted-reproduction dating blocks adoption.
Consultation LMP remains unchanged after adoption.
Later Pregnancy Profile updates do not change Consultation LMP.
Optional create-profile checkbox copies LMP only when selected.

Required Obstetrics conversion tests:

Guarded current_pregnancy.pregnancy_confirmed cannot be written.
Profile presence is projected as confirmation.
Guarded current_pregnancy.danger_signs cannot be written.
current_complaints, high_risk_notes, booking_status remain writable.
Guarded birth_plan.danger_signs_counseling cannot be written.
Guarded birth_plan.next_visit_date cannot be written.
planned_place and delivery_plan remain writable.
Latest ANC counselling/next date render from AntenatalVisit.
Labor delivery plan is shown separately and does not overwrite the
Consultation delivery plan.

Required write-path hardening tests:

Normal form POST uses the write guard.
Order-set patch execution uses the write guard.
Quick-action/template patch execution uses the write guard where applicable.
A stale custom order set cannot write guarded Maternity-owned fields.
Guard-disabled mode preserves previous behavior.
Non-O&G profiles remain unaffected.
No sanctioned runtime specialty-entry path bypasses the guard.

Required order-set tests:

Audit finds all Maternity-shaped patch targets.
Known seeded pregnancy_confirmed item is retargeted.
Known seeded counselling item is retargeted.
Retargeted order set creates no specialty entry.
Retargeted order set creates no Pregnancy Profile automatically.
Retargeted order set creates no ANC visit automatically.
Applying the action produces an explicit action-required/advisory result.
Existing explicit profile marks create/link action satisfied.
Custom/admin-modified order-set item is not overwritten.
Seeder is idempotent.
Historical applications remain unchanged.
New applications record the new action type.
Repeated application does not duplicate action records.

Regression:

Phase 14R.3 Obstetrics workspace tests remain green.
Phase 14R.3.1 pilot tests remain green.
Phase 14R.2 bridge tests remain green.
Existing Gynaecology consultation remains usable without Maternity context.
Existing consultation completion/readiness/summary remain unchanged.
Existing orders/prescriptions/procedures/tasks remain unchanged.
Maternity billing remains preview-only.
No invoice item is created.
EN/FR parity passes.

Run:

php artisan test tests/Feature/ConsultationGynaecologyMaternityPhase14R4Test.php
php artisan test tests/Feature/ConsultationObgynOrderSetRetargetingPhase14R4Test.php
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

Run PHP syntax checks on every changed PHP, Blade and localisation file.

Baseline handling:

tests/Feature/Consultations may retain exactly 22 documented pre-existing
failures.
AntenatalCarePhase9Test may retain exactly one documented pre-existing
failure.
Phase 14R.4 must introduce zero new failures.
The Gynaecology skip in Phase 14R.3 should be replaced with a deterministic
Gynaecology fixture where practical.

Do not run composer test:wide.

Performance verification

Measure:

Gynaecology with context flag off.
Gynaecology enabled, no explicit link.
Gynaecology enabled, explicit Pregnancy Profile link.
Gynaecology explicit link + latest ANC.
Non-Gynaecology consultation with Gynaecology flag enabled.

Requirements:

Flag off: zero Gynaecology Maternity resolver calls.
Non-Gynaecology: zero calls.
Gynaecology enabled/unlinked:
do not invoke the six-table fallback chain
only bounded explicit-link checks are allowed
Explicit context:
resolve once per request
no Blade queries
Candidate-profile queries occur only after explicit selector action.
No persistent cross-request caching.
Record exact query counts in the report.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_GYNAECOLOGY_PHASE_14R_4_REPORT.md

Include:

What was implemented.
Files changed.
Gynaecology feature flags.
Explicit-only context resolution.
Small context-card behavior.
Positive pregnancy-test behavior.
Create/link behavior.
Confirmation that specialty never auto-switches.
Gynaecology obstetric-history write policy.
LMP adoption behavior and conflict policy.
Obstetrics current_pregnancy conversion.
Obstetrics birth_plan conversion.
All audited specialty-entry write paths.
Runtime guard-hardening result.
Order-set audit inventory.
Retargeted actions.
Seeder revision behavior.
Custom/admin-modified item behavior.
Historical application preservation.
Permissions.
Localisation.
Activity logging.
Query-count results.
Tests/checks run.
Baseline comparison.
Existing workflows protected.
Known risks.
Deferred work.
Rollout and rollback.
Next phase recommendation.

Update:

docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md
docs/maternity/OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Mark R5 implemented only after:

seeded actions are retargeted
runtime bypasses are guarded
historical applications are preserved
custom items are not overwritten
Rollout

Initial state:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Pilot:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Guarded:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=true

Before enabling guarded mode:

Measure existing Gynaecology obstetric_history entries per environment.
Audit custom order sets.
Run the order-set audit.
Review LMP adoption with clinicians.
Verify role permissions.
Confirm the Gynaecology workspace remains visually uncluttered.
Manually confirm no specialty switch occurs.

Rollback:

Disable Gynaecology write guard.
Disable Gynaecology context.
Clear config cache.
Confirm obstetric_history is editable again.
Leave bridge links and historical entries intact.
Do not reverse historical order-set applications.

Order-set definition rollback:

Restore only the current system-seeded definitions if required.
Never rewrite historical applications.
Never remove audit history.
Boundaries

Do not implement full Obstetrics/Gynaecology referral handoffs.
Do not create a new Obstetrics consultation automatically.
Do not auto-switch specialty profiles.
Do not auto-create Pregnancy Profiles.
Do not auto-link Pregnancy Profiles.
Do not auto-adopt LMP.
Do not auto-create ANC visits.
Do not auto-start Labor.
Do not change Gynaecology readiness.
Do not change Consultation summary behavior.
Do not add immutable Maternity snapshots.
Do not run historical specialty-entry reconciliation.
Do not enable Maternity billing posting.
Do not add billing UI to the doctor workspace.
Do not rewrite order, prescription, procedure or task engines.
Do not delete current or historical specialty entries.
Do not modify manual Maternity test seed data.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Files changed.
Gynaecology rollout modes.
Explicit pregnancy-transition behavior.
Confirmation that Gynaecology never auto-switches.
LMP adoption behavior.
Obstetric-history behavior.
Obstetrics current_pregnancy and birth_plan conversion.
Order-set runtime hardening.
Retargeted order-set actions.
Custom order-set treatment.
Historical application treatment.
Query counts.
Tests and baseline comparison.
Existing workflows protected.
Known risks.
Rollback steps.
Next phase recommendation.

Recommended next phase:

Phase 14R.5 — Admission, Emergency, Labor, Delivery and Postnatal Handoff Integration.
