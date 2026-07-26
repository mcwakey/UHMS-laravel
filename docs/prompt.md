You are working inside the UHMS Laravel project.

Phase 14R.6 is complete and decision R6 is closed.

Phase 14R.6 implemented:

- Advisory Consultation Maternity readiness.
- Curated live Maternity summary projection.
- `maternity_context` summary source.
- Immutable, versioned completion-time Maternity snapshots.
- Transactional snapshot capture inside Consultation completion.
- Completion identity based on Consultation route ID + completed_at.
- Reopen/recomplete versioning.
- Snapshot SHA-256 tamper-evidence verification.
- Read-only O&G specialty-entry reconciliation dry run.
- Read-only maternity billing de-duplication policy.
- Six dark-by-default feature flags.
- 65 new passing tests.
- 280 passing Phase 14R.2–14R.6 tests, with one documented skip.
- Zero new baseline failures.

Current Phase 14R.6 flags:

CONSULTATION_MATERNITY_READINESS_ENABLED=false
CONSULTATION_MATERNITY_SUMMARY_ENABLED=false
CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED=false
MATERNITY_BILLING_DEDUPLICATION_POLICY_ENABLED=false
MATERNITY_BILLING_ALLOW_BOTH_WHEN_CONFIGURED=false
MATERNITY_BILLING_ALLOW_MANUAL_SELECTION=false

Current snapshot behavior:

- Snapshot capture occurs inside `ConsultationRouteService::completeRoute()`.
- An explicit valid Maternity context is required.
- Suggested, ambiguous and invalid contexts capture no clinical values.
- Repeated completion is idempotent.
- Reopen and recomplete creates the next version.
- Previous versions remain immutable.
- A snapshot is never fabricated retroactively.
- `ConsultationMaternitySnapshotService` exposes:
  - `latestFor()`
  - `historyFor()`
  - payload-hash verification
- Completed summaries are intended to use the completion-time snapshot.
- Active summaries are intended to use the live Maternity projection.

Known Phase 14R.6 gap K1:

The summary/snapshot Blade UI is not authored.

The data and services exist programmatically, but clinicians cannot yet:

- See the live Maternity Context section in the real summary.
- See the completion-time snapshot in a completed Consultation.
- Navigate snapshot versions.
- See snapshot integrity state.
- View the current Maternity record separately from historical completion data.
- Exercise the snapshot behavior during the Phase 14R.7 manual pilot.

Now implement Phase 14R.6.1:

Maternity Summary and Completion Snapshot UI.

Goal:

Make the Phase 14R.6 summary and snapshot architecture visible and usable in
the real Consultation summary, preview and print surfaces without changing
snapshot capture, Consultation completion, readiness behavior, Maternity
records, billing, or historical data.

This must remain:

- read-only
- permission-controlled
- dark by default
- query-bounded
- explicit about live versus historical data
- incapable of modifying or deleting snapshots

This is a narrow clinician-facing UI closure phase.

Do not implement manual-test seed data yet.

Do not run environment reconciliation apply mode.

Do not enable Maternity billing posting.

Do not run `composer test:wide`.

Do not touch `docs/prompt.md`.

1. Audit every real Consultation summary surface first

Before changing templates, inspect:

- Consultation show page.
- Consultation summary preview.
- Consultation Specialty summary partials.
- `ConsultationSpecialtySummaryBuilder`.
- `ConsultationSpecialtySummaryTemplateRegistry`.
- `ConsultationSpecialtySummarySourceCollector`.
- Consultation print view.
- Consultation PDF view, if one actually exists.
- Medical-record print/export views that reuse the Consultation summary.
- Completed Consultation review view.
- Reopened Consultation behavior.
- Existing summary tabs/cards.
- Existing permissions.
- Existing print CSS.
- Existing async/lazy partial patterns.
- Existing route conventions for read-only detail views.

Confirm:

- Where the `maternity_context` source currently enters the summary builder.
- Why it is not rendered in the existing Blade templates.
- Whether the summary template registry requires an explicit section
  registration.
- Whether preview and print use the same summary payload.
- Whether a completed Consultation currently calls live summary generation.
- Whether a selected snapshot version can be represented without altering the
  existing summary contract.

Do not create a second Consultation-summary engine.

2. Add a typed summary presentation service

Create a read-only presentation service such as:

`app/Services/Consultation/Maternity/ConsultationMaternitySummaryPresentationService.php`

