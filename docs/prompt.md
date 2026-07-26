You are working inside the UHMS Laravel project.

The O&G ↔ Maternity reconciliation has been implemented through Phase 14R.6.1.

Delivered architecture:

- Phase 14R.1:
  - Field-level duplication audit.
  - Source-of-truth matrix.
  - Integration architecture.

- Phase 14R.2:
  - Consultation ↔ Maternity explicit-FK bridge.
  - Resolver and link lifecycle.

- Phase 14R.3 / 14R.3.1:
  - Obstetrics stage-aware workspace.
  - Real ribbon/panel integration.
  - Field-level write guard.
  - Correct consultation mutation boundaries.

- Phase 14R.4 / 14R.4.1:
  - Gynaecology explicit-only Pregnancy Profile context.
  - One-way LMP adoption.
  - Order-set bypass closure and retargeting.
  - Real Gynaecology card/action rendering.

- Phase 14R.5 / 14R.5.1:
  - Consultation, Emergency, Admission Request, Admission and Maternity
    handoffs.
  - Typed action rendering.
  - All 19 previously missing modal bodies completed.
  - Lazy, same-patient selectors.
  - Idempotent record-reuse UI.

- Phase 14R.6:
  - Advisory readiness.
  - Curated Maternity summary projection.
  - Immutable, versioned completion snapshots.
  - Reconciliation dry-run command.
  - Billing de-duplication policy.

- Phase 14R.6.1:
  - Real summary tab integration.
  - Completion-snapshot rendering.
  - Snapshot history.
  - Current-record separation.
  - Integrity-state UI.
  - Readiness card.
  - Print integration.
  - K1 closed.

Current technical verification:

- Phase 14R.6.1:
  - 42 new tests passed.
- All Phase 14R.2–14R.6.1 O&G suites:
  - 322 passed.
  - 1 skipped.
- Emergency:
  - 91 passed.
- Admission and Maternity Phase 8–14.1:
  - 72 passed.
  - 2 documented pre-existing failures.
- `tests/Feature/Consultations`:
  - 230 passed.
  - 22 documented pre-existing failures.
- Phase 14R.2–14R.6.1 introduced zero new failures.
- Maternity billing remains disabled and non-posting.
- All integration and traceability flags remain false by default.

Known carry-forward items:

P1:
The summary-preview endpoint returns a rendered Maternity HTML fragment in its
JSON payload, but the current preview JavaScript displays only
`summary.plain_text`. Therefore, the main summary tab, snapshot history and
print surfaces work, but the Maternity block is not yet visible in the preview
modal.

P2:
Snapshot completion identity is second-granular:
Consultation route ID + completed_at.

A reopen and recompletion inside the same second could be treated as the same
completion occurrence. This is a documented edge risk; do not silently redesign
the snapshot architecture in this phase.

P3:
The current environment reconciliation dry run reports zero rows. That proves
the command executes but does not validate classification behavior against real
historical data at scale.

P4:
Clinical usability has not yet been signed off by an Obstetrics/Gynaecology
clinician. Automated tests prove mechanics, not clinical usability.

Now implement Phase 14R.7:

O&G/Maternity Pilot Data, Environment Reconciliation Review, Clinical
Acceptance Package and Wider Regression.

Goals:

1. Close the preview-modal parity gap.
2. Create safe, explicit, removable O&G/Maternity pilot data.
3. Run and preserve an environment-specific reconciliation review.
4. Build a read-only pilot preflight command.
5. Exercise the complete O&G/Maternity journey through automated browser smoke.
6. Produce a clinician-facing manual acceptance package.
7. Run the memory-safe wide project regression.
8. Compare wide failures against the documented pre-existing baseline.
9. Produce an honest readiness verdict.
10. Do not enable production feature flags automatically.

This phase is validation and rollout preparation.

Do not:

- enable Maternity billing posting
- create invoice items
- run reconciliation apply mode
- migrate historical specialty entries
- enable write guards automatically
- modify production/default seeders
- claim clinician acceptance without actual clinician sign-off
- hide broad-suite failures behind an allowlist
- run a destructive cleanup against non-pilot data
- touch `docs/prompt.md`

