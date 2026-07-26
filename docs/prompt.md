You are working inside the UHMS Laravel project.

Completed O&G ↔ Maternity reconciliation phases:

- Phase 14R.1:
  - Consultation Specialty and Maternity domain audit.
  - Field-level source-of-truth matrix.
  - Integration architecture.

- Phase 14R.2:
  - Consultation ↔ Maternity explicit-FK bridge.
  - Link/relink/unlink lifecycle.
  - Context resolver.
  - Ambiguity handling.
  - Activity logging and permissions.

- Phase 14R.3:
  - Obstetrics stage-aware workspace.
  - Pregnancy Profile dating method.
  - Explicit Create Profile, Record ANC and Start Labor actions.
  - Server-side maternity-owned field guard.

- Phase 14R.3.1:
  - Obstetrics ribbon and panel wired into the real Consultation workspace.
  - Clinical mutation boundary hardened.
  - Query counts measured and optimised.

- Phase 14R.4:
  - Gynaecology explicit-only Pregnancy Profile context.
  - One-way LMP adoption.
  - Gynaecology obstetric-history ownership.
  - Obstetrics current_pregnancy and birth_plan conversion.
  - Specialty-entry guard moved to the service boundary.
  - Order-set bypass closed.
  - Maternity-shaped order-set actions retargeted.

- Phase 14R.4.1:
  - Gynaecology context card wired into the real Consultation workspace.
  - Retargeted maternity_context_action items receive typed clinician-facing rendering.
  - Risks R1 and R2 closed.
  - Decision R5 closed.

Current feature flags remain dark by default:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

Current documented baselines:

- New 14R.4.1 tests: 22 passing.
- tests/Feature/Consultations:
  - 230 passed
  - 22 documented pre-existing failures
- Maternity suites:
  - one documented pre-existing ANC failure
- Phase 14R.2–14R.4.1 introduced zero new failures.
- Maternity billing remains preview-only.
- MATERNITY_BILLING_ENABLED=false by default.
- MaternityBillingPostingService::postForSource() remains non-posting.
- No billing card exists in the doctor Consultation workspace.

Now implement Phase 14R.5:

Admission, Emergency, Labor, Delivery and Postnatal Handoff Integration.

Goal:

Create durable, explicit and idempotent operational handoffs between:

- Consultation
- Emergency
- Admission Requests
- Admissions
- Pregnancy Profiles
- ANC
- Labor
- Delivery
- Newborn
- Postnatal

while preserving clear ownership:

- Consultation owns the specialist encounter.
- Emergency owns triage, emergency treatment, bay, emergency notes and disposition.
- Admission owns bed, ward, nursing, medication/MAR and discharge.
- Maternity owns Pregnancy Profile, ANC, Labor, Delivery, Newborn and Postnatal.
- Existing order systems continue owning investigations, radiology, procedures,
  prescriptions, pharmacy and tasks.

The integration must link existing records and invoke existing services.

It must not create parallel emergency, admission, labor, delivery or postnatal
engines inside Consultation.

It must not silently duplicate clinical records.

It must not silently change operational ownership.

Important boundaries:

- Do not implement summary projection or immutable completion snapshots.
- Do not implement historical O&G specialty-entry reconciliation.
- Do not enable Maternity billing posting.
- Do not change consultation readiness.
- Do not change admission discharge enforcement defaults.
- Do not rewrite Emergency.
- Do not rewrite Admission.
- Do not rewrite Maternity.
- Do not implement Theatre handoff in this phase beyond preserving current
  escalation flags and navigation.
- Do not run composer test:wide.
- Do not touch docs/prompt.md.

1. Audit all existing handoff and creation services first

Before implementation, inspect and document the existing runtime paths for:

Consultation:

- Consultation route/session creation.
- Consultation referral or department handoff.
- Route assignment and open-next behavior.
- Consultation route completion and reopening.
- Existing return-URL handling.
- Existing O&G Maternity bridge actions.

Emergency:

- Emergency case creation.
- Existing patient and temporary-patient emergency entry.
- Emergency visit creation/status.
- Emergency session creation.
- Emergency disposition.
- Emergency-to-admission flow.
- Emergency bay assignment/release.
- Emergency case logs.
- Emergency duplicate prevention.
- Existing emergency service/controller classes.

Admission:

- AdmissionRequestService.
- Admission request source_type/source_id behavior.
- Admission request acceptance/reservation/conversion.
- AdmissionService.
- Emergency-origin admission behavior.
- Bed reservation and transfer.
- Admission location history.
- Nursing workspace.
- Discharge readiness.

Maternity:

- PregnancyProfileService.
- MaternityCaseService.
- AntenatalVisitService.
- LaborEpisodeService.
- LaborObservationService.
- DeliveryRecordService.
- NewbornRecordService.
- PostnatalCaseService.
- Existing ANC/Labor admission-request actions.
- Labor emergency/theatre escalation flags.
- Existing postnatal referral behavior.
- Existing links to visit_id and admission_id.