Create a typed view model such as:

`ConsultationMaternitySummaryViewModel`

The view model should contain:

- feature-enabled state
- Consultation route ID/status
- whether the Consultation is active, completed or reopened
- presentation mode:
  - live
  - completion_snapshot
  - no_snapshot
  - unavailable
- live Maternity projection, when permitted and requested
- selected snapshot
- latest snapshot
- snapshot-history metadata
- snapshot version
- schema version
- captured at
- captured by
- Pregnancy Profile ID
- payload-hash verification state
- previous/next snapshot identifiers
- whether “View Current Maternity Record” is available
- whether the current user may view current Maternity data
- warnings
- source labels
- print mode
- selected history version
- internal navigation routes

The presentation service must consume:

- `ConsultationMaternitySummaryService`
- `ConsultationMaternitySnapshotService`
- existing Consultation summary services
- existing permissions/policies

It must not:

- duplicate Maternity calculations
- write a snapshot
- modify a snapshot
- create a link
- create a Maternity record
- create a specialty entry
- create billing
- query inside Blade

Memoise the presentation result per request.

3. Define flag behavior precisely

A. Summary flag false

CONSULTATION_MATERNITY_SUMMARY_ENABLED=false

Expected:

- No Maternity Context section in summary/preview/print.
- No summary-presentation service call.
- No live Maternity projection query.
- No snapshot-history query.
- Existing Consultation summary output remains unchanged.
- Existing snapshots remain stored and immutable.

B. Summary flag true, snapshot capture flag false

CONSULTATION_MATERNITY_SUMMARY_ENABLED=true
CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED=false

Expected:

- Active Consultation may show live Maternity projection.
- Completed Consultation may show an existing snapshot if one was captured
  earlier.
- No new snapshots are captured.
- Disabling snapshot capture must not hide or delete existing snapshots.
- A completed Consultation with no snapshot must not fabricate one.

C. Summary and snapshot flags true

Expected:

- Active Consultation shows live projection.
- Completed Consultation shows completion snapshot by default.
- Reopened active Consultation shows live projection plus historical snapshot
  access.
- Recompleted Consultation defaults to the latest snapshot version.

The snapshot flag controls future capture.

It must not be treated as permission to rewrite or delete prior snapshots.

4. Render the live Maternity Context summary

Add a reusable partial such as:

`resources/views/consultations/partials/maternity/summary-live.blade.php`

Use the existing curated projection only.

The live summary should clearly show:

Header:

- Current Maternity Record
- Source of truth: Maternity
- Encounter source: Consultation
- Context status
- Pregnancy Profile reference

Pregnancy:

- status
- gravida
- para
- abortions
- living children
- previous caesarean
- LMP
- EDD
- gestational age
- dating method
- risk level

ANC:

- latest visit date
- gestational age
- BP
- weight
- fundal height
- fetal heart rate
- presentation
- danger-sign codes/labels
- risk-flag codes/labels
- next visit date

Labor:

- stage
- status
- latest observation time
- cervical dilation
- fetal heart rate
- maternal BP
- escalation flags

Delivery:

- delivery date/time
- mode
- outcome
- maternal condition
- estimated blood loss
- newborn count

Newborn:

- birth order
- sex
- birth weight
- APGAR 1/5/10
- resuscitation required
- outcome
- status

Postnatal:

- status
- mother readiness
- newborn readiness
- ready-for-discharge state
- referral state
- follow-up date
- latest observation times

Admission:

- Admission ID/reference
- status
- ward
- bed
- label:
  - “Operational owner: Admission”

Do not display excluded clinical narrative.

Do not display:

- full ANC assessment/plan
- labor notes
- delivery notes
- postnatal observation values
- STI/sexual history
- protected fields
- billing
- pharmacy
- stock

Render enum/status labels through localisation.

Do not store translated labels.

5. Render the completion snapshot as the default completed view

Add a partial such as:

`resources/views/consultations/partials/maternity/summary-snapshot.blade.php`

For a completed Consultation with a snapshot, show:

- Maternity Context at Consultation Completion
- Completion Snapshot vN
- captured at
- captured by
- schema version
- Pregnancy Profile reference
- source-record references where authorised
- hash verification state
- curated snapshot payload rendered through the same clinical presentation
  structure as the live projection
- clear historical label

The completed summary must not silently call live Maternity services for the
default displayed values.

