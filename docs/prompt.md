You are working inside the UHMS Laravel project.

Phase 14R.5 is complete.

Implemented in Phase 14R.5:

- Durable, explicit Maternity context links for:
  - Emergency cases
  - Admission Requests
  - Admissions
- Shared `MaternityContextTargetService`.
- Consultation → Admission Request handoff.
- Gynaecology → Obstetrics/Maternity referral through the existing consultation
  routing service.
- Emergency → Pregnancy/Labor/Admission Request handoffs.
- Maternity → Emergency explicit escalation.
- Admission Request → Admission Maternity context propagation.
- Admission Maternity context resolver and workspace view model.
- Postnatal review links.
- Safe named-route return contexts.
- Additive permissions and EN/FR localisation.
- Four new feature suites:
  - 64 tests passed.
- All Emergency suites:
  - 88 tests passed.
- Consultation baseline:
  - 230 passed / 22 documented pre-existing failures.
- Admission/Maternity baselines remained unchanged.
- Phase 14R.5 introduced zero new failures.
- All 10 O&G/Maternity integration flags remain false by default.
- Maternity billing remains disabled and preview-only.

Important Phase 14R.5 ownership model:

- Consultation owns the specialist encounter.
- Emergency owns the acute emergency episode.
- Admission owns the inpatient episode.
- Maternity owns the pregnancy, ANC, labor, delivery, newborn and postnatal
  records.

Important unresolved Phase 14R.5 items:

K1:
Gynaecology → Obstetrics referral requires an active
`ConsultationSpecialtyProfileMapping` for an Obstetrics consultation
department. When none exists, the handoff correctly returns unavailable and
must direct the user into the existing standard consultation-creation flow.

Do not create a new referral subsystem.

K2:
Several Phase 14R.5 handoff buttons reference modal IDs whose modal bodies are
not yet implemented.

Examples documented in the report include:

- `emergencyLinkPregnancyModal`
- `admissionLinkPregnancyModal`
- `consultationMaternityAdmissionRequestModal`

There may be additional modal targets.

Routes, permissions, services and server-side behavior are implemented and
tested, but buttons targeting missing modals are inert.

K3:
The no-context Consultation handoff panel costs eight queries, mostly from
permission loading. It is not a blocker but should be observed during pilot.

K4:
Legacy `source_type=maternity` Admission Requests are ambiguous because
`source_id` may refer to an ANC Visit or a Labor Episode. They must continue
to show a warning and require explicit linking. Do not reinterpret them.

Now implement Phase 14R.5.1:

Handoff UI Completion and Pilot Closure.

Goal:

Make every visible Phase 14R.5 handoff action operational in the real
Consultation, Emergency, Admission and Maternity workspaces.

This phase should:

- Author all missing modal/dialog/form bodies.
- Ensure every visible button leads to a valid authorised action.
- Preserve server-side ownership, permission, lifecycle and idempotency rules.
- Preserve dark-by-default rollout.
- Avoid introducing additional hot-path queries when features are disabled.
- Close K2 completely.
- Verify the K1 fallback is usable without creating a new referral system.

This is a narrow UI and pilot-hardening phase.

Do not implement Phase 14R.6 summary, readiness, snapshots, reconciliation or
billing policy work yet.

Do not enable Maternity billing posting.

Do not create a new Emergency, Admission, Consultation or Maternity engine.

Do not run `composer test:wide`.

Do not touch `docs/prompt.md`.

1. Audit every handoff trigger and target before coding

Search all changed Phase 14R.5 views for:

- `data-bs-toggle="modal"`
- `data-bs-target`
- `href="#..."`
- JavaScript modal-open calls
- action keys rendered by handoff presenters
- forms with missing action routes
- disabled buttons with no explanatory state

Audit at least:

- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/maternity/handoff-actions.blade.php`
- `resources/views/emergency/show.blade.php`
- `resources/views/emergency/partials/maternity-context-card.blade.php`
- `resources/views/admissions/show.blade.php`
- simplified nurse Admission view if the Maternity context card is included
- `resources/views/admissions/partials/maternity-context-card.blade.php`
- Maternity Labor, Delivery and Postnatal views that expose Emergency handoff
  buttons
- shared Maternity context-card partials
- any Gynaecology referral CTA
- any Postnatal review CTA

Produce an exact inventory:

- trigger label
- trigger ID
- modal target ID
- source workspace
- controller route
- HTTP method
- required request fields
- required bridge permission
- required target-domain permission
- consultation/emergency/admission lifecycle requirement
- current state:
  - complete
  - missing modal
  - missing form
  - missing route binding
  - missing disabled explanation
  - intentionally unavailable

Do not assume the three documented IDs are the only missing targets.

2. Add an automated modal-target integrity check

Add a targeted test or test helper that validates every Phase 14R.5 modal
trigger.

The check should verify that each relevant `data-bs-target="#modalId"` has
one of:

- a matching rendered modal element with `id="modalId"`, or
- an explicit registered lazy-modal endpoint mapping, if the project already
  uses lazy modal loading

A visible trigger must never point to a missing target.

The test should fail when a new handoff button is added without a corresponding
dialog/body.

Do not scan unrelated third-party or global navigation modals unless the
project has a safe scoped parser.

Scope the integrity check to the O&G/Maternity handoff views.

3. Reuse existing modal and form conventions

Audit the project’s existing modal architecture:

- Bootstrap version and modal markup
- reusable Blade modal components
- confirmation dialogs
- validation-error handling
- old-input handling
- searchable selectors
- AJAX/lazy form loading
- form dirty-state handling
- button loading/disabled states
- success/error flash behavior

Use the existing application pattern.

Do not introduce:

- a second modal framework
- a new frontend framework
- a separate SPA layer
- inline JavaScript business logic
- state-changing GET routes

All mutations must remain POST/PATCH/DELETE as already defined.

4. Add a typed handoff-action presentation contract

Avoid scattering route, permission and modal rules through Blade.

Audit and reuse where possible:

- `ConsultationMaternityHandoffPresenter`
- `EmergencyMaternityWorkspaceService`
- `AdmissionMaternityWorkspaceService`
- `MaternityContextCardBuilder`
- `OperationalMaternityViewModel`

Extend an existing presenter or add a typed DTO such as:

`MaternityHandoffActionViewModel`

Recommended fields:

- action key
- label
- description
- visible
- enabled
- disabled reason
- route name
- route parameters
- HTTP method
- modal ID
- confirmation level
- required fields
- source module
- source record ID
- target module
- target context type
- existing target record ID where reused
- return context
- permission state
- lifecycle state
- duplicate/reuse state

Blade should render from this typed action model.

Blade must not:

- rediscover permissions
- query for profiles, requests, admissions or Emergency cases
- infer whether a record should be reused
- build unsafe return URLs
- interpret ambiguous legacy `source_id`

5. Implement Consultation → Admission Request modal

Author the real body for the existing Consultation Maternity Admission Request
trigger.

Use the actual modal ID referenced by the current view.

Expected fields, based on the existing server action:

- most-specific linked Maternity context, displayed read-only
- Pregnancy Profile, displayed read-only
- priority
- requested ward
- provisional diagnosis
- short clinical handover summary
- return-to-consultation context

Rules:

- Available in Obstetrics only.
- Explicit valid Pregnancy Profile context required.
- Active/editable Consultation required through `mutableRoute()`.
- Requires both:
  - `consultation.maternity_context.create_admission_request`
  - `admission.requests.create`
- Do not show the action in Gynaecology.
- Do not create an Admission.
- Do not reserve a bed automatically.
- Do not post billing.
- Do not copy the entire consultation note.
- Preserve the existing 500-character safe-summary boundary.
- Repeated submission must reuse the existing open matching request.
- When an open request already exists:
  - show its ID and status
  - replace “Create” with “Open Existing Admission Request” where appropriate
- Completed, paused, unstarted or invalid Consultations must show the existing
  lifecycle reason rather than an executable form.

On success:

- close/return safely
- return to the same Consultation Maternity panel
- display whether the request was created or reused
- provide a link to the Admission Request detail page

6. Implement Emergency Pregnancy Profile selector/link modal

Author the real modal body for the Emergency link/profile action.

The modal must support:

- confirming a suggested same-visit Pregnancy Profile
- selecting an existing same-patient Pregnancy Profile
- linking the selected profile
- relinking with a required reason
- unlinking with a required reason
- navigating to explicit profile creation when permitted

Rules:

- Never preselect among multiple active profiles.
- Never show another patient’s profile.
- Never create or link because of:
  - emergency complaint
  - diagnosis
  - danger sign
  - patient sex
  - pregnancy test
- Suggested context remains unpersisted until explicit confirmation.
- Ambiguous candidates require explicit selection.
- Link/relink/unlink must use `EmergencyMaternityLinkService`.
- Same-target linking is idempotent.
- Relink/unlink reasons are required.
- Existing history remains preserved.
- Profile creation requires both:
  - `emergency.maternity_context.create_profile`
  - `maternity.pregnancy.create`
- Link actions require the relevant Emergency bridge permission and target
  profile visibility.

Candidate-loading performance:

- Do not load all candidate Pregnancy Profiles on every Emergency page render.
- Prefer the existing searchable-selector pattern or a lazy internal endpoint.
- Candidate lookup occurs only when the user opens the selector.
- Search is patient-scoped server-side.
- Client input must not be able to change the patient scope.

7. Implement Emergency Start/Open Labor dialog

Author the action body for starting or opening Labor from Emergency.

Display:

- explicitly linked Pregnancy Profile
- current gestational age/EDD where available
- existing active Labor Episode if one exists
- Emergency visit/context
- warning that Emergency remains owner of acute care
- warning that Maternity owns Labor records

Behavior:

- Explicit Pregnancy Profile link required.
- Suggested context is insufficient.
- Requires:
  - `emergency.maternity_context.start_labor`
  - `maternity.labor.start`
- If an active episode exists:
  - do not show a second-create form
  - show “Open Existing Labor Episode”
- Otherwise:
  - show only the inputs already accepted by the existing
    `LaborEpisodeService` handoff route
  - do not duplicate the full Labor form inside Emergency
- Repeated submission reuses the active episode.
- Delivered, closed or cancelled Labor Episodes are not reused.
- No Labor Episode is started automatically from the context card.
- No billing is posted.
- No Admission Request is created automatically.

On success:

- show created/reused result
- provide Open Labor action
- preserve Emergency return context

8. Implement Emergency Admission Request modal

Author the real Emergency Maternity Admission Request dialog.

Display:

- Emergency case
- Pregnancy Profile
- active Labor Episode where available
- operational source:
  - Emergency
- clinical maternity context:
  - separate and clearly labelled
- requested ward
- priority
- provisional diagnosis
- short handover summary

Rules:

- Use existing Emergency disposition/admission-request behavior.
- `source_type` remains `emergency`.
- `source_id` remains EmergencyCase ID.
- Maternity context is written through
  `AdmissionRequestMaternityLinkService`.
- Do not overload source fields.
- Repeated action reuses the open Emergency Admission Request.
- Existing disposition/bay history remains intact.
- Do not auto-admit.
- Do not auto-reserve a bed.
- Do not post billing.
- Suggested Maternity context must be confirmed before a Maternity-aware request
  is submitted.

When an open request exists:

- show current status
- show Open Request action
- do not render a misleading “Create another” button

9. Implement Admission Pregnancy/Maternity context modal

Author the real Admission context-management modal.

Support:

- link an existing same-patient Pregnancy Profile
- link a more specific Maternity target where allowed by the current server
  action
- correct/relink context with required reason
- unlink with required reason
- inspect propagated request context
- distinguish:
  - carried from Admission Request
  - linked directly to Admission
  - inferred from matching `admission_id`
  - legacy ambiguous source warning

Rules:

- Use `AdmissionMaternityLinkService`.
- Never modify `PregnancyProfile.admission_id` as the durable linkage.
- Never overwrite a stage record’s conflicting `admission_id`.
- Never choose between multiple Pregnancy Profiles automatically.
- Direct non-maternity Admissions remain unaffected.
- Link/relink/unlink requires:
  - Admission bridge permission
  - target Maternity visibility
- Candidate profiles load only when the modal is opened.
- No ANC, Labor, Delivery, Newborn or Postnatal record is created by this
  modal.
- No billing is posted.

For propagated request context:

- display the source Admission Request
- display the operational origin
- display the clinical Maternity context separately

10. Implement Maternity → Emergency handoff confirmation

Author the real dialog for explicit Emergency escalation from:

- Labor Episode
- Delivery Record where supported
- Postnatal Case

Display:

- source Maternity record
- Pregnancy Profile
- patient
- current visit
- escalation/referral flag state
- warning:
  - the flag alone created nothing
  - this explicit action will create or open an Emergency Case
- existing active linked/on-visit Emergency Case where present

Rules:

- Requires:
  - `maternity.emergency_handoff.create`
  - existing Emergency case-create permission
- Use `MaternityEmergencyHandoffService`.
- Never manually insert an EmergencyCase.
- Reuse an active linked or same-visit Emergency Case.
- Repeated submission is idempotent.
- Do not create an Admission Request automatically.
- Do not create a Theatre case.
- Do not post billing.
- Preserve return context to the originating Maternity record.

On success:

- state whether Emergency Case was created or reused
- provide Open Emergency Case
- preserve source Maternity record

11. Implement Postnatal review/link dialog where referenced

If the Consultation handoff panel exposes a Postnatal review button or modal,
author its body.

Support:

- select/link an existing same-patient Postnatal Case
- open the existing Postnatal Case
- show current readiness read-only
- show latest mother/newborn observation timestamps
- preserve Consultation return context

Rules:

- Requires:
  - `consultation.maternity_context.open_postnatal`
  - `maternity.postnatal.view`
- No Postnatal Case is created automatically.
- No mother/newborn observation is created.
- Consultation note remains Consultation-owned.
- Completed Consultation may view/link existing context according to the
  approved bridge policy.
- Clinical Postnatal mutations remain in Maternity.

12. Close K1 with a real standard-flow fallback

Do not create a new referral subsystem.

When Gynaecology → Obstetrics referral is unavailable because there is no
active `ConsultationSpecialtyProfileMapping`:

- show a clear unavailable reason
- provide a real link into the existing standard create-consultation/referral
  flow
- preserve:
  - patient
  - visit
  - linked Pregnancy Profile context where safe
  - originating Gynaecology Consultation return context
- do not preselect an invalid department
- do not create a route until the user completes the existing standard flow
- do not change the Gynaecology specialty
- do not create ANC, Labor or Admission Request

When a valid mapping exists:

- show the explicit referral confirmation
- reuse the existing `ConsultationRouteService`
- repeated submission must reuse/detect the existing open equivalent route
- link the target route to the same Pregnancy Profile
- preserve the original Gynaecology route and entries

K1 remains a configuration dependency, but the clinician-facing fallback must
be fully usable.

13. Render disabled/unavailable states honestly

Every handoff action must render one of:

- enabled
- existing_record
- blocked
- unavailable
- ambiguous
- permission_missing
- feature_disabled
- invalid_context

Do not render a clickable button when the server will inevitably reject it.

Examples:

- completed Consultation clinical action:
  - blocked
  - start a new active Consultation or use Maternity
- no explicit Pregnancy Profile:
  - link/confirm first
- target permission missing:
  - underlying Maternity/Admission/Emergency permission required
- feature flag disabled:
  - integration unavailable
- existing open request:
  - open existing request
- active Labor exists:
  - open existing Labor
- ambiguous legacy source:
  - explicit context selection required

The server remains authoritative even when the UI correctly disables an
action.

14. Preserve lifecycle boundaries

Context-management actions:

- link
- relink
- unlink
- review existing context

May remain available after Consultation completion where already approved.

Clinical/operational creation actions launched from Consultation:

- create Pregnancy Profile
- adopt LMP
- record ANC
- start Labor
- create Admission Request
- create Obstetrics referral

Must use the appropriate existing mutation/lifecycle boundary.

Emergency actions must respect Emergency case state.

Admission actions must respect Admission status.

Maternity Emergency handoff must respect source-record and patient validity.

No UI change may weaken the server-side checks already implemented in 14R.5.

15. Preserve idempotency visibly

The UI should expose the server’s idempotent results.

For repeated actions:

- show “Existing record reused”
- show the existing record ID/status
- link to the existing record
- do not present success wording that implies a duplicate record was created

Cover:

- Consultation → Admission Request
- Emergency → Labor
- Emergency → Admission Request
- Maternity → Emergency Case
- Gynaecology → Obstetrics referral
- request → Admission propagated context

Do not add client-side duplicate identity logic.

The server-side transactional identity remains authoritative.

16. Safe return contexts

All modal forms must use `MaternityReturnContext`.

Do not accept raw return URLs.

Required behavior:

- Consultation action returns to the same Consultation/panel.
- Emergency action returns to the same Emergency case/context card.
- Admission action returns to the same Admission/context tab.
- Maternity Emergency action returns to the same Labor/Delivery/Postnatal
  record.
- Invalid return context falls back to the normal target show page.
- External URLs remain structurally impossible.
- Authorization is rechecked at the destination.
- No browser-history loops.

17. Validation and error rendering

Use the existing project form-error pattern.

Requirements:

- validation messages appear inside the relevant modal/dialog
- user input is preserved where safe
- modal can reopen after redirect when validation fails, using the project’s
  existing flash/session convention
- patient mismatch is shown clearly
- ambiguous profile selection is shown clearly
- relink/unlink reason errors are shown clearly
- conflicting `admission_id` is shown as a review conflict, not overwritten
- duplicate/idempotent result is not displayed as an error
- no stack traces or internal model class names are exposed

Do not duplicate validation rules already owned by controller requests/services.

18. Permissions

Do not add new permissions unless the audit finds an action lacking an
appropriate existing bridge permission.

Use Phase 14R.5 permissions:

Consultation:

- `consultation.maternity_context.create_admission_request`
- `consultation.maternity_context.refer_obstetrics`
- `consultation.maternity_context.open_postnatal`

Emergency:

- `emergency.maternity_context.view`
- `emergency.maternity_context.link`
- `emergency.maternity_context.unlink`
- `emergency.maternity_context.create_profile`
- `emergency.maternity_context.start_labor`
- `emergency.maternity_context.create_admission_request`

Admission:

- `admission.maternity_context.view`
- `admission.maternity_context.link`
- `admission.maternity_context.unlink`

Maternity:

- `maternity.emergency_handoff.create`

Every action still requires the target-domain permission.

Do not expose action forms to a user who lacks either half.

19. Feature-flag behavior

All new modal bodies and action controls remain governed by the Phase 14R.5
flags.

```env
MATERNITY_CONSULTATION_HANDOFFS_ENABLED=false
MATERNITY_EMERGENCY_CONTEXT_ENABLED=false
MATERNITY_ADMISSION_CONTEXT_ENABLED=false
MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false