Orders:

- Investigation request creation.
- Radiology request creation.
- Procedure/theatre request creation.
- Prescription creation.
- Task creation.

Identify:

- Every existing durable handoff record.
- Every source/target ID convention.
- Every duplicate-prevention mechanism.
- Every idempotency mechanism.
- Every activity-log convention.
- Every route used to return to an originating workspace.
- Any current service that performs database writes during route/view loading.

Do not add a generic handoff engine until the existing mechanisms are fully
understood.

2. Ratify the operational ownership matrix in code and documentation

The following ownership rules are mandatory.

Consultation:

- Complaints.
- HOPC.
- Examination and specialist narrative.
- Diagnoses.
- Assessment.
- Encounter plan.
- Orders.
- Encounter-level notes.
- Consultation completion.
- Consultation summary.

Emergency:

- Emergency arrival and triage.
- Emergency acuity.
- Emergency vitals and notes.
- Bay assignment.
- Emergency treatment.
- Emergency tasks.
- Emergency disposition.
- Emergency-to-admission decision.

Admission:

- Admission request review.
- Bed reservation.
- Admission conversion.
- Ward/bed.
- Nursing notes and tasks.
- Medication/MAR.
- Admission transfers.
- Discharge planning and discharge.

Maternity:

- Pregnancy Profile.
- ANC Visit.
- Labor Episode.
- Labor Observation.
- Delivery Record.
- Newborn Record.
- Postnatal Case.
- Postnatal mother/newborn observations.
- Maternity risk and readiness.

Rules:

- A handoff may create a link or a new record through the target domain service.
- The source module must not duplicate the target module’s record.
- The source record must remain intact and auditable.
- The target record must retain its normal lifecycle.
- One operational event must not silently create two target records.
- Existing source and target history must never be rewritten to simulate a new
  architecture.

3. Add dark-by-default integration flags

Prefer a central configuration file such as:

`config/maternity.php`

or extend an existing maternity integration config if one already exists.

Recommended flags:

```php
'integration' => [
    'consultation_handoffs_enabled' => env(
        'MATERNITY_CONSULTATION_HANDOFFS_ENABLED',
        false
    ),

    'emergency_context_enabled' => env(
        'MATERNITY_EMERGENCY_CONTEXT_ENABLED',
        false
    ),

    'admission_context_enabled' => env(
        'MATERNITY_ADMISSION_CONTEXT_ENABLED',
        false
    ),

    'emergency_handoffs_enabled' => env(
        'MATERNITY_EMERGENCY_HANDOFFS_ENABLED',
        false
    ),
],

All flags must default false.

Flag behavior:

A. All flags false

No new Emergency Maternity card.
No new Admission Maternity card.
No handoff actions.
No additional link/resolver queries.
Existing Emergency/Admission/Consultation behavior remains unchanged.

B. Context flags enabled, handoff flags disabled

Read-only Maternity context may be shown.
Navigation to existing records may be shown.
No new emergency/admission/maternity records are created.

C. Handoff flags enabled

Explicit actions become available according to permissions and lifecycle.
No action executes automatically from a warning, diagnosis, risk flag or
context match.

The existing four Obstetrics/Gynaecology flags remain independent.

Extract shared Maternity target derivation

The ConsultationMaternityLinkService currently knows how to:

map supported Maternity models to context types
derive Pregnancy Profile roots
validate patient ownership
handle mother_patient_id for Newborn/Postnatal targets
reject unsupported and inconsistent targets

Extract that reusable logic into a shared domain service such as:

MaternityContextTargetService

or:

MaternityContextTargetDescriptorFactory

It should support:

PregnancyProfile
MaternityCase
AntenatalVisit
LaborEpisode
DeliveryRecord
NewbornRecord
PostnatalCase

It should return a typed descriptor containing:

context type
target model/class
target ID
Pregnancy Profile ID
mother/patient ID
visit ID where available
admission ID where available
target FK payload
warnings

Rules:

ConsultationMaternityLinkService must delegate to the shared service.
Existing bridge behavior and tests must remain unchanged.
Patient ownership remains fail-closed.
Newborn and Postnatal contexts continue validating through mother_patient_id.
Visit mismatch remains allowed for longitudinal links.
Do not weaken any Phase 14R.2 validation rule.
Add Emergency ↔ Maternity explicit links

Create:

emergency_maternity_links

Use the same approved explicit-FK and active-slot design as
consultation_maternity_links.

Recommended fields:

id
emergency_case_id
pregnancy_profile_id nullable
maternity_case_id nullable
antenatal_visit_id nullable
labor_episode_id nullable
delivery_record_id nullable
newborn_record_id nullable
postnatal_case_id nullable
context_type
link_role
linked_by nullable
linked_at
unlinked_by nullable
unlinked_at nullable
reason nullable
metadata nullable JSON
active_slot nullable tiny integer
timestamps

Uniqueness:

active link:
active_slot = 1
historical link:
active_slot = null
unique:
emergency_case_id
context_type
active_slot

Add:

EmergencyMaternityLink
EmergencyMaternityLinkService
EmergencyMaternityContextResolver
typed Emergency Maternity context/view model

Required behavior:

Same-target link is idempotent.
Different target requires explicit relink with reason.
Unlink preserves history.
Patient ownership is validated.
No profile is auto-created.
No context is inferred from pregnancy-related emergency complaint/diagnosis.
Same-visit context may be displayed as suggested only if explicitly designed
and clearly labelled.
Suggested context is never persisted automatically.
Multiple Pregnancy Profiles return ambiguous.
No Labor Episode is started during context resolution.
No Emergency Case is created during context resolution.
Add Admission Request ↔ Maternity context links

Do not overload admission_requests.source_type/source_id.

Operational source must remain truthful.

Examples:

Request created by Emergency:
source_type = emergency
source_id = EmergencyCase ID
Request created from Consultation:
source_type = consultation
source_id = VisitConsultationRoute ID
Request created directly from Maternity:
source_type = maternity
source_id remains its current source ID behavior

Add a separate explicit context table:

admission_request_maternity_links

Recommended fields:

id
admission_request_id
pregnancy_profile_id nullable
maternity_case_id nullable
antenatal_visit_id nullable
labor_episode_id nullable
delivery_record_id nullable
newborn_record_id nullable
postnatal_case_id nullable
context_type
link_role
linked_by nullable
linked_at
unlinked_by nullable
unlinked_at nullable
reason nullable
metadata nullable
active_slot nullable
timestamps

Use the same active-slot uniqueness strategy:

one active link per request/context type
unlimited historical rows

Add:

AdmissionRequestMaternityLink
AdmissionRequestMaternityLinkService

Rules:

Operational source remains separate from clinical Maternity context.
Existing requests without this table remain valid.
Existing source_type=maternity requests remain valid.
Do not automatically reinterpret ambiguous legacy source IDs.
Legacy ambiguous requests should show a warning rather than guessing the
source model.
Context links are created only by explicit handoff actions or known
idempotent service paths.
Add Admission ↔ Maternity explicit links

Create:

admission_maternity_links

Use the same target columns, roles, audit fields and active-slot uniqueness.

Add:

AdmissionMaternityLink
AdmissionMaternityLinkService
AdmissionMaternityContextResolver
typed Admission Maternity view model

Resolution order:

Explicit active Admission Maternity links.
Active links carried from the Admission Request.
Maternity records whose admission_id matches the Admission.
Single Pregnancy Profile candidate.
None/ambiguous.

Rules:

Never choose “latest” between multiple Pregnancy Profiles.
Never create a link during resolution.
Never create a Maternity record during resolution.
Direct non-maternity admissions remain unaffected.
Existing admissions remain valid without any new link.
Newborn contexts continue validating against the mother for this O&G
integration.
A separate neonatal/paediatric admission bridge is out of scope.
Add request-to-admission context propagation

Update the existing admission conversion path safely.

When an Admission Request with active Maternity context links is converted:

Preserve the Admission Request.
Create corresponding Admission Maternity links with:
link_role = handoff
Keep source context IDs.
Preserve the operational origin:
Emergency
Consultation
Maternity
direct
Do not duplicate active links.
Run context propagation in the same transaction as safe admission conversion,
or in a transactionally consistent post-conversion service step.
A failure to propagate context must not leave a partially converted admission
without a clear error/rollback strategy.

Maternity stage records:

Where the relevant source model contains admission_id:

If admission_id is null and the stage record logically belongs to this
admission:
populate it through the relevant Maternity service or safe domain method
If admission_id already equals this Admission:
treat idempotently
If admission_id points to another Admission:
do not overwrite
record a conflict warning
require review

Do not force PregnancyProfile.admission_id to represent every future admission.

The durable source of current admission linkage should be
admission_maternity_links, not a single overwritable Pregnancy Profile field.

Add Consultation → Admission Request handoff

Add permission:

consultation.maternity_context.create_admission_request

Also require:

admission.requests.create

This action is a clinical/operational mutation and must require:

active/editable Consultation via mutableRoute()
explicit valid Pregnancy Profile context
relevant underlying Maternity context permission
relevant Admission Request permission

Available from Obstetrics only.

Do not expose it in Gynaecology by default.

Recommended behavior:

Allow creating an Admission Request from:
Pregnancy Profile
ANC Visit
active Labor Episode
Maternity Case
Use the most specific explicit linked context available.
source_type = consultation
source_id = VisitConsultationRoute ID
create AdmissionRequest Maternity link(s)
copy only safe handover fields:
priority
requested ward
provisional diagnosis where selected/available
short clinical summary
Do not copy full consultation notes into request metadata.
Do not post billing.
Do not auto-admit.
Do not reserve a bed automatically unless the existing request workflow
already explicitly supports that user action.

Duplicate prevention:

Reuse an existing open request for:
same patient
same source Consultation route
same Pregnancy Profile
same most-specific Maternity context
Rejected, cancelled or converted requests are not reused as open.
Repeated button clicks must not create duplicate open requests.
Use locking/transactional duplicate protection.

After creation:

Show request ID/status.
Link to Admission Request detail.
Return to the same Obstetrics Consultation Maternity panel.
Do not create a duplicate specialty entry.
Add explicit Gynaecology → Obstetrics/Maternity referral handoff

Gynaecology must remain Gynaecology.

Audit the existing Consultation referral/route creation service.

If a safe existing service exists:

Add an explicit action:

“Refer to Obstetrics/Maternity”

Requirements:

Current Gynaecology route remains unchanged.
Original entries remain intact.
An explicit linked Pregnancy Profile is required.
The new target Consultation/referral uses the existing routing service.
Do not invent a parallel consultation route engine.
Link the target Obstetrics consultation route to the same Pregnancy Profile
with:
link_role = handoff
Do not create ANC automatically.
Do not start Labor automatically.
Do not create an Admission Request automatically.
Repeated action should reuse or detect an existing open equivalent referral
where the existing system supports idempotency.

If the existing referral architecture cannot safely create a target route:

Add a link into the existing standard referral/create-consultation flow with
safe preselected context.
Do not build a new referral subsystem in this phase.
Document the remaining limitation.

Add permission only if needed:

consultation.maternity_context.refer_obstetrics

Underlying consultation/referral permission must also pass.

Add Emergency Maternity context UI

Behind:

MATERNITY_EMERGENCY_CONTEXT_ENABLED=false

Add a compact Maternity context card/ribbon to the Emergency case workspace.

Use the real Emergency workspace data composer.

Do not query inside Blade.

When disabled:

no resolver invocation
no new queries
no UI change

When no explicit context exists:

do not create or infer pregnancy
show a discreet Link/Create Pregnancy Profile action only when permitted
same-visit candidate may be shown as suggested, never silently linked

When explicitly linked, show:

Pregnancy Profile
gestational age
EDD
risk
latest ANC
active Labor Episode
admission request/admission state
Delivery/Postnatal state where relevant
source-of-truth labels
links to Maternity records

Emergency retains ownership of:

triage
emergency notes
emergency vitals
bay
emergency treatment
emergency tasks
disposition

Do not duplicate Emergency clinical fields in Maternity.

Add explicit Emergency → Maternity actions

Add permissions additively as needed:

emergency.maternity_context.view
emergency.maternity_context.link
emergency.maternity_context.unlink
emergency.maternity_context.create_profile
emergency.maternity_context.start_labor
emergency.maternity_context.create_admission_request

Every action also requires the corresponding underlying Maternity or Admission
permission.

Actions:

A. Link/Create Pregnancy Profile

Explicit only.
Same-patient validation.
No profile from complaint/diagnosis/test.
Use PregnancyProfileService.
Create Emergency Maternity link with created or primary.

B. Start/Re-use Labor Episode

Explicit valid Pregnancy Profile required.
If active Labor Episode exists:
reuse/open it
do not create another
Otherwise use LaborEpisodeService.
Carry Emergency visit/admission/department context where safe.
Link Labor Episode to Emergency case.
Do not auto-start from danger signs or emergency diagnosis.

C. Create/Re-use Admission Request

Use the existing Emergency admission/disposition flow.
source_type remains emergency.
source_id remains EmergencyCase ID.
Attach active Emergency Maternity contexts through
AdmissionRequestMaternityLinkService.
Repeated actions reuse the open request.
Do not create a second request for the same emergency/context.
Do not change Emergency disposition history.
Existing emergency-to-admission behavior remains intact when no Maternity
context exists.
Add Maternity → Emergency explicit escalation handoff

Labor and Postnatal already store Emergency escalation/referral indicators.

Do not create Emergency cases automatically from those flags.

Behind:

MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false

Add explicit actions from:

Labor Episode
Delivery Record where clinically appropriate
Postnatal Case

Action:

“Create/Open Emergency Case”

Required behavior:

Use the existing Emergency case creation service/controller path.
Do not manually insert EmergencyCase rows.
Reuse an existing active linked Emergency case when one exists.
Do not create duplicate Emergency cases for repeated clicks.
Create Emergency Maternity links with link_role=handoff.
Preserve Maternity source records.
Keep Emergency as operational owner after handoff.
Preserve originating Maternity return URL.
Do not create Admission Request automatically unless the clinician explicitly
chooses Emergency disposition/admission later.
Do not create Theatre cases in this phase.

Permissions:

a bridge/handoff permission such as:
maternity.emergency_handoff.create
plus the existing Emergency case-create permission.
Add Admission Maternity context UI

Behind:

MATERNITY_ADMISSION_CONTEXT_ENABLED=false

Add a Maternity context tab/card to:

full Admission workspace
simplified nurse Admission view where appropriate

Use one prepared Admission Maternity view model.

Do not query in Blade.

Show:

Pregnancy Profile
Pregnancy status
gestational age and EDD
ANC summary
active Labor Episode
latest Labor observation
Delivery Record
Newborn summary
Postnatal readiness
Emergency origin where applicable
Admission Request origin
current ward/bed
cross-links to Maternity records

Do not duplicate forms for:

ANC
Labor observations
Delivery
Newborn
Postnatal observations

The Admission workspace may provide:

Open Pregnancy Profile
Open ANC history
Open Labor workspace
Open Delivery record
Open Newborn record
Open Postnatal case

Clinical writes remain in Maternity.

Admission continues owning:

bed
ward
nursing
MAR
admission care flags
discharge planning
discharge summary
Add explicit Admission context management

Add permissions:

admission.maternity_context.view
admission.maternity_context.link
admission.maternity_context.unlink

Underlying Maternity view/update permissions still apply.

Allow:

Link existing Pregnancy Profile/context.
Relink with reason.
Unlink with reason.
Correct an incorrectly propagated request context.
Open target Maternity records.

Do not allow:

Auto-create Pregnancy Profile merely because the patient is admitted.
Auto-start ANC.
Auto-start Labor.
Auto-create Delivery/Newborn/Postnatal.
Auto-infer between multiple active Pregnancy Profiles.

For a maternity-linked Admission, an explicit Pregnancy Profile link is the
root context.

Integrate Postnatal with Admission discharge readiness

Phase 12 already added an advisory Postnatal readiness area.

Use the explicit Admission Maternity context when available.

Required behavior:

Admission discharge readiness can resolve the linked PostnatalCase directly.
Mother readiness and Newborn readiness remain Maternity-owned.
Admission readiness displays advisory warning/state.
Default enforcement remains disabled:
ADMISSION_REQUIRE_POSTNATAL_READY_BEFORE_DISCHARGE=false
Do not change the existing final discharge flow.
Do not copy Postnatal observations into Admission nursing notes.
Do not create Postnatal observations from the Admission view.
Do not block general non-maternity admissions.
Add Postnatal review Consultation handoff

Add permission:

consultation.maternity_context.open_postnatal

Also require:

maternity.postnatal.view

From an Obstetrics or appropriate follow-up Consultation:

Allow linking an existing same-patient PostnatalCase using
ConsultationMaternityLinkService.
Use link_role=reviewed or handoff.
Show Postnatal readiness and latest observations read-only.
Open the Maternity Postnatal workspace for recording observations.
Keep the Consultation note encounter-owned.
Do not duplicate mother/newborn observations.
Do not change Consultation completion or readiness.

If a Consultation route is created from the Postnatal module using an existing
referral/follow-up service:

link it to the PostnatalCase
preserve the originating Maternity return context
do not create a second PostnatalCase
Define idempotency and duplicate-prevention rules

All handoff actions must be idempotent.

Required duplicate identities:

Consultation → Admission Request:

consultation route
Pregnancy Profile
most-specific Maternity target
open request status

Emergency → Labor:

Emergency case
Pregnancy Profile
active Labor Episode

Emergency → Admission Request:

Emergency case
Pregnancy Profile/context
open request status

Maternity → Emergency:

Maternity source record
active Emergency case
same patient/visit where applicable

Request → Admission propagation:

admission request
admission
context type
target ID

Postnatal → Consultation review:

PostnatalCase
target Consultation route/referral state

Rules:

Repeated clicks return existing records.
Concurrent clicks must be protected by transaction/locking or unique indexes.
Same-target links remain idempotent.
Different targets require relink/correction.
Completed/rejected/cancelled records must not be mistaken for active records.
Do not suppress legitimate repeat events such as separate future admissions.
Add safe return-context handling

Cross-module navigation must preserve where the clinician came from.

Do not accept arbitrary external return URLs.

Create or reuse a safe return-context mechanism such as:

MaternityReturnContext

It may carry:

source module
source record ID
route name
consultation route ID
emergency case ID
admission ID
panel/anchor
signed or validated query data

Rules:

Only internal named routes may be used.
Authorisation is rechecked on return.
No open redirect.
Invalid return context falls back to the target module’s normal show page.
Do not create browser-history loops.
Do not silently discard unsaved data.
Use the existing dirty-state/navigation protection in Consultation.
Add shared read-only components

Reuse and generalise existing service-backed components where practical:

Pregnancy summary card
ANC summary card
Labor summary card
Delivery summary card
Newborn summary card
Postnatal summary card
Maternity context warning
Handoff status card
Operational ownership badge

Components may be used in:

Obstetrics Consultation
Gynaecology Consultation
Emergency
Admission
Maternity

Rules:

No component may query independently.
No component may recreate Maternity business logic.
No duplicate editing forms.
Every card clearly identifies:
owner module
source record
open/navigation action
Add activity logging

Use identifier-only logs.

Suggested events:

CONSULTATION_MATERNITY_ADMISSION_REQUEST_CREATED
GYNAECOLOGY_OBSTETRICS_HANDOFF_CREATED
EMERGENCY_MATERNITY_CONTEXT_LINKED
EMERGENCY_LABOR_EPISODE_STARTED
EMERGENCY_MATERNITY_ADMISSION_REQUEST_CREATED
MATERNITY_EMERGENCY_HANDOFF_CREATED
ADMISSION_MATERNITY_CONTEXT_PROPAGATED
ADMISSION_MATERNITY_CONTEXT_LINKED
POSTNATAL_CONSULTATION_REVIEW_LINKED

Metadata:

source module
source record ID
target module
target record ID
Pregnancy Profile ID
admission request/admission/emergency/consultation IDs
actor
timestamp
handoff role

Do not log:

full clinical notes
sexual history
ANC measurements
Labor observations
Newborn measurements
Postnatal observations
Add permissions additively

Consultation:

consultation.maternity_context.create_admission_request
consultation.maternity_context.refer_obstetrics
consultation.maternity_context.open_postnatal

Emergency:

emergency.maternity_context.view
emergency.maternity_context.link
emergency.maternity_context.unlink
emergency.maternity_context.create_profile
emergency.maternity_context.start_labor
emergency.maternity_context.create_admission_request

Admission:

admission.maternity_context.view
admission.maternity_context.link
admission.maternity_context.unlink

Maternity:

maternity.emergency_handoff.create

Every bridge permission also requires the target domain’s existing permission.

Examples:

Start Labor from Emergency:

emergency.maternity_context.start_labor
maternity.labor.start

Create Admission Request from Consultation:

consultation.maternity_context.create_admission_request
admission.requests.create

Create Emergency Case from Labor:

maternity.emergency_handoff.create
existing Emergency case-create permission

Open Postnatal:

consultation.maternity_context.open_postnatal
maternity.postnatal.view

The bridge must never escalate target-module access.

Do not remove existing permissions.

Add EN/FR localisation

Keep strict recursive parity.

Include keys for:

Ownership:

Operational owner
Consultation encounter
Emergency episode
Admission episode
Maternity longitudinal record
Source of truth
Handoff context

Consultation handoff:

Create Admission Request
Existing Admission Request
Admission Request created from Consultation
Refer to Obstetrics/Maternity
Current consultation remains Gynaecology
Open Obstetrics referral
Open Postnatal review

Emergency:

Emergency Maternity Context
Link Pregnancy Profile
Start/Open Labor
Create/Open Admission Request
Emergency remains owner of acute care
Maternity owns pregnancy and labor
Suggested context
Confirm link
Ambiguous Pregnancy Profile

Admission:

Admission Maternity Context
Context carried from Admission Request
Context linked directly
Open Pregnancy Profile
Open Labor
Open Delivery
Open Newborn
Open Postnatal
Maternity context conflict

Handoffs:

Handoff created
Handoff already exists
Handoff unavailable
Handoff blocked
Record reused
Duplicate prevented
Context propagation complete
Context propagation conflict
Return to Consultation
Return to Emergency
Return to Admission
Return to Maternity

Postnatal:

Postnatal review
Postnatal readiness is advisory
Record observations in Maternity
No observations were duplicated
Tests

Create:

tests/Feature/ConsultationMaternityHandoffsPhase14R5Test.php

Create:

tests/Feature/EmergencyMaternityHandoffsPhase14R5Test.php

Create:

tests/Feature/AdmissionMaternityHandoffsPhase14R5Test.php

Create:

tests/Feature/MaternityOperationalHandoffsPhase14R5Test.php

Required shared-link tests:

Shared target descriptor preserves all Phase 14R.2 derivation behavior.
Consultation bridge tests remain unchanged.
Emergency same-target link is idempotent.
Emergency relink requires reason.
Admission link validates patient ownership.
Newborn/Postnatal links validate mother patient.
One active link per source/context type.
Historical rows are preserved.

Required scenario A — Obstetrics outpatient:

Linked Obstetrics Consultation can create one Admission Request.
Request source remains consultation.
Request receives Maternity context link.
Repeated action reuses the open request.
No automatic admission occurs.
No billing occurs.
Existing ANC/Labor records are not duplicated.

Required scenario B — Gynaecology, no pregnancy:

No Maternity context is required.
No handoff action runs automatically.
Existing Gynaecology completion remains unchanged.

Required scenario C — Gynaecology discovers pregnancy:

Linking/creating profile leaves specialty Gynaecology.
Explicit Obstetrics/Maternity referral preserves original route and entries.
Target route/referral is linked to the same Pregnancy Profile if implemented.
Repeated referral does not duplicate an open equivalent route.
No ANC/Labor/Admission Request is created automatically.

Required scenario D — Emergency obstetric case:

Emergency context feature off adds zero resolver calls.
Explicit Pregnancy Profile can be linked.
Patient mismatch is blocked.
Start Labor creates exactly one LaborEpisode.
Existing active LaborEpisode is reused.
Emergency admission request source remains emergency.
Admission Request receives Maternity context links.
Repeated admit/request action does not duplicate the request.
Emergency bay/disposition history remains intact.
No Emergency or Labor record is created by context resolution.

Required Maternity → Emergency:

Labor escalation flag alone creates no Emergency case.
Explicit action creates one Emergency case through existing service.
Repeated action reuses existing active linked case.
Emergency Maternity link is created.
Postnatal explicit emergency handoff works where allowed.
No Admission Request is created automatically.
No Theatre case is created.

Required scenario E — admitted obstetric patient:

Request context propagates to Admission.
One Admission Maternity link per context.
Repeated propagation is idempotent.
Admission shows Pregnancy/Labor/Delivery/Postnatal context.
Bed/Nursing/MAR/Discharge remain Admission-owned.
Maternity records remain Maternity-owned.
Conflicting existing admission_id is not overwritten.
Direct non-maternity admission remains unaffected.
Context feature off adds zero resolver calls.

Required scenario F — Postnatal review:

Consultation links to existing PostnatalCase.
Consultation shows read-only readiness/observation summary.
No new mother/newborn observation is created.
Open Postnatal action navigates to existing record.
Consultation note remains encounter-owned.
Admission discharge readiness remains advisory by default.

Return-context tests:

Consultation → Maternity → Consultation returns to the same route/panel.
Emergency → Labor → Emergency returns safely.
Admission → Postnatal → Admission returns safely.
External return URLs are rejected.
Unauthorized return target falls back safely.

Permission tests:

Bridge permission without target permission is blocked.
Target permission without bridge permission is blocked.
Clinical creation actions require active/editable Consultation where
applicable.
Completed Consultation can view/open existing records but cannot create a
new Admission Request from the completed encounter.
Gynaecology never receives ANC/Labor mutations.

Regression:

Phase 14R.2 bridge tests remain green.
Phase 14R.3/14R.3.1 Obstetrics tests remain green.
Phase 14R.4/14R.4.1 Gynaecology/order-set tests remain green.
Existing Emergency tests remain green.
Existing Admission foundation/bed/nursing/discharge tests remain green.
Maternity Phase 8–13 tests retain documented baseline.
Existing Consultation suite retains exactly its documented baseline.
No invoice item is created.
Maternity Billing Posting remains disabled.
EN/FR parity passes.

Run targeted checks:

php artisan test tests/Feature/ConsultationMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/EmergencyMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/AdmissionMaternityHandoffsPhase14R5Test.php
php artisan test tests/Feature/MaternityOperationalHandoffsPhase14R5Test.php

php artisan test tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php
php artisan test tests/Feature/ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php
php artisan test tests/Feature/ConsultationGynaecologyMaternityPhase14R4Test.php
php artisan test tests/Feature/ConsultationObgynOrderSetRetargetingPhase14R4Test.php
php artisan test tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php
php artisan test tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php
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

Run PHP syntax checks on every changed PHP, Blade and localisation file.

Baseline handling:

tests/Feature/Consultations may retain exactly 22 documented pre-existing
failures.
AntenatalCarePhase9Test may retain exactly one documented pre-existing
failure.
Any other known broad-suite defects remain outside this targeted phase.
Phase 14R.5 must introduce zero new failures.
Do not run composer test:wide.
Performance verification

Measure:

Consultation:

Obstetrics handoffs disabled.
Obstetrics enabled with explicit context and no request.
Obstetrics with open Admission Request.
Gynaecology handoffs disabled.

Emergency:

context disabled.
enabled with no context.
suggested context.
explicit Pregnancy Profile.
explicit Labor Episode.

Admission:

context disabled.
enabled non-maternity admission.
linked Pregnancy Profile.
linked Labor/Delivery/Postnatal context.

Requirements:

Disabled surfaces add zero Maternity resolver calls.
Non-maternity records use fast no-context paths.
Each surface resolves at most once per request.
Shared cards perform no queries.
No N+1 for Newborn collections.
No repeated Admission Request lookup per component.
No persistent cross-request caching.
Record exact query counts and any optimisation.
Manual acceptance scenarios

Run or document:

A. Obstetrics outpatient

Link Pregnancy Profile.
Record ANC.
Create Admission Request explicitly.
Confirm one request and one ANC record.
Confirm no admission or invoice is created automatically.

B. Gynaecology without pregnancy

Confirm no context or handoff requirement.
Complete normally.

C. Gynaecology discovers pregnancy

Save positive pregnancy test.
Explicitly create/link Pregnancy Profile.
Refer to Obstetrics/Maternity.
Confirm original Gynaecology route and entries remain intact.
Confirm specialty did not change.

D. Emergency obstetric case

Link Pregnancy Profile.
Start/reuse Labor.
Create Admission Request.
Confirm Emergency remains owner of acute episode.
Confirm no duplicate Labor or request.

E. Maternity escalation to Emergency

Mark Labor/Postnatal escalation.
Confirm no Emergency case appears automatically.
Perform explicit handoff.
Confirm one Emergency case and context link.
Repeat action and confirm reuse.

F. Admission conversion

Convert maternity-aware request.
Confirm context propagates.
Confirm Admission shows Maternity summary.
Confirm bed, nursing and discharge remain unchanged.

G. Postnatal review

Link Consultation to PostnatalCase.
Confirm read-only projection.
Record observation in Maternity.
Return to Consultation and confirm projection updates.
Confirm no duplicate observation.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_HANDOFFS_PHASE_14R_5_REPORT.md

Include:

Existing handoff architecture audited.
Files changed.
New tables and models.
Shared target derivation.
Emergency link behavior.
Admission Request context behavior.
Admission context behavior.
Consultation-to-Admission behavior.
Gynaecology referral behavior.
Emergency-to-Labor/Admission behavior.
Maternity-to-Emergency behavior.
Postnatal review behavior.
Operational ownership matrix.
Idempotency strategy.
Return-context security.
Permissions.
Localisation.
Activity logging.
Query counts.
Tests/checks.
Baseline comparison.
Existing workflows protected.
Known risks.
Deferred items.
Rollout.
Rollback.
Next recommended phase.

Update:

docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Mark scenarios A–F complete only after their automated and manual acceptance
criteria are satisfied.

Rollout

Initial state:

MATERNITY_CONSULTATION_HANDOFFS_ENABLED=false
MATERNITY_EMERGENCY_CONTEXT_ENABLED=false
MATERNITY_ADMISSION_CONTEXT_ENABLED=false
MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false

Pilot order:

Enable Admission Maternity read-only context.
Enable Emergency read-only context.
Enable Consultation handoff actions.
Enable explicit Emergency/Maternity handoffs last.

Before rollout:

Confirm bridge permissions.
Confirm target-module permissions.
Confirm open-request duplicate rules.
Confirm active Labor reuse.
Confirm Emergency case reuse.
Confirm return-context security.
Confirm existing Maternity-shaped specialty-entry counts per environment.
Keep all O&G write guards in their current approved rollout state.
Keep Maternity billing disabled.

Rollback order:

Disable Emergency handoff actions.
Disable Consultation handoff actions.
Disable Emergency context.
Disable Admission context.
Clear config cache.

Rollback effects:

Read-only cards disappear.
New handoff actions disappear.
Existing linked records remain auditable.
Existing Emergency, Admission and Maternity records remain valid.
No destructive migration rollback is required.
Historical links remain.
Existing Admission Requests and Admissions remain usable.
Boundaries

Do not implement summary projection.
Do not implement immutable Maternity snapshots.
Do not implement readiness enforcement.
Do not run historical O&G reconciliation.
Do not enable Maternity billing posting.
Do not add billing cards to Consultation, Emergency or Admission.
Do not rewrite Emergency disposition.
Do not rewrite Admission conversion.
Do not rewrite Nursing or MAR.
Do not create duplicate investigation/prescription/procedure engines.
Do not implement Theatre case creation in this phase.
Do not create Emergency cases automatically from escalation flags.
Do not create Labor automatically from diagnosis or danger signs.
Do not create Admission Requests automatically.
Do not switch Gynaecology to Obstetrics automatically.
Do not delete or rewrite historical specialty entries.
Do not rename existing routes.
Do not modify default launch seeders with mass manual data.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Files changed.
New tables and links.
Ownership behavior.
Consultation handoff behavior.
Emergency handoff behavior.
Admission context behavior.
Postnatal review behavior.
Idempotency rules.
Query counts.
Permissions and localisation.
Tests and baseline comparison.
Existing workflows protected.
Known risks.
Rollout and rollback.
Deferred work.
Next phase recommendation.

Recommended next phase:

Phase 14R.6 — Advisory Readiness, Consultation Summary Projection,
Immutable Completion Snapshot, Historical Reconciliation Dry Run and
Billing De-duplication Policy.