1. Gate 0 — close Consultation preview-modal parity

Audit:

- `ConsultationSpecialtySummaryController::preview`
- the JSON payload containing:
  - summary
  - maternity HTML fragment
  - readiness fragment if included
- the existing summary-preview JavaScript in `consultations/show.blade.php`
  or its extracted asset
- existing project patterns for injecting server-rendered partial HTML
- preview-modal lifecycle
- empty-state handling
- loading/error handling

Implement the smallest safe change required so the existing preview modal
renders the Maternity block.

Required behavior:

Active Consultation:

- preview modal shows the live Current Maternity Record block
- same presentation service as the summary tab
- no snapshot is created by preview

Completed Consultation with snapshot:

- preview modal shows the selected/latest completion snapshot
- does not display live Maternity values as historical values

Completed Consultation without snapshot:

- shows the honest no-snapshot state
- does not fabricate history

Reopened Consultation:

- shows live projection
- historical snapshot navigation may remain on the main summary page if the
  preview modal is intentionally compact

Flag off:

- preview output remains exactly as before
- no empty Maternity container
- no additional presentation query

Security:

- use server-rendered, Blade-escaped HTML
- follow the existing safe fragment-insertion pattern
- do not evaluate scripts from the fragment
- do not expose raw JSON
- do not duplicate business logic in JavaScript
- do not create a second summary renderer

Add tests:

- active preview contains Maternity HTML
- completed preview contains snapshot HTML
- no-snapshot preview is honest
- flag-off preview is unchanged
- changing Maternity after completion does not change previewed historical HTML
- preview creates no snapshot
- preview creates no specialty entry
- preview creates no billing record

Do not proceed to pilot acceptance until this gate passes.

2. Audit existing manual-data infrastructure

Review:

- `MaternityManualTestDataService`
- `maternity:seed-manual-test-data`
- existing consultation E2E fixture services
- existing Emergency manual seeders
- existing Admission/Ward manual seeders
- existing Playwright/browser fixture commands
- manual-record marker conventions such as `MT-MAT-`
- cleanup conventions
- production environment guards
- patient-number creation rules
- role/user test accounts
- how test URLs and credentials are surfaced

Reuse existing infrastructure.

Do not add O&G pilot data to:

- `DatabaseSeeder`
- default production seeders
- installation seeders
- normal demo seeding unless explicitly invoked

3. Add a dedicated O&G/Maternity pilot-data service

Create a service such as:

`app/Services/Maternity/Testing/ObgynMaternityPilotDataService.php`

Or extend the existing manual-data service only if doing so keeps the existing
command contract clean.

Create a guarded command such as:

```bash
php artisan maternity:seed-obgyn-pilot-data \
  --fresh-manual \
  --force

Recommended optional arguments:

--batch=
--scenario=
--department=
--count=
--output=
--json

Production safety:

refuse to run in production by default
require --force in allowed non-production environments
never operate on unmarked data
never alter real patients
never use existing real patient numbers
never modify default seeders

Use a clear marker:

MT-OBGYN-14R7-

Also produce a manifest:

storage/app/manual-testing/obgyn-maternity/<batch-id>.json

The manifest should contain only:

batch ID
created record IDs
route names
scenario codes
test-user identifiers
generated URLs
timestamps

Do not place clinical narrative or secrets into the manifest.

Add safe pilot cleanup

Provide either:

php artisan maternity:clear-obgyn-pilot-data \
  --batch=<batch-id> \
  --force

or an equivalent --fresh-manual cleanup path.

Cleanup requirements:

require a known pilot batch manifest
delete only records listed in that manifest and/or carrying the exact pilot
marker
validate relationships before deletion
delete in safe dependency order
never delete a record merely because its name resembles a pilot record
refuse to clean records linked to non-pilot data
report skipped conflicts
remain blocked in production
preserve ordinary migration/audit history where required by foreign-key or
immutable-snapshot rules

Snapshot cleanup:

pilot snapshots may be removed only as part of deleting the entire isolated
pilot Consultation chain in a non-production environment
do not add a clinical snapshot-delete route
cleanup must use a dedicated test-data cleanup path, not normal model delete
methods exposed to application users
document why this is test-fixture teardown rather than clinical mutation
Seed complete pilot scenarios

Create deterministic scenarios with discoverable metadata.

Scenario O1 — Obstetrics, no Maternity context

Obstetrics Consultation
no Pregnancy Profile
workspace should load normally
no automatic profile/link

Scenario O2 — Obstetrics, linked Pregnancy Profile and ANC

explicit Pregnancy Profile link
Pregnancy Profile with dating method
one ANC Visit
advisory readiness ready
live summary projection
no duplicate specialty ANC entries

Scenario O3 — ambiguous profiles

same patient
two active/high-risk Pregnancy Profiles
no explicit link
resolver must return ambiguous
explicit selection required

Scenario G1 — Gynaecology without pregnancy

normal Gynaecology Consultation
no context card when flag off
normal sections and completion

Scenario G2 — positive pregnancy test, unlinked

persisted positive free-text pregnancy-test value
no Pregnancy Profile created automatically
explicit Start/Link affordance only

Scenario G3 — linked Gynaecology with LMP adoption eligible

persisted menstrual-history LMP
explicit linked Pregnancy Profile with null LMP
adoption can be tested
consultation remains Gynaecology

Scenario G4 — LMP conflict/scan dating

linked profile with a different LMP or ultrasound/ART dating
adoption must be unavailable

Scenario E1 — Emergency obstetric context

Emergency case
Pregnancy Profile
optional suggested context before confirmation
explicit context after confirmation
no automatic Labor Episode

Scenario E2 — Emergency with active Labor

explicit Pregnancy Profile
active Labor Episode
Open Existing Labor state
Admission Request creation/reuse path

Scenario A1 — Maternity-aware Admission Request

Consultation or Emergency origin
separate operational source and Maternity clinical context
open request
bed not reserved automatically

Scenario A2 — converted Admission

request converted to Admission
Maternity context propagated
ward/bed/nursing remain Admission-owned
postnatal readiness advisory where applicable

Scenario M1 — Maternity → Emergency escalation

Labor or Postnatal escalation flag set
no Emergency case created by the flag
explicit handoff may create/reuse one Emergency case

Scenario P1 — Postnatal review Consultation

existing PostnatalCase
linked review Consultation
no duplicated postnatal observation

Scenario S1 — active Consultation live summary

explicit full chain:
Pregnancy Profile
ANC
Labor
Delivery
at least three Newborn records
Postnatal
live Maternity summary visible
no snapshot yet

Scenario S2 — completed snapshot v1 with changed current data

completed Consultation
snapshot v1
underlying Pregnancy/ANC data subsequently changed
historical snapshot and current data intentionally differ

Scenario S3 — reopened/recompleted snapshot v2

v1 preserved
Consultation reopened
Maternity changed
Consultation recompleted
v2 latest
v1 unchanged

Scenario S4 — completed Consultation without snapshot

completed while snapshot capture disabled
no historical snapshot
current record separately available
no retroactive fabrication

Scenario S5 — five-newborn print stress

Delivery with five Newborn records
completed Consultation snapshot
used to validate dompdf pagination and compact rendering

Scenario R1–R5 — reconciliation classifications

Create isolated pilot specialty entries that produce:

safe_to_link
safe_to_migrate
conflict_requires_review
historical_only
insufficient_context

Rules:

these are pilot-only records
preserve original specialty-entry JSON
do not run apply mode
do not allow them to contaminate the real environment reconciliation count

Scenario B1 — billing overlap readiness

Consultation event-specific mapping
ANC or another Maternity event mapping representing the same act
de-duplication policy should show maternity_event_only
base attendance fee remains separate
no billing posting
Keep environment review and synthetic validation separate

This distinction is mandatory.

A. Real environment reconciliation review

Run before seeding pilot historical entries:

php artisan maternity:reconcile-obgyn-entries \
  --format=json \
  --output=<environment-report-path>

Record:

safe_to_link count
safe_to_migrate count
conflict_requires_review count
historical_only count
insufficient_context count
total inspected
command version/schema version
environment identifier that does not expose secrets
timestamp
entry hashes

Do not include patient names.

Do not run --apply.

B. Synthetic classifier validation

Run against the isolated pilot scenarios R1–R5.

Clearly label the output:

SYNTHETIC PILOT DATA — NOT ENVIRONMENT RECONCILIATION COUNTS

Prove all five classifications can be produced.

Never combine synthetic and real counts in the closure report.

Add O&G/Maternity pilot preflight

Create a read-only service and command such as:

php artisan maternity:obgyn-pilot-preflight

Recommended options:

--format=table|json
--output=
--strict
--include-query-baselines
--environment-reconciliation=<path>
--batch=<pilot-batch-id>

The command must perform zero clinical writes.

Checks should include:

Database/schema:

all required bridge/link/snapshot tables exist
required migrations are applied
indexes/active-slot uniqueness exist
snapshot schema version is supported

Feature flags:

report current values for all O&G/Maternity flags
warn if a write guard is enabled without its context/workspace flag
warn if Maternity billing is enabled
warn if auto-post is enabled
never change flag values

Profiles/mappings:

Obstetrics specialty profile exists and is active
Gynaecology specialty profile exists and is active
active Obstetrics department mapping exists, or record K1 fallback
order-set audit contains no unsafe unguarded Maternity patch
custom-needs-review order sets are listed

Permissions:

bridge and underlying-domain permission combinations are valid
pilot users have the required dual permissions
no role receives a bridge action without target-domain access
summary/snapshot permissions are present

Reconciliation:

environment reconciliation report is present
report is dry-run
report matches the current command schema
counts are displayed
conflicts and insufficient-context rows are highlighted
pilot readiness must not silently approve unresolved real-environment rows

Handoffs:

no dangling modal triggers
selectors are patient-scoped
return-context named-route rules are active
action flags are dark by default or intentionally configured for pilot

Snapshots:

capture flag state
existing snapshot count
hash verification sample
no snapshot update/delete routes
current-record view permission requirements

Billing:

MATERNITY_BILLING_ENABLED=false
MATERNITY_BILLING_AUTO_POST=false
postForSource() remains non-posting
de-duplication policy configuration is reported
no doctor-workspace billing card exists

Verdict states:

PASS
WARNING
BLOCKED

The command should distinguish:

ready for technical pilot
ready for guarded rollout
not ready

It must never claim clinician acceptance.

Add technical rollout gates

Define explicit gates.

Gate A — Technical pilot readiness

Requires:

all targeted tests pass
preview-modal parity fixed
preflight has no BLOCKED findings
environment reconciliation dry run completed
no Maternity billing posting
no dangling modal targets
pilot data command succeeds
pilot cleanup succeeds in test environment

Gate B — Projection pilot readiness

Requires Gate A plus:

Obstetrics and Gynaecology context cards manually reviewed
active summary reviewed
Emergency/Admission read-only context reviewed
no layout blocker

Gate C — Guarded source-of-truth readiness

Requires Gate B plus:

environment reconciliation reviewed by an authorised person
conflict and insufficient-context rows resolved, accepted or excluded
clinician accepts read-only projection behavior
role permissions verified
ANC/Labor actions manually exercised
Gynaecology LMP policy accepted

Gate D — Snapshot pilot readiness

Requires Gate B plus:

summary preview works
snapshot completion works
v1/v2 history verified
no-snapshot behavior verified
print verified
hash-mismatch behavior understood

Gate E — Production rollout readiness

Must not be automatically granted by this phase.

Requires:

actual clinician sign-off
actual environment review
wide regression review
production deployment/change-control approval
backup/rollback plan
monitoring plan
Fix and test the preview-modal JavaScript

Use the existing summary preview modal.

Required implementation:

add a dedicated Maternity container
insert the pre-rendered, Blade-escaped fragment from the JSON payload
clear the container before every preview request
hide it when the fragment is empty
preserve existing plain_text summary rendering
display readiness fragment only if already part of the response contract
handle fetch failure without leaving stale Maternity content visible
do not execute scripts included in the fragment
do not duplicate snapshot/live mode decisions in JavaScript
do not create any record

If the project has a standard partial-insertion helper, use it.

Otherwise, keep the change minimal and documented.

Add browser/E2E smoke using the existing harness

Audit the existing Playwright/browser-test infrastructure.

If an established consultation E2E pattern exists, add a focused O&G/Maternity
smoke test.

Do not create a new browser-testing framework.

Recommended coverage:

Obstetrics:

flags off: no ribbon/panel
pilot mode: ribbon/panel visible, legacy fields editable
guarded mode: Maternity-owned fields read-only
Record ANC creates one ANC Visit
Start Labor reuses active episode

Gynaecology:

no automatic context
positive test produces explicit affordance only
linked card says consultation remains Gynaecology
LMP adoption eligible/conflict states

Handoffs:

Consultation Admission Request modal opens and submits
Emergency profile selector is lazy
Emergency Start Labor reuses existing episode
Admission context displays propagation
Maternity → Emergency explicit handoff

Summary/snapshot:

active preview modal displays Current Maternity Record
completed preview displays snapshot
history version switching works
current-record block loads only after click
no-snapshot state is honest
v1/v2 are visually distinct

No billing:

no Post Charge action
no invoice item created

Use the pilot-data manifest to discover records.

Do not hard-code database IDs.

Save screenshots/traces only under a manual-testing artifact directory.

Do not commit patient-identifiable screenshots.

Add technical manual-test guide

Create or update:

docs/manual-testing/OBGYN_MATERNITY_CLINICIAN_PILOT_GUIDE.md

The guide should be written for clinicians, not developers.

It should explain:

how to identify each pilot patient/scenario
expected module ownership
where to click
expected result
what must not happen
how to record feedback
rollback contact/process

Include scenarios:

Obstetrics without context
Obstetrics linked Pregnancy Profile
ANC from Consultation
ambiguous profiles
Gynaecology without pregnancy
positive pregnancy test
explicit Pregnancy Profile transition
LMP adoption
Emergency obstetric handoff
Admission propagation
Postnatal review
live summary
completion snapshot
current-vs-historical separation
reopen/recomplete
no-snapshot case
five-newborn print
billing de-duplication preview

Add explicit sign-off fields:

reviewer name/role
environment
date
scenario result
usability issue
clinical-safety issue
accepted / rejected / needs changes
signature or approved electronic acknowledgement

Do not mark these fields as passed automatically.

Add a technical acceptance results template

Create:

docs/manual-testing/OBGYN_MATERNITY_PILOT_RESULTS_TEMPLATE.md

Statuses:

NOT_RUN
PASS
PASS_WITH_OBSERVATION
FAIL
BLOCKED

Separate:

automated evidence
developer manual evidence
clinician evidence
environment reconciliation evidence
wide regression evidence

The implementation agent must not fill clinician evidence as PASS without
actual clinician feedback supplied by the project owner.

Validate reconciliation behavior on real and synthetic data

Real environment:

preserve the pre-seed reconciliation output
review aggregate counts
list only identifiers/hashes in detailed artifacts
do not expose patient names in committed docs
do not enable write guards if unreviewed conflicts or insufficient-context
rows exist

Synthetic pilot data:

confirm all five classifications
confirm parsers behave as designed
confirm command makes zero writes
confirm --apply exits non-zero
confirm deterministic repeated output
confirm pilot cleanup does not modify reconciliation source entries unless
those exact entries are pilot records in the cleanup manifest
Re-run order-set safety audit

Run:

php artisan consultation:obgyn-order-set-audit

Required result:

no Maternity-owned patch_specialty_entry remains among known system-seeded
definitions
the two reconciled actions remain maternity_context_action
admin-modified custom items remain listed as needs review
historical applications remain untouched
Gynaecology menstrual_history.bleeding_pattern remains allowed
service-boundary guard remains active when configured

Do not modify custom items automatically.

Validate feature-flag rollout profiles

Document exact pilot profiles without committing enabled defaults.

Dark/default:

CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false
CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED=false
CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED=false

MATERNITY_CONSULTATION_HANDOFFS_ENABLED=false
MATERNITY_EMERGENCY_CONTEXT_ENABLED=false
MATERNITY_ADMISSION_CONTEXT_ENABLED=false
MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false

CONSULTATION_MATERNITY_READINESS_ENABLED=false
CONSULTATION_MATERNITY_SUMMARY_ENABLED=false
CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED=false

MATERNITY_BILLING_DEDUPLICATION_POLICY_ENABLED=false
MATERNITY_BILLING_ALLOW_BOTH_WHEN_CONFIGURED=false
MATERNITY_BILLING_ALLOW_MANUAL_SELECTION=false

MATERNITY_BILLING_ENABLED=false
MATERNITY_BILLING_AUTO_POST=false

Projection pilot:

Obstetrics workspace on
Obstetrics guard off
Gynaecology context on
Gynaecology guard off
Admission context on
Emergency context on
advisory readiness on
summary on
snapshot capture off initially
all handoff mutations off initially
billing posting off

Controlled handoff pilot:

consultation handoffs on
Emergency/Maternity handoffs on last
target permissions explicitly assigned
billing posting remains off

Snapshot pilot:

summary on
snapshot capture on
controlled pilot Consultations only
current-record permissions verified

Guarded pilot:

only after environment reconciliation review and clinician approval
enable Obstetrics/Gynaecology write guards separately
never enable both automatically

Do not write these values into committed environment files as enabled defaults.

Add query/performance contract verification

Re-run and compare the established contracts.

Required flag-off expectations:

Obstetrics workspace: 0 Maternity resolver queries
Gynaecology workspace: 0 Maternity resolver queries
Emergency context: 0
Admission context: 0
Consultation handoffs: 0
readiness: 0
summary: 0
billing policy: 0

Required enabled expectations:

explicit Obstetrics context remains bounded
explicit Gynaecology context remains bounded
Emergency suggested context remains near the optimised five-query path
completed snapshot defaults to the two-query snapshot path
three versus five Newborns do not introduce N+1
current live record is lazy
selector candidates remain lazy
modal and card partials issue zero queries
preview-modal rendering does not add another projection build
presentation services remain memoised

Create performance-contract tests if the current tests do not already assert
these values robustly.

Do not introduce persistent cross-request caching for clinical records.

Add controlled same-second completion-risk check

Reproduce P2 in an isolated automated test.

Test:

complete a Consultation
reopen it
force or simulate recompletion inside the same second
observe completion-reference behavior

Rules:

do not silently change the snapshot identity architecture in this phase
if the collision cannot occur through the real workflow, document why
if it can occur and collapses two genuine completions:
mark snapshot production readiness WARNING or BLOCKED
document a required follow-up
do not hide it
do not weaken existing idempotency to make the test pass

This risk does not automatically block a limited pilot, but it must be visible
in the readiness verdict.

Wide-regression baseline preparation

The memory-safe command remains:

composer test:wide

Audit the Phase 13.1 known broad-suite defects.

Create a non-suppressing baseline artifact such as:

tests/Baselines/wide-suite-known-defects.json

Only if the project does not already have a better baseline mechanism.

The artifact should contain:

exact test class and method
expected status:
failure
error
first documented phase/date
module
reason/source report
whether still reproducible

Rules:

the baseline must not cause PHPUnit to exit zero
the baseline must not skip tests
the baseline must not convert failures into passes
it is comparison metadata only
resolved failures are reported as resolved
new failures are reported as regressions
changed failure signatures are reported for review

Alternatively, implement a read-only JUnit comparison script/command.

Preferred command:

php artisan tests:compare-wide-baseline \
  --junit=storage/logs/phpunit-14r7-wide.xml

Output:

known unchanged
known resolved
new failure
new error
missing test
changed status

Do not hide the raw PHPUnit result.

Run targeted regression first

Run all new and high-risk targeted suites.

At minimum:

php artisan test tests/Feature/ConsultationMaternitySummaryUiPhase14R6_1Test.php
php artisan test tests/Feature/ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test.php
php artisan test tests/Feature/ConsultationMaternitySummaryPrintPhase14R6_1Test.php

php artisan test tests/Feature/ConsultationMaternityReadinessPhase14R6Test.php
php artisan test tests/Feature/ConsultationMaternitySummarySnapshotPhase14R6Test.php
php artisan test tests/Feature/ObgynMaternityReconciliationDryRunPhase14R6Test.php
php artisan test tests/Feature/MaternityBillingDeduplicationPolicyPhase14R6Test.php

php artisan test tests/Feature/MaternityHandoffUiPhase14R5_1Test.php
php artisan test tests/Feature/MaternityHandoffModalIntegrityPhase14R5_1Test.php
php artisan test tests/Feature/MaternityHandoffSelectorsPhase14R5_1Test.php

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
php artisan test tests/Feature/MaternityReportsBillingReadinessPhase13Test.php
php artisan test tests/Feature/MaternityBillingPostingPhase14_1Test.php

php artisan test tests/Feature/Consultations

Also run new Phase 14R.7 suites.

The targeted reconciliation chain must introduce zero new failures beyond its
documented baseline.

Run the memory-safe wide suite

Run:

composer test:wide -- \
  --log-junit=storage/logs/phpunit-14r7-wide.xml

If Composer does not forward arguments correctly, run the equivalent direct
memory-safe PHPUnit command.

Record:

total tests
assertions
failures
errors
skipped
incomplete
time
peak memory
exact failing test names

Compare against the Phase 13.1 known baseline.

Acceptance:

no new failure/error attributable to O&G/Maternity reconciliation
no failure in Phase 14R.2–14R.7 suites
no failure in Emergency/Admission pathways changed by this batch
known unrelated defects may remain, but must be explicitly listed
the whole project must not be described as green if failures remain

If a new failure appears:

investigate it
fix if caused by this batch
do not add it to the known baseline merely to pass the phase
Route, syntax, localisation and artifact checks

Run:

php artisan route:list --name=consultation
php artisan route:list --name=emergency
php artisan route:list --name=admissions
php artisan route:list --name=maternity

php artisan view:clear
php artisan config:clear

composer validate --no-check-publish

git diff --check -- . ':!docs/prompt.md'

Run:

PHP lint on all changed PHP/lang files
project-safe Blade compile/lint
recursive EN/FR parity for every O&G/Maternity localisation file
modal-target integrity suite
route-name existence checks for safe return contexts
pilot manifest validation
pilot cleanup dry-run if supported

Do not run or modify docs/prompt.md.

Manual clinician acceptance

The implementation agent may prepare and facilitate the pilot.

It must not claim clinical acceptance without actual clinician evidence.

For every manual scenario:

record NOT_RUN initially
provide the pilot URL
provide the pilot patient/scenario code
list expected behavior
list prohibited behavior
capture clinician feedback
record pass/fail only after the clinician or project owner provides it

Required clinician scenarios:

A. Obstetrics no context.

B. Obstetrics linked Pregnancy Profile.

C. Record ANC from Consultation.

D. Ambiguous Pregnancy Profiles.

E. Gynaecology without pregnancy.

F. Positive pregnancy test with no automatic transition.

G. Explicit Gynaecology Pregnancy Profile link.

H. One-way LMP adoption.

I. Emergency Pregnancy/Labor/Admission handoff.

J. Admission context propagation.

K. Maternity → Emergency handoff.

L. Postnatal review Consultation.

M. Active live summary.

N. Completed completion snapshot.

O. Current versus historical record separation.

P. Reopen/recomplete snapshot versioning.

Q. Completed Consultation with no snapshot.

R. Five-Newborn print.

S. Billing de-duplication preview.

T. Feature-flag rollback.

Technical automation may mark the mechanism verified.

It may not substitute for clinical usability approval.

Readiness verdict

Produce one explicit verdict:

BLOCKED
READY_FOR_TECHNICAL_PILOT
READY_FOR_CLINICAL_PILOT
READY_FOR_GUARDED_PILOT
READY_FOR_PRODUCTION_ROLLOUT

Rules:

READY_FOR_TECHNICAL_PILOT

automated targeted tests pass
preview-modal parity fixed
preflight passes
real reconciliation dry run captured
wide regression has no new related failure

READY_FOR_CLINICAL_PILOT

technical pilot ready
pilot data and cleanup verified
browser smoke passes
clinician guide prepared

It does not require clinician sign-off yet.

READY_FOR_GUARDED_PILOT

actual clinician scenarios accepted
environment reconciliation reviewed
write-guard impact accepted
permission mapping accepted

READY_FOR_PRODUCTION_ROLLOUT

must not be issued automatically
requires explicit project-owner approval
requires production environment review and change control

If clinician testing has not occurred, the maximum honest verdict is:

READY_FOR_CLINICAL_PILOT
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_PILOT_REGRESSION_PHASE_14R_7_REPORT.md

The report must include:

Gate 0 preview-modal fix.
Existing manual-data infrastructure audited.
Pilot-data command and cleanup behavior.
Pilot batch ID and manifest location.
Scenario inventory.
Real environment reconciliation counts.
Synthetic classification counts, clearly separated.
Pilot preflight results.
Order-set audit result.
Feature-flag profiles.
Browser/E2E results.
Query/performance comparison.
Same-second completion-risk result.
Targeted regression result.
Wide-suite result.
Known unchanged failures.
New failures, if any.
Clinician pilot status.
Technical acceptance status.
Final readiness verdict.
Existing workflows protected.
Known risks.
Rollout.
Rollback.
Recommended next phase.

Update:

docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md
docs/maternity/OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md
docs/manual-testing/OBGYN_MATERNITY_CLINICIAN_PILOT_GUIDE.md

Mark Phase 14R.7 complete only when:

preview modal shows the Maternity block
real environment reconciliation report is captured
synthetic classifications are validated separately
pilot command and cleanup are safe
preflight completes
targeted regression completes
wide regression completes
no new related failures remain
clinician sign-off status is represented honestly
Rollout

Do not enable production flags automatically.

Recommended controlled pilot order:

Run real environment reconciliation dry run.
Run order-set audit.
Run pilot preflight.
Seed isolated pilot data.
Enable read-only/projection flags in the pilot environment.
Run automated browser smoke.
Run developer technical checks.
Conduct clinician pilot.
Enable snapshot capture for controlled pilot consultations.
Enable handoff actions.
Enable write guards only after reconciliation and clinician approval.
Keep Maternity billing posting disabled.
Rollback

Feature rollback:

Disable Maternity write guards.
Disable Gynaecology write guard.
Disable handoff actions.
Disable Emergency/Admission context.
Disable summary/readiness.
Disable snapshot capture.
Disable workspace/context cards.
Clear config cache.

Data rollback:

do not delete real bridge links or snapshots
preserve audit history
clear only isolated pilot data through the batch manifest
do not drop tables
do not reverse historical order-set applications
do not reinterpret legacy Admission Request sources

Billing:

remains disabled throughout
no billing rollback should be required
Boundaries

Do not implement reconciliation apply mode.
Do not migrate historical entries.
Do not enable Maternity billing posting.
Do not create Invoice Items.
Do not add clinical billing cards.
Do not modify immutable snapshots.
Do not fabricate old snapshots.
Do not automatically approve clinician scenarios.
Do not automatically enable feature flags.
Do not put pilot data in default seeders.
Do not delete unmarked data during cleanup.
Do not hide broad-suite failures.
Do not add new failures to the known baseline without investigation.
Do not run a new referral/emergency/admission engine.
Do not rename routes.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Preview-modal closure.
Pilot-data command and cleanup.
Environment reconciliation counts.
Synthetic classification results.
Preflight verdict.
Browser/E2E results.
Query-count comparison.
Targeted regression result.
Wide-suite result.
Known unchanged failures.
New failures and disposition.
Clinician pilot status.
Final readiness verdict.
Existing workflows protected.
Known risks.
Rollout and rollback.
Next-phase recommendation.

Recommended next phase after an honest READY_FOR_GUARDED_PILOT verdict:

Phase 14.2 — Controlled Manual Maternity Billing Posting Only.

Do not resume Phase 14.2 before:

environment reconciliation is reviewed
O&G/Maternity clinician pilot is accepted
billing de-duplication conflicts are reviewed
Maternity billing remains disabled until the manual-posting phase is
explicitly approved