When a feature is false:

no active trigger
no modal body
no candidate query
no presenter action calculation beyond a cheap disabled state where needed
no server-side behavior change

Enabling read-only context without handoffs:

shows context
does not render mutation forms

Enabling handoffs:

renders only actions the user may execute

The four Obstetrics/Gynaecology feature flags remain independent.

Performance and query protection

Candidate/profile selectors should not add hot-path cost.

Required:

Flag-disabled surfaces:
zero new queries
Read-only cards:
keep existing Phase 14R.5 measured counts
Candidate Pregnancy Profile queries:
run only when selector/modal is explicitly opened
Ward selector:
use the project’s existing bounded/active ward query
do not load beds unless the current action requires them
Modal partials:
perform no direct queries
Presenters:
reuse existing prepared view models
No N+1 for candidate profiles
No repeated request lookup for each button
No persistent cross-request caching

Measure:

Emergency page with flags off
Emergency page with context on and selector closed
Emergency profile selector opened
Admission page with flags off
Admission context on and selector closed
Admission profile selector opened
Consultation page with handoffs off
Consultation handoff modal opened
Maternity Labor/Postnatal page with Emergency handoff off/on

Document exact query counts and compare to Phase 14R.5.

Localisation

Extend EN/FR in strict recursive parity.

Include keys for:

