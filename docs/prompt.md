You are working inside the UHMS Laravel project.

Completed reconciliation phases:

- Phase 14R.1:
  - O&G ↔ Maternity audit.
  - Field-level source-of-truth matrix.
  - Reconciliation architecture.

- Phase 14R.2:
  - Consultation ↔ Maternity bridge.
  - Explicit-FK context links.
  - Resolver.
  - Link/relink/unlink service.
  - Bridge permissions and activity logging.

- Phase 14R.3:
  - Obstetrics stage-aware Maternity context.
  - Pregnancy Profile dating method.
  - Field-level Obstetrics write guard.
  - Create-profile, Record-ANC and Start-Labor actions.

- Phase 14R.3.1:
  - Obstetrics ribbon and panel wired into the real consultation workspace.
  - Clinical mutation boundary hardened.
  - Real query counts measured.
  - Obstetrics rollout remains dark by default.

- Phase 14R.4:
  - Gynaecology explicit-only pregnancy context.
  - Gynaecology one-way LMP adoption.
  - Gynaecology obstetric-history ownership.
  - Obstetrics current_pregnancy and birth_plan conversion.
  - Runtime specialty write guard moved to the
    ConsultationSpecialtyEntryService boundary.
  - Order-set audit command.
  - Order-set reconciliation seeder.
  - maternity_context_action order-set action type.
  - Exactly two system-seeded maternity-shaped order-set patches were found
    and retargeted.
  - R5 is closed.

Current Phase 14R.4 verification:

- 21 Gynaecology tests passed.
- 10 order-set reconciliation tests passed.
- Maternity regression group:
  - 110 passed
  - 1 documented pre-existing ANC failure
- tests/Feature/Consultations:
  - 230 passed
  - 22 documented pre-existing failures
- Zero new failures.
- EN/FR parity:
  - 169 / 169
- Maternity billing remains preview-only and disabled by default.

Current remaining gaps:

R1:
The Gynaecology Pregnancy Context card partial and its view model are not
included in the real Gynaecology consultation workspace.

R2:
The new `maternity_context_action` order-set action is stored and handled
safely by the executor, but its clinician-facing CTA/result rendering is not
yet wired into the real workspace.

Now implement Phase 14R.4.1:

Gynaecology Pilot Wiring and Maternity Action Rendering.

Goal:

Make the already-built Gynaecology context feature and retargeted order-set
actions reachable in the real Consultation workspace, while preserving
dark-by-default rollout, explicit-only pregnancy transitions, zero automatic
specialty switching and all existing consultation/maternity ownership rules.

This is a narrow wiring, rendering, performance and pilot-hardening phase.

Do not begin Admission/Emergency handoff implementation in this phase.
Do not change Gynaecology readiness or completion.
Do not implement summary projection or immutable completion snapshots.
Do not enable Maternity billing posting.
Do not run historical reconciliation or backfills.
Do not run composer test:wide.
Do not touch docs/prompt.md.

1. Audit the real Gynaecology workspace include point

Review the real Consultation show-data and rendering path:

- HandlesConsultationWorkspace
- consultations/show.blade.php
- specialtyContext
- specialtyLayout
- selected specialty profile
- Obstetrics maternityContext integration from Phase 14R.3.1
- GynaecologyConsultationContextService
- GynaecologyWorkspaceViewModel
- Gynaecology context-card partial
- existing specialty action menus
- existing order-set result/application UI
- current dirty-state/navigation protection

Use the same workspace composer architecture as Obstetrics.

Do not resolve the Gynaecology context in Blade.

Do not create a second Gynaecology page or route.

2. Build the Gynaecology view model once per request

Wire `GynaecologyConsultationContextService` into
`HandlesConsultationWorkspace`.

Required behavior:

- Determine the active specialty profile through the existing specialty
  resolver.
- Only build the Gynaecology Maternity view model when:
  - the profile is Gynaecology, and
  - CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
- When the flag is false:
  - do not call GynaecologyConsultationContextService
  - do not call resolveExplicitOnly()
  - do not query consultation_maternity_links
  - do not query Pregnancy Profiles
  - preserve the existing view payload and query count
- For non-Gynaecology consultations:
  - do not call the Gynaecology service