Default completed behavior:

- load the latest snapshot
- render that snapshot
- do not load current Maternity records
- do not compare against current data automatically

The historical snapshot must remain visually distinct from:

- Current Maternity Record
- current Admission state
- current Postnatal state

Do not label a live projection as completion-time data.

6. Add snapshot history navigation

Add a read-only history component such as:

`resources/views/consultations/partials/maternity/snapshot-history.blade.php`

Show bounded metadata:

- version
- captured at
- captured by
- schema version
- integrity state
- current/latest marker

Allow the user to open a selected historical version.

Use either:

- existing summary query parameters, or
- a dedicated permission-protected GET route

Follow existing project conventions.

If a new route is required, it must:

- be GET/read-only
- scope the snapshot to the Consultation route
- reject a snapshot from another Consultation
- require Consultation access
- require `consultation.maternity_context.summary.view`
- never accept arbitrary model/class names
- never expose raw JSON
- preserve the originating Consultation-summary route

Version behavior:

- Latest completion version is selected by default.
- Version 1 remains accessible after version 2 exists.
- Previous/next navigation is bounded.
- Selecting a version performs no writes.
- Viewing history does not verify against live Maternity data.
- A hash mismatch does not modify or regenerate the snapshot.

7. Add snapshot integrity display

Use `verifyPayloadHash()`.

Closed UI states:

- verified
- mismatch
- unavailable

Verified:

- show a discreet success label

Mismatch:

- show a prominent warning
- state that the stored payload no longer matches its captured hash
- do not hide the historical payload
- do not repair it automatically
- do not recalculate and save a new hash
- do not create a new snapshot
- do not claim non-repudiation

Unavailable:

- use only when verification cannot run because required data is missing or
  malformed
- show an honest warning

Normal clinical users may see:

- verification status
- abbreviated hash/reference where useful

Do not expose raw canonical JSON.

A full hash may be shown only if existing clinical-audit conventions allow it.

8. Add “View Current Maternity Record” as an explicit separate action

A completed Consultation must continue showing the historical snapshot by
default.

Add:

- View Current Maternity Record

Requirements:

- explicit user action
- separate card/section
- clearly labelled:
  - “Current Maternity Record”
  - “Not part of the completion-time snapshot”
- requires:
  - Consultation summary access
  - `consultation.maternity_context.summary.view`
  - `consultation.maternity_context.view`
  - underlying Maternity record access
- use current explicit Consultation Maternity links
- do not reinterpret suggested/ambiguous contexts as confirmed truth
- do not change the historical snapshot
- do not compare or overwrite values automatically
- do not create a link
- do not create a snapshot

Prefer lazy/on-demand loading so the normal completed summary remains at its
existing bounded snapshot query count.

Use the project’s existing internal partial-loading pattern if one exists.

Do not introduce another frontend framework.

9. Handle completed Consultations with no snapshot honestly

When:

- Consultation is completed
- summary feature is enabled
- no completion snapshot exists

Show:

- No Maternity completion snapshot was captured for this Consultation.

Explain:

- the Consultation may have been completed before snapshot capture was enabled
- no historical snapshot has been fabricated
- current Maternity data is not the same as completion-time data

Where permissions allow, provide:

- View Current Maternity Record

Do not:

- create a snapshot on page load
- use current data under a historical label
- capture a late snapshot
- update `completed_at`
- alter completion history

10. Handle reopened Consultations

For a reopened active Consultation:

Default:

- show live Current Maternity Record
- show that previous completion snapshots exist
- provide snapshot-history access

Do not:

- display the previous snapshot as the current active record
- overwrite previous versions
- create a new snapshot until recompletion
- treat reopening as snapshot deletion

After recompletion:

- latest snapshot becomes the completed default
- prior versions remain available
- version labels remain stable

11. Handle Gynaecology correctly

Gynaecology remains explicit-only.

No explicit Pregnancy Profile/context:

- no Maternity summary section
- no Maternity readiness card
- no snapshot clinical values

Explicit linked Pregnancy Profile:

- render only the approved limited Gynaecology projection
- do not show ANC/Labor mutation controls
- do not change Gynaecology completion behavior
- clearly state:
  - This consultation remains Gynaecology

A Gynaecology completion snapshot may contain only the curated values permitted
by the Phase 14R.6 projection.

Do not pull in sexual/STI history or menstrual narrative.