General modal behavior:

Confirm handoff
Select context
Existing record reused
Record created
Record already exists
Operation unavailable
Action blocked
Feature disabled
Permission required
Invalid context
Ambiguous context
Return to source workspace
Validation failed

Consultation Admission Request:

Create Maternity Admission Request
Open Existing Admission Request
Requested ward
Priority
Provisional diagnosis
Clinical handover summary
Operational source: Consultation
Clinical context: Maternity
No Admission was created
No bed was reserved

Emergency:

Link Pregnancy Profile
Confirm Suggested Pregnancy Profile
Select Pregnancy Profile
Create Pregnancy Profile in Maternity
Relink Pregnancy Profile
Unlink Pregnancy Profile
Start Labor
Open Existing Labor
Create Admission Request
Open Existing Admission Request
Emergency remains owner of acute care
Labor remains owned by Maternity

Admission:

Link Admission Maternity Context
Context propagated from Admission Request
Context linked directly
Correct context
Context conflict
Legacy source cannot be interpreted automatically

Maternity → Emergency:

Create Emergency Handoff
Open Existing Emergency Case
Escalation flag created no Emergency Case
Emergency Case will be created explicitly
No Admission Request will be created
No Theatre Case will be created

Gynaecology fallback:

Obstetrics mapping unavailable
Continue through standard Consultation creation
Existing Gynaecology consultation will remain unchanged
No maternity record will be created automatically