- Build once per request.
- Pass the prepared view model into the real Consultation page.
- Reuse the same instance for:
  - context card
  - obstetric-history projections
  - positive-pregnancy-test affordance
  - LMP-adoption affordance
  - order-set maternity actions where applicable

Do not call the full Obstetrics context service for Gynaecology.

Do not use the normal visit/admission/single-profile fallback resolver.

Gynaecology remains explicit-only.

3. Include the small Gynaecology Pregnancy Context card

Wire the existing Gynaecology card partial into the real Consultation view.

Recommended placement:

- Within the Gynaecology specialty area.
- Near `obstetric_history` or the Gynaecology specialty action controls.
- After the common consultation/session context.
- Before the final summary/completion area.
- Do not place it as a full-width Obstetrics-style ribbon.
- Do not replace the patient banner.
- Do not replace the specialty profile banner.
- Do not move unrelated Gynaecology sections.

When context feature is disabled:

- Render nothing.
- Add no Maternity query cost.

When enabled but no explicit profile is linked:

- Do not render the full profile card.
- Show only a discreet, permission-controlled action:
  - “Start or Link Pregnancy Workflow”
- Do not show gestational age, EDD or Pregnancy Profile risk from an inferred
  profile.
- Do not query candidate profiles until the user explicitly opens the
  selector.

When an explicit profile is linked, show:

- “This consultation remains Gynaecology.”
- Explicitly linked Pregnancy Profile.
- Pregnancy Profile status.
- LMP.
- EDD.
- Gestational age.
- Dating method.
- Risk level.
- Latest ANC date where already available in the view model.
- Link to the Maternity Pregnancy Profile.
- Link/relink/unlink actions based on permissions.
- LMP-adoption action only when eligible.

Do not show:

- Record ANC.
- Start Labor.
- Delivery creation.
- Newborn creation.
- Postnatal mutation actions.

Holding Maternity ANC/Labor permissions must not make those actions appear in
Gynaecology.

4. Preserve all three Gynaecology rollout modes

Mode A — dark/default:

```env
CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Expected:

No card.
No pregnancy affordance.
No explicit-link query.
Existing Gynaecology fields behave exactly as before.
Existing Gynaecology query count is unchanged.

Mode B — pilot:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Expected:

Discreet Start/Link action when unlinked.
Small card when explicitly linked.
Existing obstetric-history fields remain editable.
Pilot marker explains that the linked Pregnancy Profile is visible but
legacy fields remain editable.
No specialty switch.

Mode C — guarded:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=true

Expected:

Small card remains.
With an explicit valid profile link:
gravida
para
abortions
living children
previous caesarean
render as Maternity projections
Those fields are not rendered as editable specialty inputs.
Server-side guard remains authoritative.
previous_complications remains writable.
menstrual_history.lmp remains writable.
All other Gynaecology sections remain unchanged.

The write guard must remain impossible to activate while the context feature
is disabled.

Add Gynaecology rollout markers

When context is enabled and guard is disabled, show a discreet marker:

“Pregnancy Context pilot mode”
“The linked Pregnancy Profile is visible; legacy Gynaecology obstetric
history remains editable.”

When both are enabled, show:

“The Pregnancy Profile is the source of truth for linked obstetric-history
fields.”

Only show markers to users with:

consultation.maternity_context.view

Keep EN/FR parity.

Render positive-pregnancy-test affordance safely

Use the tolerant persisted-value detection implemented in Phase 14R.4.

Required behavior:

Read from the saved sexual_sti_history.pregnancy_test specialty entry.
Never trust an unsaved or client-supplied value for this affordance.
Positive matching may include the already-implemented safe patterns:
pos*
yes
reactive
Explicitly exclude:
neg*
not ...
When positive and no explicit Pregnancy Profile exists:
show a non-blocking message
state that no Pregnancy Profile was created automatically
show Start or Link Pregnancy Workflow when permitted
Do not:
create a profile
link a profile
change specialty
create ANC
start Labor
create an Admission Request
change readiness
block consultation completion

When negative, unknown or absent:

Do not show a pregnancy warning.
The normal discreet Start/Link action may still exist in the action menu.
Wire explicit link/create/profile actions into the real card

Expose the existing Phase 14R.4 actions through the real page.

Required actions:

Select and link existing same-patient Pregnancy Profile.
Create Pregnancy Profile explicitly.
Relink with reason.
Unlink with reason.
Open linked Maternity Pregnancy Profile.
Adopt saved Gynaecology LMP when eligible.

Mutation boundaries:

Context management:

link
relink
unlink

May remain available on completed consultations under the already-approved
bridge policy.

Clinical mutations:

create Pregnancy Profile
adopt LMP

Must use mutableRoute() and require an editable consultation.

Required behavior:

Active/started consultation:
allowed with dual permissions
Paused/unstarted/cancelled:
follow the existing consultation mutation policy
Completed:
create profile blocked
LMP adoption blocked
Blocked attempts create:
no Pregnancy Profile
no bridge link
no partial update
Existing profile navigation remains available after completion.

Preserve originating Consultation URLs and return to the Gynaecology card.

Do not change the current specialty profile.

Wire one-way LMP adoption into the real card

The existing server-side adoption policy remains unchanged.

The UI must show the action only when:

an explicit Pregnancy Profile is linked
a persisted Gynaecology LMP exists
user has:
consultation.maternity_context.adopt_lmp
maternity.pregnancy.update
the consultation is currently editable
profile dating method does not prohibit LMP replacement

The confirmation UI must display:

Saved Gynaecology Consultation LMP.
Pregnancy Profile current LMP.
Pregnancy Profile dating method.
One-way adoption warning.
Statement that later Pregnancy Profile changes will not update the
Consultation entry.
Statement that the Consultation LMP will not be changed.

Behavior:

Null profile LMP:
allow adoption
Same LMP:
show already matches/idempotent state
Different non-null profile LMP:
show conflict
no adoption button
link to review profile in Maternity
Early ultrasound, late ultrasound or assisted reproduction dating:
show that LMP replacement is unavailable
no adoption button

Do not add an override path.

Do not add automatic adoption during linking.

Render linked Gynaecology obstetric-history projections

When guarded mode applies, update the real section presentation.

Read-only Pregnancy Profile projections:

gravida
para
abortions
living children
previous caesarean

Writable Consultation field:

previous complications

Historical behavior:

Existing obstetric_history entries remain visible.
Label historical values as:
“Legacy Gynaecology Consultation Entry”
Label current projections as:
“Source: Maternity Pregnancy Profile”
Do not compare or reconcile values automatically.
Do not hide conflicts.
Do not overwrite historical values.
Do not delete the section or its rows.

Pilot mode:

Leave existing inputs editable.
Display the Pregnancy Profile card separately.
Do not pretend the write guard is active.
Add clinician-facing rendering for maternity_context_action

Audit the existing order-set application/result display and action rendering
architecture.

Do not render action CTAs by parsing raw JSON directly in arbitrary Blade
templates.

Create or extend a typed presenter/view-model such as:

ConsultationSpecialtyOrderSetActionPresenter
ConsultationMaternityOrderSetActionViewModel

Supported action keys remain closed:

create_or_link_pregnancy_profile
record_anc_counselling

Unknown maternity action keys must fail closed and render an unsupported-action
warning.

A. create_or_link_pregnancy_profile

When no explicit profile is linked:

Show an explicit clinician CTA.
Available action depends on:
specialty
consultation editability
bridge permissions
underlying Maternity permissions
In Gynaecology:
show Start or Link Pregnancy Workflow
never show ANC/Labor actions
In Obstetrics:
show Create or Link Pregnancy Profile

When an explicit profile is already linked:

Show:
satisfied
linked Pregnancy Profile
Open Profile action
Do not create another profile.
Do not write current_pregnancy.pregnancy_confirmed.

B. record_anc_counselling

This action belongs to Obstetrics, not Gynaecology.

When Obstetrics has an explicit Pregnancy Profile and the user has both:

consultation.maternity_context.record_anc
maternity.anc.record

show:

Record ANC Visit / Counselling

When no explicit Pregnancy Profile exists:

Show:
Create or Link Pregnancy Profile first

When context is inferred but unconfirmed:

Show:
Confirm and Link first

When consultation is completed or not editable:

Show:
Start a new active consultation or use the Maternity workspace
Do not allow the clinical mutation from the completed Consultation.

In Gynaecology:

Never show Record ANC Counselling from an order-set action.
If a custom Gynaecology order set somehow contains this action, render:
unavailable in Gynaecology
use Obstetrics/Maternity workflow
Do not execute it.
Preserve order-set application semantics

The new action renderer must preserve:

order-set application ID
order-set application-item ID
action type
action key
application history
idempotency
legacy vs retargeted distinction

Display states should include:

action_required
satisfied
unavailable
blocked
unsupported
legacy_patch

Do not rewrite historical applications.

Historical patch_specialty_entry application items must display as:

“Legacy specialty patch”

New maternity_context_action items must display as:

“Maternity workflow action”

Do not pretend old applications used the new action.

Do not change the past application payload.

Define action execution versus action presentation

maternity_context_action is an explicit CTA, not an automatic mutation.

Applying an order set may:

create the order-set application/application-item history
mark the item action-required/advisory
present the clinician CTA

Applying an order set must not:

create a Pregnancy Profile
create a Consultation-Maternity link
create an ANC Visit
update specialty JSON
switch specialty
create billing
create an Admission Request

The actual clinician action must go through the normal existing controller,
permission and mutation-boundary checks.

Handle flags and retargeted order sets safely

The order-set action semantics must remain non-writing regardless of feature
flags.

When a retargeted order-set item exists but the relevant context feature is
disabled:

Do not revert to patch_specialty_entry.
Do not silently disappear.
Render a safe state such as:
Maternity workflow integration is not enabled
use the Maternity module directly
Preserve the application-item history.

When the feature is enabled:

Render the appropriate CTA/status.

The feature flag controls workspace visibility, not the safety semantics of
the retargeted order-set action.

Unsaved-data protection

Audit the existing Consultation dirty-state/navigation behavior.

Required behavior:

Opening profile selector must not silently discard unsaved Gynaecology data.
Creating/linking a profile must preserve or warn about unsaved data.
LMP adoption must use persisted LMP only.
Unsaved LMP must not be adopted.
Order-set CTAs that navigate to another form must use the existing
navigation/dirty-state pattern.
Successful actions return to:
the same Consultation
the Gynaecology context-card anchor, or
the Obstetrics Maternity Context panel for ANC actions
Do not add a second JavaScript navigation framework.
Performance measurement

Measure real Consultation workspace query counts for:

A. Gynaecology, flags off.

B. Gynaecology, context enabled, no explicit link.

C. Gynaecology, context enabled, explicit Pregnancy Profile link.

D. Gynaecology, explicit link plus latest ANC.

E. Gynaecology, positive persisted pregnancy test, no explicit link.

F. Non-Gynaecology consultation with Gynaecology flag enabled globally.

G. Obstetrics with a retargeted order-set action visible.

Required assertions:

Flags off:
zero Gynaecology Maternity context calls
zero link/profile queries from this feature
Non-Gynaecology:
zero Gynaecology context calls
Gynaecology enabled/unlinked:
explicit-link check only
no six-table fallback
no active-profile query unless selector opened
Explicit linked:
resolve once
no Blade queries
Latest ANC:
no N+1
Positive-test affordance:
reuse loaded/saved specialty-entry data where possible
do not independently reload the section from each component
Order-set presenter:
use already-loaded application/action data
no per-item N+1

Document exact counts and any optimisation performed.

Do not add persistent cross-request caching.

Routes and middleware

Keep all existing routes.

Do not rename Phase 14R.4 routes.

Review middleware so that:

Read-only/card operations require:

Consultation access
consultation.maternity_context.view
underlying record view permissions where appropriate

Context-management operations require:

Consultation access
bridge link/unlink permission
target Pregnancy Profile visibility
no specialty mutation

Clinical operations require:

Consultation access
editable mutation context
bridge clinical-action permission
underlying Maternity permission

Order-set CTA renderer must not expose a link the user cannot execute.

Disabled actions should render their reason rather than a clickable forbidden
link where practical.

Activity logging

Keep existing Phase 14R.4 activity logging.

Do not log every card render.

Do not log every order-set CTA render unless the existing order-set application
already records it.

Successful actions retain:

PREGNANCY_PROFILE_CREATED_FROM_GYNAECOLOGY_CONSULTATION
PREGNANCY_PROFILE_LINKED_FROM_GYNAECOLOGY_CONSULTATION
GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING
OBGYN_ORDER_SET_MATERNITY_ACTION_PRESENTED
OBGYN_ORDER_SET_MATERNITY_ACTION_SATISFIED

Use identifier-only metadata.

Do not log sexual/STI history, menstrual notes or counselling text.

Localisation

Extend EN/FR in strict recursive parity for:

Real card:

Pregnancy Context
This consultation remains Gynaecology
Start or Link Pregnancy Workflow
Explicitly linked Pregnancy Profile
Source: Maternity Pregnancy Profile
Legacy Gynaecology Consultation Entry
Pregnancy Context pilot mode
Pregnancy Profile source-of-truth mode
No Pregnancy Profile was created automatically
Positive pregnancy test recorded
Open Maternity Pregnancy Profile

LMP:

Adopt saved LMP
LMP already matches
LMP conflict
Scan/ART dating prevents replacement
One-way adoption
Unsaved LMP cannot be adopted
Review in Maternity

Order-set actions:

Maternity workflow action
Action required
Action satisfied
Action unavailable
Action blocked
Unsupported maternity action
Create or Link Pregnancy Profile
Record ANC Visit / Counselling
Confirm Pregnancy Profile first
Not available in Gynaecology
Legacy specialty patch
Integration disabled
Use Maternity workspace
Start a new active consultation

Return/navigation:

Return to Gynaecology consultation
Return to Maternity Context
Unsaved changes warning

Verify recursive EN/FR parity.

Tests

Create:

tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php

Create or extend:

tests/Feature/ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php

Required real-page Gynaecology tests:

Flags and wiring:

Real Gynaecology page renders no context card when flags are off.
Flags off invoke Gynaecology context service zero times.
Non-Gynaecology page invokes it zero times.
Context enabled/unlinked shows only the discreet Start/Link action.
Context enabled/linked shows the small card.
Ribbon/full Obstetrics stage panel does not render for Gynaecology.
Pilot marker renders with guard off.
Source-of-truth marker renders with guard on.
Card and obstetric-history projection use the same view-model instance.
Blade partial performs no queries.

Explicit-only behavior:

Same-visit Pregnancy Profile is not displayed automatically.
Same-admission Pregnancy Profile is not displayed automatically.
Single active profile is not displayed automatically.
Positive pregnancy test does not create or link a profile.
Positive pregnancy test displays only the explicit affordance.
Linking retains Gynaecology specialty.
Creating a profile retains Gynaecology specialty.
No ANC or Labor action is shown.

Real obstetric-history rendering:

Unlinked Gynaecology fields remain editable.
Linked pilot mode leaves them editable.
Linked guarded mode renders gravida/para/abortions/living children/
previous caesarean as projections.
previous_complications remains editable.
menstrual_history.lmp remains editable.
Historical entries remain visible and unchanged.
Direct POST remains blocked by the service-boundary guard.

Real LMP UI:

Eligible linked profile shows adoption action.
No saved persisted LMP shows no action.
Unsaved browser value cannot be adopted.
Existing different profile LMP shows conflict.
Ultrasound/ART dating shows unavailable state.
Completed consultation shows no clinical adoption action.
Active consultation can adopt with both permissions.
Consultation LMP remains unchanged.

Required maternity action rendering tests:

Retargeted create/link action renders action-required when unlinked.
Explicit linked profile renders satisfied.
Action creates no specialty entry.
Action creates no Pregnancy Profile automatically.
Action creates no link automatically.
Record-ANC-counselling action renders in Obstetrics only.
Record-ANC action requires explicit profile.
Record-ANC action requires both permissions.
Completed consultation renders non-executable guidance.
Gynaecology never renders Record ANC CTA.
Unknown maternity action renders unsupported.
Integration-disabled state does not revert to patching.
Historical patch application renders as legacy.
New application renders as Maternity workflow action.
Rendering does not modify historical applications.
Rendering does not duplicate application items.
No billing record or invoice item is created.

Regression:

Phase 14R.4 Gynaecology tests remain green.
Phase 14R.4 order-set tests remain green.
Phase 14R.3/14R.3.1 Obstetrics tests remain green.
Phase 14R.2 bridge tests remain green.
Existing Consultation completion/readiness/summary stay unchanged.
Existing Gynaecology order set for bleeding_pattern remains unchanged.
Maternity billing remains preview-only.
EN/FR parity passes.

Run:

php artisan test tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php
php artisan test tests/Feature/ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php
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
Phase 14R.4.1 must introduce zero new failures.
Do not run composer test:wide.
Manual pilot checks

A. Dark mode

Open Gynaecology Consultation.
Confirm no Pregnancy Context card.
Confirm current layout and fields are unchanged.
Confirm no Maternity query delta.

B. Pilot mode

Enable context, leave guard off.
Link a Pregnancy Profile explicitly.
Confirm small card placement is clear and not intrusive.
Confirm consultation still says Gynaecology.
Confirm legacy obstetric-history inputs remain editable.
Confirm no ANC/Labor actions are shown.

C. Guarded mode

Enable both flags.
Confirm Pregnancy Profile obstetric history is projected read-only.
Confirm previous complications and menstrual LMP remain editable.
Confirm old Gynaecology values remain visible as legacy entries.

D. Positive pregnancy test

Save a supported positive free-text result.
Confirm explicit Start/Link affordance appears.
Confirm no profile, bridge, ANC or Labor record is created.

E. LMP adoption

Save Gynaecology LMP.
Link a profile with null LMP.
Adopt explicitly.
Confirm Pregnancy Profile gets LMP and dating_method=lmp.
Confirm Consultation entry remains unchanged.
Confirm conflict/scan-dating cases block adoption.

F. Retargeted order set

Apply Obstetrics antenatal-booking order set.
Confirm the two retargeted items present CTAs.
Confirm no specialty JSON is written for the guarded fields.
Confirm no Pregnancy Profile or ANC visit is created until the clinician
explicitly performs the normal action.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_GYNAECOLOGY_PILOT_PHASE_14R_4_1_REPORT.md

The report must include:

Real include point.
Card placement.
Files changed.
Dark/pilot/guarded behavior.
Explicit-only resolution behavior.
Positive-pregnancy-test behavior.
Link/create behavior.
Confirmation that specialty never changes.
LMP-adoption UI behavior.
Obstetric-history projection behavior.
Order-set action presenter design.
Action states.
Legacy-vs-retargeted display.
Feature-disabled behavior.
Mutation-boundary behavior.
Unsaved-data behavior.
Query-count measurements.
Tests/checks.
Baseline comparison.
Existing workflows protected.
Known risks.
Rollout.
Rollback.
Next recommended phase.

Update:

docs/maternity/OBGYN_MATERNITY_GYNAECOLOGY_PHASE_14R_4_REPORT.md
docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Close report risks R1 and R2 only after:

the card is visible in the real workspace when enabled
retargeted order-set actions render real clinician CTAs/statuses
Rollout and rollback

Initial:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Pilot:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Guarded:

CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=true
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=true

Before guarded rollout:

Measure existing Gynaecology obstetric-history entries.
Run consultation:obgyn-order-set-audit.
Review custom-needs-review items.
Run the order-set reconciliation seeder.
Confirm card layout with clinicians.
Confirm LMP-adoption language and policy.
Confirm clinical permissions.
Test completed-consultation behavior.

Rollback:

Disable Gynaecology write guard.
Disable Gynaecology context.
Clear config cache.
Confirm existing Gynaecology fields are editable.
Leave bridge links and historical entries intact.
Leave historical order-set applications intact.
Do not revert retargeted items into unsafe automatic writes merely to
restore the previous UI.
Boundaries

Do not begin Admission/Emergency handoffs.
Do not create an Obstetrics consultation automatically.
Do not switch specialty automatically.
Do not infer Gynaecology Pregnancy Profile context.
Do not auto-create or auto-link profiles.
Do not auto-adopt LMP.
Do not show ANC/Labor mutations in Gynaecology.
Do not change Gynaecology readiness or completion.
Do not change Consultation summary behavior.
Do not add immutable completion snapshots.
Do not run historical reconciliation.
Do not enable Maternity billing posting.
Do not add billing UI to the doctor workspace.
Do not rewrite order, prescription, procedure or task engines.
Do not delete specialty entries.
Do not rename routes.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Real include point and card placement.
Dark/pilot/guarded behavior.
Confirmation that Gynaecology never auto-switches.
Positive-test affordance.
LMP-adoption behavior.
Obstetric-history projection behavior.
Order-set CTA rendering.
Query counts.
Tests and baseline comparison.
Existing workflows protected.
Known risks.
Rollout and rollback.
Next phase recommendation.

Next phase after Phase 14R.4.1 passes:

Phase 14R.5 — Admission, Emergency, Labor, Delivery and Postnatal Handoff
Integration.