12. Wire the advisory readiness UI if it is not already visible

Audit whether `ConsultationMaternityReadinessResult` is actually rendered in:

- Obstetrics Maternity Context panel
- Consultation completion/readiness area

If already wired and correct:

- do not duplicate it

If not wired:

Add a read-only advisory card showing:

- review mode
- ready/warning/unavailable
- warnings
- recommended Maternity action/navigation
- statement:
  - Completion is still allowed

Rules:

- no hard block
- no new readiness mutation
- no Gynaecology warning without explicit context
- no automatic record creation
- no copying Admission discharge-readiness rules

The readiness flag controls rendering.

13. Integrate the real summary preview

The Consultation summary preview must follow:

Active Consultation:

- live projection

Completed Consultation with snapshot:

- selected/latest completion snapshot

Completed Consultation without snapshot:

- no fabricated historical projection

Reopened Consultation:

- live projection
- history available separately

The preview must use the same presentation service as the main summary.

Do not create separate preview-specific Maternity business logic.

14. Integrate print output

Audit every real print surface.

Required default:

Active Consultation print:

- live Maternity projection when summary flag is enabled

Completed Consultation print:

- completion snapshot
- version
- captured-at metadata
- historical label
- integrity status

Completed without snapshot:

- clear no-snapshot message
- do not print current Maternity values as historical values

Selected historical snapshot:

- allow print of that selected version if existing route conventions permit
- clearly show its version

Current Maternity record:

- do not include it automatically in the completed Consultation print
- a separately labelled print may be allowed only if the current-record view
  has an existing safe print pattern

Keep print styling compact and readable.

Do not add PDF generation dependencies.

15. Preserve snapshot immutability

This UI phase must add no snapshot mutation path.

Prohibited:

- update snapshot
- edit snapshot
- delete snapshot
- force delete snapshot
- recalculate stored hash
- regenerate version 1
- “fix” mismatch
- overwrite payload
- change captured actor/time
- fabricate a snapshot for an old completion

No form may POST/PATCH/DELETE to a snapshot.

Any snapshot route added must be GET only.

16. Preserve privacy and permissions

Snapshot visibility requires:

- access to the Consultation
- existing summary-view permission
- `consultation.maternity_context.summary.view`

Current live Maternity visibility additionally requires:

- `consultation.maternity_context.view`
- underlying Maternity view permission

Do not allow snapshot UI to expose:

- a Consultation the user cannot view
- another Consultation’s snapshot
- raw protected Maternity fields
- raw JSON
- hidden sexual/STI content
- billing data

The snapshot is a curated Consultation historical artefact, not unrestricted
Maternity access.

17. Keep billing completely absent

Confirm:

- no billing card in Consultation
- no Maternity Billing Event is created
- no invoice item is created
- no invoice is recalculated
- no posting button exists
- `MaternityBillingPostingService::postForSource()` remains non-posting
- billing-policy evaluation does not run merely because a summary is viewed
- billing-de-duplication UI remains administrative

18. Query and performance requirements

Measure:

A. Summary flag off:

- active Consultation
- completed Consultation
- reopened Consultation

Expected:

- zero Maternity summary/snapshot-presentation queries added

B. Active explicit context:

- Pregnancy Profile only
- full chain with ANC, Labor, Delivery, three Newborns and Postnatal
- same chain with five Newborns

Expected:

- no newborn N+1
- reuse the Phase 14R.6 live projection
- one presentation build per request

C. Completed Consultation:

- latest snapshot
- selected historical version
- snapshot history
- completed without snapshot

Expected:

- latest snapshot default remains approximately the measured two-query path
- no live Maternity fan-out
- history remains bounded
- no component queries

D. Current live record from completed Consultation:

- not loaded until explicitly requested
- normal completed page does not pay its live-query cost
- underlying permissions are checked

E. Gynaecology:

- no explicit context adds zero Maternity summary queries
- explicit limited projection remains bounded

F. Flags disabled:

- all Phase 14R.6 UI partials render nothing
- no hidden snapshot-history query

Document exact before/after counts.

Do not add persistent cross-request caching.

19. Localisation

Extend EN/FR in strict recursive parity.

Add keys for:

Summary modes:

- Current Maternity Record
- Maternity Context at Consultation Completion
- Historical Maternity Context
- Not part of the completion-time snapshot
- Source of truth: Maternity
- Encounter source: Consultation
- Operational owner: Admission