Postnatal review:

Link Postnatal Review
Open Postnatal Case
Observations remain in Maternity
No Postnatal observation was created

Verify recursive EN/FR parity.

Activity logging

Do not log modal opens or profile-search queries.

Retain successful identifier-only logs already created in Phase 14R.5.

Do not add logs containing:

full clinical summary
diagnosis narrative
ANC measurements
Labor observations
Newborn measurements
Postnatal observations
sexual or menstrual history

If UI completion requires a new event, keep it identifier/status-only.

Tests

Create:

tests/Feature/MaternityHandoffUiPhase14R5_1Test.php

Create:

tests/Feature/MaternityHandoffModalIntegrityPhase14R5_1Test.php

Optional, if selectors use dedicated endpoints:

tests/Feature/MaternityHandoffSelectorsPhase14R5_1Test.php

Required modal-integrity tests:

Every Phase 14R.5 modal trigger has a rendered target or registered lazy
endpoint.
No trigger targets a missing ID.
Feature-disabled actions do not render active modal triggers.
Unauthorized users do not receive executable modal forms.
Read-only context mode renders no mutation forms.

Required Consultation modal tests:

Consultation Admission Request trigger opens a real modal.
Form posts to the correct route.
Required fields validate.
Active Consultation can create/reuse request.
Completed Consultation renders blocked guidance, not an executable form.
Open request renders Open Existing Request.
Repeated submission reuses the request.
No Admission, bed reservation or invoice item is created.
Return context returns to the same Consultation panel.

Required Emergency modal tests:

Link Pregnancy trigger opens a real selector/modal.
Candidate query is same-patient scoped.
Ambiguous profiles require selection.
Suggested profile requires confirmation.
Patient mismatch is blocked.
Relink/unlink require reason.
Start Labor modal opens only with explicit profile.
Existing active Labor shows Open Existing Labor.
Repeated Start Labor reuses the episode.
Admission Request modal preserves source_type=emergency.
Request receives Maternity context links.
Open request is reused.
No auto-admission or billing occurs.

Required Admission modal tests:

Link Pregnancy trigger opens a real modal.
Request-propagated context is shown separately from operational origin.
Explicit linking is same-patient scoped.
Relink/unlink require reason.
Legacy ambiguous source renders warning.
Conflicting stage admission_id is never overwritten.
Direct non-maternity Admission remains unaffected.
No Maternity record is created.

Required Maternity → Emergency tests:

Labor handoff opens a real confirmation.
Postnatal handoff opens a real confirmation where supported.
Flag alone creates no Emergency Case.
Explicit confirmation creates/reuses one Emergency Case.
Repeated confirmation reuses it.
No Admission Request, Theatre Case or invoice item is created.
Return context returns to source Maternity page.

Required Gynaecology fallback tests:

Missing Obstetrics profile mapping shows unavailable reason.
Standard create-consultation link is usable.
External/raw return URL is not accepted.
Existing Gynaecology route remains unchanged.
Valid mapping renders real referral confirmation.
Repeated referral reuses/detects the existing route.
Target route links to the same Pregnancy Profile.
No ANC/Labor/Admission Request is created.