Snapshot:

- Completion Snapshot
- Snapshot version
- Latest snapshot
- Previous snapshot
- Next snapshot
- Historical versions
- Captured at
- Captured by
- Schema version
- Integrity verification
- Verified
- Hash mismatch
- Verification unavailable
- Snapshot cannot be edited
- Snapshot cannot be deleted
- Tamper-evidence warning
- No completion snapshot was captured
- No historical snapshot was fabricated
- Completed before snapshot capture was enabled

Current comparison:

- View Current Maternity Record
- Hide Current Maternity Record
- Current values may differ from the historical snapshot
- Current record loaded separately
- Current record unavailable
- Underlying Maternity permission required

Reopened Consultation:

- Consultation reopened
- Previous completion snapshots
- Live values shown while consultation is active
- Recompletion will create a new snapshot version

Readiness:

- Completion is still allowed
- Resolve this warning in Maternity
- Context confirmation required

Print:

- Historical Consultation Summary
- Printed snapshot version
- Current-record print

Maintain strict EN/FR parity.

20. Tests

Create:

`tests/Feature/ConsultationMaternitySummaryUiPhase14R6_1Test.php`

Create:

`tests/Feature/ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test.php`

Create if print behavior needs separate coverage:

`tests/Feature/ConsultationMaternitySummaryPrintPhase14R6_1Test.php`

Required real-summary tests:

- Summary flag off preserves the real Consultation summary output.
- Flag off invokes no Maternity presentation service.
- Active explicit Consultation renders Current Maternity Record.
- Active summary renders the curated projection.
- Active summary creates no snapshot.
- Active summary creates no specialty entry.
- Suggested context renders no clinical values.
- Ambiguous context renders no selected profile.
- Invalid context renders warning only.
- Non-O&G summary remains unchanged.
- Unlinked Gynaecology renders no Maternity section.
- Explicit linked Gynaecology renders limited projection.
- Full excluded narrative does not leak.

Required completed-summary tests:

- Completed Consultation defaults to latest snapshot.
- Completed summary does not load live Maternity data.
- Pregnancy/ANC changes after completion do not change rendered historical
  summary.
- Snapshot version and captured-at data render.
- Snapshot integrity verified state renders.
- A deliberately tampered snapshot renders mismatch warning.
- Mismatch does not modify snapshot/hash.
- No raw JSON is rendered.
- No snapshot-edit/delete form exists.
- Completed Consultation without snapshot renders no-snapshot message.
- Page load does not fabricate a snapshot.
- Enabling capture later does not fabricate history.
- Existing snapshot remains visible when capture flag is later disabled.

Required history tests:

- Version 1 and version 2 appear in history.
- Latest version is selected by default.
- Selecting version 1 renders version 1 values.
- Selecting version 1 does not modify version 2.
- Snapshot from another Consultation cannot be accessed.
- Previous/next navigation is scoped and bounded.
- Unauthorized user cannot view history.
- History viewing performs no writes.

Required current-record tests:

- View Current Maternity Record requires explicit action.
- Normal completed page does not load live Maternity data.
- Current record requires bridge and underlying Maternity permissions.
- Current record is labelled separately from snapshot.
- Current record changes do not alter snapshot.
- Current record view creates no link or snapshot.
- Invalid/suggested context is not treated as current explicit truth.

Required reopened tests:

- Reopened Consultation shows live projection.
- Previous snapshots remain accessible.
- Reopened view creates no new snapshot.
- Recompletion creates the new version through the existing completion flow.
- UI then defaults to the new latest version.

Required readiness UI tests:

- Readiness flag off renders no card.
- Advisory warning renders when enabled.
- Warning states completion is allowed.
- Gynaecology with no explicit context gets no warning.
- Readiness UI creates no record.
- Existing Consultation readiness result remains unchanged.

Required print tests:

- Active print uses live projection.
- Completed print uses snapshot.
- Selected historical version prints that version.
- Completed without snapshot does not print live values as historical values.
- Print includes historical/source labels.
- Print includes no raw JSON.
- Print creates no snapshot or other write.

Required permission/privacy tests:

- Summary permission required.
- Current-record access requires underlying Maternity permission.
- User cannot access another Consultation’s snapshot.
- Protected narrative fields are absent.
- Billing fields are absent.
- STI/sexual history is absent.

Required performance assertions:

- Flags off add zero queries.
- Completed snapshot default does not fan out into Maternity tables.
- Three and five newborns have the same bounded query count.
- Snapshot partials perform no queries.
- Current live record is lazy/on-demand.
- Presentation service runs once per request.
- No N+1 in history or newborn lists.

Regression:

- Phase 14R.6 suites remain green.
- Phase 14R.5.1 and 14R.5 suites remain green.
- Phase 14R.2–14R.4.1 suites remain green.
- Emergency baseline remains unchanged.
- Admission/Maternity baselines remain unchanged.
- `tests/Feature/Consultations` remains at the documented baseline.
- No invoice item or Maternity Billing Event is created.
- EN/FR parity passes.

Run:

```bash
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
php artisan test tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php
php artisan test tests/Feature/ConsultationMaternityBridgePhase14R2Test.php

php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php
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

php artisan route:list --name=consultation
php artisan route:list --name=maternity
php artisan view:clear
php artisan config:clear
git diff --check -- . ':!docs/prompt.md'

Only run the print test command if that test file is created.

Run PHP syntax checks on every changed PHP, Blade and localisation file.

Run the existing project-safe Blade compile/lint process.

Baseline handling:

tests/Feature/Consultations may retain exactly the documented 22
pre-existing failures.
AntenatalCarePhase9Test may retain its documented pre-existing failure.
AdmissionNursingCarePhase6Test may retain its documented pre-existing
failure.
Phase 14R.6.1 must introduce zero new failures.
Do not run composer test:wide.
Manual pilot acceptance

A. Active Obstetrics Consultation

Enable Maternity summary.
Link an explicit Pregnancy Profile.
Add ANC and Labor context.
Open summary preview.
Confirm Current Maternity Record renders.
Confirm no snapshot is created by preview.

B. Complete Consultation

Enable snapshot capture.
Complete the Consultation.
Open summary.
Confirm Completion Snapshot v1 is default.
Confirm integrity state is verified.

C. Change Maternity after completion

Change Pregnancy Profile or ANC data.
Reopen completed summary.
Confirm historical snapshot is unchanged.
Explicitly open Current Maternity Record.
Confirm current values appear separately.

D. Reopen/recomplete

Reopen Consultation.
Confirm live projection plus v1 history.
Update Maternity.
Recomplete.
Confirm v2 becomes default.
Confirm v1 remains readable and unchanged.

E. Completed without snapshot

Complete a Consultation with capture disabled.
Enable capture later.
Open summary.
Confirm no historical snapshot is fabricated.
Confirm current values are available only through the separate current
record action.

F. Gynaecology

Complete unlinked Gynaecology.
Confirm no Maternity section.
Link a Pregnancy Profile explicitly.
Confirm limited projection and unchanged Gynaecology behavior.

G. Hash mismatch controlled test

In a non-production test database, alter a copied snapshot payload directly.
Open its history view.
Confirm mismatch warning.
Confirm the UI does not repair, overwrite or delete it.

H. Print

Print active summary.
Print completed summary.
Print version 1 after version 2 exists.
Confirm each is clearly labelled and no live/current values are mixed into
historical output.
Documentation

Create:

docs/maternity/OBGYN_MATERNITY_SUMMARY_SNAPSHOT_UI_PHASE_14R_6_1_REPORT.md

The report must include:

Actual summary/rendering architecture audited.
Real include points.
Files changed.
Typed presentation service/view model.
Feature-flag behavior.
Active live-summary behavior.
Completed snapshot behavior.
No-snapshot behavior.
Reopened/recompleted behavior.
Snapshot-history navigation.
Integrity-verification UI.
Current-live-record separation.
Gynaecology behavior.
Readiness UI status.
Print behavior.
Permission/privacy behavior.
Query counts.
Tests/checks.
Baseline comparison.
Manual acceptance results.
Existing workflows protected.
Known risks.
Confirmation that K1 is closed.
Rollout.
Rollback.
Next phase recommendation.

Update:

docs/maternity/OBGYN_MATERNITY_READINESS_SUMMARY_RECONCILIATION_PHASE_14R_6_REPORT.md
docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md
docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md

Mark K1 CLOSED only after:

real summary preview renders live projection
completed summary defaults to snapshot
snapshot history is visible
current live data is visually separate and permission-controlled
print uses snapshot for completed Consultation
no-snapshot Consultations do not fabricate historical data
manual scenarios A–H pass
Rollout

Keep flags false after deployment:

CONSULTATION_MATERNITY_READINESS_ENABLED=false
CONSULTATION_MATERNITY_SUMMARY_ENABLED=false
CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED=false

Pilot order:

Enable advisory readiness.
Enable live Maternity summary.
Review active summary with Obstetrics clinicians.
Enable snapshot capture for a controlled pilot.
Complete one pilot Consultation.
Change Maternity data.
Confirm the historical snapshot does not change.
Reopen/recomplete and confirm version history.
Test print.
Test completed Consultation with no snapshot.

Do not enable write guards in an environment until its reconciliation dry-run
has been reviewed.

Rollback

Readiness:

disable readiness flag
card disappears
Consultation completion remains unchanged

Summary:

disable summary flag
Maternity section disappears
existing Consultation summary returns to previous rendering

Snapshot capture:

disable snapshot-capture flag
no new snapshots are captured
existing snapshots remain immutable
existing snapshot rows are not deleted

No destructive migration rollback is required.

Boundaries

Do not modify snapshot schema unless an actual defect prevents UI use.
Do not change snapshot completion identity in this UI phase.
Do not reopen R6 architecture.
Do not add snapshot mutation routes.
Do not expose raw JSON.
Do not fabricate old snapshots.
Do not compare historical/current values automatically.
Do not change readiness from advisory to blocking.
Do not change Gynaecology readiness.
Do not run reconciliation writes.
Do not enable Maternity billing posting.
Do not add billing cards.
Do not create Invoice Items.
Do not run manual-test seeding yet.
Do not run composer test:wide.
Do not touch docs/prompt.md.

Final response

At the end, provide:

Summary.
Real summary include points.
Feature flags.
Active/live behavior.
Completed snapshot behavior.
Version-history behavior.
Current-record separation.
Integrity-state behavior.
Print behavior.
Query counts.
Permissions/privacy.
Tests and baseline comparison.
Manual acceptance results.
Existing workflows protected.
Known risks.
Confirmation that K1 is closed.
Rollout and rollback.
Next phase recommendation.

Next phase after Phase 14R.6.1 passes:

Phase 14R.7 — O&G/Maternity Manual-Test Data, Environment Reconciliation
Review, Pilot Acceptance and Wider Regression.


This should be the final small closure phase before 14R.7. The key rule is simple: **active consultations show current Maternity truth; completed consultations show what was true at completion; current data is available separately and must never silently rewrite history.**
OBGYN_MATERNITY_READINESS_SUMMARY_RECONCILIATION_PHASE_14R_6_REPORT(1).md
File
Phase 14R.6 is complete. R6 is closed.

Summary
Six dark-by-default flags add a traceability layer: advisory readiness, a curated live maternity projection in the consultation summary, an immutable versioned snapshot captured at completion, a read-only reconciliation dry run, and a declared billing de-duplication policy. Nothing posts, nothing blocks, nothing backfills.

Completion & summary integration point (from the audit)
ConsultationRouteService::completeRoute() owns the completion transaction and already early-returns when the route is completed — so completion was idempotent before I touched it, and recompletion after a reopen stamps a fresh completed_at. That gave me a real completion identity (route id + completed_at) rather than an invented token. Capture goes inside that transaction, after the status write.

Summaries turned out to be generated live and never persisted — which is exactly why the snapshot matters: without it, a completed consultation's summary would silently re-render from current maternity data.

Two schema facts corrected my first draft: consultation_specialty_entries has no patient_id (the patient comes via the route), and consultation_specialty_service_mappings has no maternity key column — so billing overlap is a declared section_key map, documented in the service, not an invented column.

Key guarantees, all test-enforced
Immutability — update(), dirty save(), delete() and forceDelete() all throw; the table has no updated_at and no soft deletes.
Transactional — rolling back an outer transaction removes the snapshot and the completion.
Versioned — reopen + recomplete writes v2; v1 stays byte-identical with its hash intact.
Never fabricated — turning the snapshot flag on later does not retroactively invent history.
Explicit-only — a suggested/ambiguous/invalid context carries advisory status and zero clinical values.
Advisory — blocksCompletion() returns false unconditionally; a warning still completes.
Zero writes — the dry run makes no insert/update/delete (asserted via a query listener); --apply exits non-zero.
Base ≠ duplicate — the attendance fee is not a specialty billing application, so it can't even be matched as a duplicate.
Hash caveat stated plainly in the report: it's tamper evidence, not non-repudiation — anyone able to rewrite the row could recompute it.

Results
Check	Result
New suites	65 passed (13 + 20 + 19 + 13)
All 14R.2 → 14R.6 combined	280 passed, 1 skipped
Emergency	91 passed
Admission + Maternity 8–14.1	84 passed, 2 failed — both documented pre-existing
tests/Feature/Consultations	230 / 22 — exactly baseline
EN/FR parity	182 · 197 · 47 · 18 · 21 — all clean
php -l / git diff --check	27/27 · clean
All 16 flags	false
Query counts: every flag-off surface 0. Completed summary from snapshot 2 queries with no live fan-out. 3 newborns and 5 newborns both cost 14 — no N+1. Billing policy off 0.

Reconciliation in this environment: 0 rows in all five classifications. Must be re-measured per environment before enabling any write guard there.

Known risks
K1 — the summary/snapshot Blade UI isn't authored yet. The projection is wired into the summary source collector and the service exposes latestFor()/historyFor(), so this is a template-only slice, but until it lands the data is reachable only programmatically. This is the one part of §7–8 of the spec I did not complete, and I'm flagging it rather than claiming it.
K2 — the completion reference is second-granular; two completions on one route inside the same second would collapse (requires a reopen + recompletion within one second).
K3 — billing overlap is a declared section map, not a schema relationship.
K4 — 0 reconciliation rows here proves the command runs, not that it classifies real data correctly at scale.
K5 — hash caveat above.
Boundaries held: no readiness blockers, no Gynaecology change, no snapshots for unconfirmed context, no reconciliation writes, no billing posting, no invoice items, no billing UI in clinical workspaces, no route renames, composer test:wide not run, docs/prompt.md untouched.

Next: Phase 14R.7 — manual-test data, environment reconciliation review, pilot acceptance and wider regression. I'd suggest closing K1 first, since scenarios B/C/D in the manual plan need the UI to be exercised by a clinician.
Worked for 2m 22s

This is the Phase 14R.6 completion report again, not a Phase 14R.6.1 result. Based on the report, R6 is closed architecturally, but K1 remains open: the snapshot and summary data are still not rendered in the real Consultation summary, history, current-record comparison, or print interfaces.

So the sequence should remain:

14R.6 architecture and persistence     ✅ complete
14R.6.1 summary/snapshot UI            ⏳ next
14R.7 pilot data and wider regression  🚫 not yet

Do not proceed directly to 14R.7 yet. Its clinician pilot scenarios require users to see and verify:

the live Maternity Context in an active Consultation;
the immutable completion snapshot in a completed Consultation;
snapshot versions after reopen/recomplete;
the separately labelled Current Maternity Record;
integrity verification state;
snapshot-based print output;
the honest no-snapshot state for historical Consultations.

The Phase 14R.6.1 prompt from my preceding response remains the correct next prompt. Its decisive acceptance criteria are:

CONSULTATION_MATERNITY_SUMMARY_ENABLED=false must preserve the existing summary with zero added queries.
An active, explicitly linked Consultation must show Current Maternity Record.
A completed Consultation must show Maternity Context at Consultation Completion from the snapshot by default.
Changing Pregnancy/ANC data after completion must not change the historical summary.
Reopen and recomplete must create and display version 2 while version 1 remains unchanged.
A completed Consultation without a snapshot must state that no historical snapshot was captured; it must never fabricate one.
View Current Maternity Record must be a separate, permission-controlled, on-demand view.
Snapshot history must be read-only and scoped to the correct Consultation.
Hash mismatch must warn without repairing or rewriting anything.
Completed Consultation print output must use the selected completion snapshot—not current Maternity data.
No snapshot update/delete route, billing action, specialty entry, or clinical mutation may be introduced.
K1 is closed only after the real summary, preview, history and print surfaces are manually exercised.

One point worth preserving exactly: the snapshot hash remains tamper evidence, not non-repudiation. The UI should say “verified against the stored capture hash,” not imply that the record is externally signed or impossible for a privileged database actor to rewrite.

After 14R.6.1 passes, proceed to:

Phase 14R.7 — O&G/Maternity Manual-Test Data,
Environment Reconciliation Review,
Pilot Acceptance and Wider Regression