Required Postnatal review tests:

Review link modal/form exists.
Existing same-patient Postnatal Case can be linked.
No case or observation is created.
Completed Consultation can open/view according to approved bridge policy.
Return context is safe.

Required lifecycle and permission tests:

Bridge permission without target permission renders disabled reason.
Target permission without bridge permission renders disabled reason.
Active Consultation mutation action is executable.
Completed Consultation action is not executable.
Context-management action remains available where approved.
Emergency closed/incompatible state renders blocked reason.
Admission discharged state prevents inappropriate context mutation where
current service policy requires it.

Required performance assertions:

Flags off add zero queries.
Selector candidates are not loaded until selector is opened.
Modal partials execute no queries.
One presenter/view model is reused per workspace.
No per-button request lookup N+1.

Regression:

All four Phase 14R.5 handoff suites remain green.
Phase 14R.2–14R.4.1 suites remain green.
Emergency suites remain green.
Admission foundation/bed/nursing/discharge suites retain baseline.
Maternity Phase 8–12 suites retain baseline.
tests/Feature/Consultations retains exactly the documented baseline.
No invoice item is created.
Maternity Billing Posting remains disabled.
EN/FR parity passes.

Run:

php artisan test tests/Feature/MaternityHandoffUiPhase14R5_1Test.php
php artisan test tests/Feature/MaternityHandoffModalIntegrityPhase14R5_1Test.php
php artisan test tests/Feature/MaternityHandoffSelectorsPhase14R5_1Test.php

php artisan test tests/Feature/ConsultationMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/EmergencyMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/AdmissionMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/MaternityOperationalHandoffsPhase14R5Test.php

php artisan test tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php
php artisan test tests/Feature/ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php
php artisan test tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php
php artisan test tests/Feature/ConsultationMaternityBridgePhase14R2Test.php

php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php
php artisan test tests/Feature/AdmissionBedWorkflowPhase4Test.php
php artisan test tests/Feature/AdmissionBedWorkflowPhase5Test.php
php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php
php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php

php artisan test tests/Feature/MaternityFoundationPhase8Test.php
php artisan test tests/Feature/AntenatalCarePhase9Test.php
php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php
php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php
php artisan test tests/Feature/PostnatalCarePhase12Test.php

php artisan test tests/Feature/Consultations

php artisan route:list --name=consultation
php artisan route:list --name=emergency
php artisan route:list --name=admissions
php artisan route:list --name=maternity
php artisan view:clear
php artisan config:clear
git diff --check -- . ':!docs/prompt.md'

Only run the optional selector test command when that test file is created.

Run PHP syntax checks on all changed PHP, Blade and localisation files.

Run Blade compile/lint using the existing project-safe method.

Baseline handling:

tests/Feature/Consultations may retain exactly 22 documented pre-existing
failures.
AntenatalCarePhase9Test may retain its documented pre-existing failure.
AdmissionNursingCarePhase6Test may retain its documented pre-existing
failure.
Phase 14R.5.1 must introduce zero new failures.
Do not run composer test:wide.
Manual pilot acceptance

A. Consultation → Admission Request

Open an active Obstetrics Consultation with explicit Pregnancy Profile.
Open the modal.
Submit an Admission Request.
Confirm one request.
Repeat and confirm reuse.
Confirm no Admission, bed reservation or invoice exists.

B. Emergency Pregnancy context

Open an Emergency case with no link.
Open selector.
Confirm profiles load only then.
Link a profile.
Start Labor.
Repeat and confirm the existing episode opens.
Create Admission Request.
Repeat and confirm reuse.

C. Admission context

Convert a Maternity-aware request.
Open Admission Maternity card.
Confirm propagated context.
Open correction modal.
Confirm historical link preservation after relink.
Confirm bed/nursing/MAR remain unchanged.

D. Maternity → Emergency

Mark Labor/Postnatal escalation.
Confirm no Emergency case is created.
Open explicit handoff dialog.
Confirm and create/reuse Emergency case.
Return to the original Maternity page.

E. Gynaecology referral fallback

Remove/disable Obstetrics profile mapping in the test setup.
Confirm unavailable reason.
Use standard create-consultation flow.
Confirm the original Gynaecology Consultation is unchanged.

F. Permissions

Test each action with:
bridge permission only
target permission only
both permissions
Confirm only both makes the form executable.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_HANDOFF_UI_PHASE_14R_5_1_REPORT.md

The report must include:

Exact trigger/modal audit inventory.
Missing targets discovered.
Files changed.
Modal architecture used.
Typed action presentation contract.
Consultation Admission Request UI.
Emergency link/profile UI.
Emergency Labor UI.
Emergency Admission Request UI.
Admission context-management UI.
Maternity → Emergency UI.
Postnatal review UI.
Gynaecology referral fallback.
Disabled/unavailable states.
Lifecycle boundaries.
Idempotent result display.
Return-context security.
Validation/error behavior.
Permission behavior.
Feature-flag behavior.
Query-count measurements.
Tests/checks.
Baseline comparison.
Manual pilot results.
Existing workflows protected.
Known risks.
Rollout and rollback.
Confirmation that K2 is closed.
Next recommended phase.

Update:

docs/maternity/OBGYN_MATERNITY_HANDOFFS_PHASE_14R_5_REPORT.md
docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Mark K2 CLOSED only after:

every visible Phase 14R.5 trigger has a real target
every target form reaches its real server action
permission/lifecycle-disabled actions are not rendered as executable
candidate/profile queries remain lazy or bounded
manual scenarios A–F pass
Rollout

All flags remain false after deployment:

MATERNITY_CONSULTATION_HANDOFFS_ENABLED=false
MATERNITY_EMERGENCY_CONTEXT_ENABLED=false
MATERNITY_ADMISSION_CONTEXT_ENABLED=false
MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false

Pilot order remains:

Admission read-only Maternity context.
Emergency read-only Maternity context.
Consultation handoff actions.
Emergency/Maternity handoff actions last.

Do not expose mutation modals in read-only context mode.

Before enabling mutation actions:

verify permissions
verify active/open duplicate rules
verify modal validation and return context
confirm no billing posting
confirm no modal target is missing
confirm manual scenarios pass
Rollback
Disable Emergency handoffs.
Disable Consultation handoffs.
Disable Emergency context.
Disable Admission context.
Clear config cache.

Results:

action controls and modal bodies disappear
read-only cards disappear according to flags
existing links remain
existing Admission Requests, Admissions, Emergency Cases and Maternity
records remain valid
no destructive migration rollback is required
no historical link is deleted
Boundaries

Do not implement Phase 14R.6 summary projection.
Do not implement immutable completion snapshots.
Do not change readiness.
Do not run historical reconciliation.
Do not enable Maternity billing posting.
Do not add billing cards.
Do not create a new referral subsystem.
Do not create a new selector framework.
Do not rewrite target-domain services.
Do not manually insert Emergency, Admission, Labor or Maternity records.
Do not alter source_type/source_id semantics.
Do not reinterpret ambiguous legacy source rows.
Do not rename existing routes.
Do not modify default launch seeders.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Trigger/modal inventory.
Missing targets fixed.
UI behavior by module.
Lifecycle and permission behavior.
Idempotency behavior.
Query counts.
Tests and baseline comparison.
Manual acceptance results.
Existing workflows protected.
Known risks.
Confirmation that K2 is closed.
Rollout and rollback.
Next phase recommendation.

Next phase after Phase 14R.5.1 passes:

Phase 14R.6 — Advisory Readiness, Consultation Summary Projection,
Immutable Completion Snapshot, Historical Reconciliation Dry Run and
Billing De-duplication Policy.
