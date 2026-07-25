You are working inside the UHMS Laravel project.

We are inserting a critical reconciliation phase before continuing with Phase 14.2 maternity billing posting.

Current completed systems:

1. Consultation Specialty Engine
- Shared specialty-profile engine.
- Specialty sections and schemas.
- Obstetrics workspace.
- Gynaecology workspace.
- Quick actions.
- Favourites.
- Order sets.
- Readiness rules.
- Consultation summary builder.
- Doctor workspace.
- Admin specialty configuration.
- Canonical section presentation and duplicate-section de-duplication.
- Existing investigation, prescription, procedure, diagnosis and consultation workflows.

2. Maternity Workflow
- Pregnancy profiles.
- Maternity cases.
- ANC visits.
- Labor episodes.
- Labor observations.
- Delivery records.
- Newborn records.
- Postnatal cases.
- Mother observations.
- Newborn observations.
- Maternity admission-request integration.
- Admission, nursing and discharge-readiness integration.
- Maternity reports.
- Billing mapping readiness.
- Preview-only maternity billing scaffolding.

Problem:

Obstetrics and Gynaecology already exist as Consultation Specialty workspaces, while Maternity now owns a complete longitudinal pregnancy and birth workflow.

We must reconcile them so that:

- Consultation remains the current clinical encounter.
- Maternity remains the longitudinal source of truth.
- Obstetrics and Gynaecology workspaces can use Maternity records.
- Clinical data is not duplicated.
- Existing consultation workflows remain compatible.
- Existing maternity workflows remain compatible.
- Admission, billing, orders, reporting and summaries remain traceable.
- Gynaecology is not forced into pregnancy workflows.
- Obstetrics becomes properly connected to pregnancy, ANC, labor, delivery, newborn and postnatal care.

This phase is audit and architecture first.

Do not perform a broad implementation yet.
Do not remove existing Obstetrics or Gynaecology specialty sections.
Do not delete existing consultation specialty entries.
Do not change billing posting.
Do not continue Phase 14.2 billing posting in this phase.
Do not run the full test suite.
Do not touch docs/prompt.md.

Primary objectives:

1. Audit the Consultation Specialty Engine

Review the current consultation specialty implementation, including:

- Specialty profile resolver.
- Obstetrics specialty profile.
- Gynaecology specialty profile.
- Specialty sections.
- Section schemas.
- Canonical section aliases.
- Section presentation layer.
- Specialty entries.
- Quick actions.
- Favourites.
- Order sets.
- Readiness rules.
- Summary builder.
- Doctor workspace.
- Admin specialty configuration.
- Consultation sessions.
- Consultation routes.
- Visit and admission context.
- Investigation, prescription, procedure and task integration.
- Billing/service mapping integration.
- Permissions.
- EN/FR localisation.
- Existing tests and seeders.

Identify the exact models, services, controllers, Blade components, migrations and tables that own specialty consultation data.

2. Audit Obstetrics and Gynaecology sections field by field

Produce a complete inventory of sections and fields currently used by the Obstetrics and Gynaecology workspaces.

For every field, classify it as:

- Consultation-owned.
- Pregnancy-profile-owned.
- ANC-owned.
- Labor-owned.
- Delivery-owned.
- Newborn-owned.
- Postnatal-owned.
- Admission-owned.
- Shared order/workflow-owned.
- Ambiguous and requiring a decision.

Pay special attention to possible duplicates such as:

- Gravida.
- Para.
- Abortions.
- Living children.
- LMP.
- EDD.
- Gestational age.
- Previous caesarean.
- Previous postpartum haemorrhage.
- Current pregnancy risks.
- Fetal heart rate.
- Fundal height.
- Presentation.
- Labor stage.
- Delivery mode.
- Delivery outcome.
- APGAR.
- Birth weight.
- Breastfeeding status.
- Postnatal condition.

Do not assume duplicate fields are harmless.

Identify:

- Fields that currently store competing copies.
- Fields that should become read-only projections.
- Fields that should call Maternity services.
- Fields that must remain in Consultation.
- Existing records that would need reconciliation or safe migration.

3. Establish the source-of-truth matrix

Create a definitive source-of-truth matrix.

The default ownership direction should be:

Consultation owns:

- Complaints.
- HOPC.
- General and specialty examination narrative.
- Clinical assessment.
- Diagnoses.
- Consultation plan.
- Orders.
- Prescriptions.
- Procedures.
- Clinician notes.
- Encounter-level final summary.

Maternity owns:

- Pregnancy profile and obstetric history.
- ANC visits.
- Labor episodes and observations.
- Delivery records.
- Newborn records.
- Postnatal cases and observations.

Admission owns:

- Ward/bed.
- Nursing workflow.
- Inpatient care flags.
- Discharge readiness and discharge summary.

Existing order systems own:

- Investigations.
- Radiology.
- Procedures/theatre requests.
- Prescriptions.
- Pharmacy.
- Clinical tasks.

For each disputed field, document:

- Current owner.
- Target owner.
- Read/write behavior in Consultation.
- Read/write behavior in Maternity.
- Historical data treatment.
- Summary behavior.
- Reporting behavior.
- Billing implications.

4. Define the three independent contexts

The architecture must explicitly separate:

A. Department context

This determines where the clinician is working:

- Consultation department.
- Maternity department.
- Ward.
- Emergency.
- Other operational area.

B. Specialty workspace context

This determines which consultation form is presented:

- Obstetrics.
- Gynaecology.
- Another specialty.

C. Maternity clinical context

This determines the longitudinal maternity stage:

- No active pregnancy context.
- Pregnancy profile.
- ANC.
- Labor.
- Delivery.
- Newborn.
- Postnatal.

Do not assume department type, specialty profile and maternity stage are the same concept.

Document how each context is resolved and displayed.

5. Design a Consultation–Maternity context bridge

Audit whether the project already has a generic clinical-context link that can be reused.

If one exists, propose using it.

If not, design a safe bridge such as:

- `consultation_maternity_links`
- or a generic `consultation_clinical_context_links`

The bridge should support linking a consultation session to:

- Pregnancy profile.
- Maternity case.
- ANC visit.
- Labor episode.
- Delivery record.
- Newborn record.
- Postnatal case.

Recommended bridge information:

- consultation_session_id or the project’s actual consultation encounter key.
- pregnancy_profile_id as the longitudinal root where applicable.
- context type.
- context record ID or explicit nullable foreign keys.
- link role:
  - primary
  - reviewed
  - created
  - handoff
  - historical
- linked_by.
- linked_at.
- unlinked_by.
- unlinked_at.
- reason.
- metadata where necessary.
- timestamps.

The audit must decide whether explicit foreign keys or a polymorphic link is safer for this project.

Do not implement a new table in this audit phase unless the existing architecture makes the choice unambiguous and the change is very small.

6. Define maternity-context resolution rules

Design a resolver such as:

`ConsultationMaternityContextResolver`

Recommended resolution order:

1. Explicit consultation-to-maternity link.
2. Maternity record linked to the same visit.
3. Maternity record linked to the same admission.
4. A single active pregnancy profile for the patient.
5. No automatic context.

Rules:

- Never silently select between multiple active pregnancy profiles.
- Never create a pregnancy profile based only on patient sex.
- Never create a pregnancy profile based only on a complaint or diagnosis.
- Never auto-start ANC, labor, delivery or postnatal workflows.
- Require explicit clinician action for creation or transition.
- Log linking, relinking and unlinking.
- Preserve historical links after consultation completion.

7. Define the Obstetrics workspace target

The Obstetrics consultation workspace should become stage-aware.

Propose a contextual header or ribbon showing:

- Active pregnancy profile.
- Gestational age.
- EDD.
- Risk level.
- Latest ANC visit.
- Next ANC date.
- Current maternity case.
- Admission/ward/bed if admitted.
- Active labor episode if present.
- Delivery status.
- Newborn records pending/complete.
- Postnatal status.

Propose context-aware panels/tabs:

- Consultation.
- Pregnancy.
- ANC.
- Labor and Delivery.
- Newborn.
- Postnatal.
- Orders.
- Summary.

Do not show all panels as editable forms at once.

Recommended behavior:

Pregnancy profile context:

- Show pregnancy summary.
- Allow explicit create/link profile action.

ANC context:

- Show latest ANC summary.
- Allow “Record ANC Visit” using the existing `AntenatalVisitService`.
- Do not save ANC measurements as generic consultation specialty entries.

Labor context:

- Show active labor episode and latest observation.
- Allow “Open Labor Workspace” or explicit “Start Labor Episode”.
- Use `LaborEpisodeService` and `LaborObservationService`.

Delivery context:

- Show delivery record.
- Allow navigation to the existing delivery workflow.
- Do not duplicate delivery fields in Consultation.

Newborn context:

- Show newborn summary.
- Navigate to newborn records.
- Do not duplicate APGAR/birth-weight data.

Postnatal context:

- Show postnatal readiness and latest observations.
- Navigate to postnatal workspace.
- Do not duplicate mother/newborn observations.

8. Define the Gynaecology workspace target

Gynaecology must remain a consultation specialty workspace for non-pregnancy reproductive health.

Identify fields that should remain consultation-owned, such as where currently supported:

- Menstrual history.
- Abnormal bleeding.
- Pelvic pain.
- Infertility history.
- Contraception.
- Cervical screening.
- Gynaecological surgery history.
- Pelvic examination.
- Gynaecological diagnoses.
- Gynaecological treatment plan.

Rules:

- Do not display the full maternity workflow by default.
- Show a small maternity/pregnancy context card only when:
  - an active pregnancy profile is explicitly linked,
  - or the clinician explicitly chooses to create/link one.
- Do not automatically switch a Gynaecology consultation to Obstetrics.
- Provide an explicit “Start/Link Pregnancy Workflow” action when appropriate.
- Preserve the original Gynaecology consultation session and history.

9. Define shared component strategy

Do not create separate duplicate maternity forms inside Consultation.

Design reusable components or view models:

- Pregnancy summary card.
- ANC summary card.
- Labor summary card.
- Delivery summary card.
- Newborn summary card.
- Postnatal summary card.
- Maternity context selector.
- Maternity context warning.
- Maternity workflow quick-action panel.

These components should consume the existing Maternity services and models.

They may be displayed in:

- Obstetrics consultation workspace.
- Gynaecology consultation workspace when explicitly linked.
- Admission workspace.
- Emergency workspace.
- Maternity workspace.

10. Define service/orchestration layer

Design services such as:

- `ConsultationMaternityContextResolver`
- `ConsultationMaternityLinkService`
- `ObstetricConsultationContextService`
- `GynaecologyConsultationContextService`
- `ConsultationMaternitySummaryService`
- `ConsultationMaternityReadinessService`

These services should orchestrate existing domain services.

They must not recreate Maternity business logic.

Example actions:

- Link pregnancy profile to consultation.
- Create pregnancy profile from consultation through `PregnancyProfileService`.
- Record ANC visit through `AntenatalVisitService`.
- Start labor through `LaborEpisodeService`.
- Create maternity admission request through the existing admission-request service.
- Open delivery/newborn/postnatal records through their existing services.
- Build read-only consultation maternity summary.

11. Define consultation readiness behavior

The current specialty engine already has readiness rules.

Design stage-aware readiness without breaking existing consultation completion.

Recommended approach:

- Advisory first.
- Enforcement disabled by default.
- Gynaecology consultations remain governed by their existing readiness rules.
- General Obstetrics consultations require normal consultation readiness.
- ANC-mode Obstetrics consultations may warn when no ANC visit was recorded.
- Labor-review consultations may warn when no labor episode is linked.
- Postnatal-review consultations may warn when no postnatal case is linked.

Suggested config:

- `CONSULTATION_OBSTETRIC_MATERNITY_READINESS_ENFORCED=false`

Do not introduce hard blockers in the first implementation phase.

12. Define consultation summary integration

Consultation summary should include a generated Maternity Context section when linked.

The section may include:

- Pregnancy profile summary.
- Latest ANC summary.
- Labor stage/latest observation.
- Delivery outcome.
- Newborn summary.
- Postnatal readiness.

Rules:

- The summary projection reads from Maternity records.
- It must not create duplicate specialty entries.
- Identify whether a frozen completion-time snapshot is required for medico-legal history.
- If snapshots are proposed, clearly distinguish:
  - Maternity source of truth.
  - Consultation completion snapshot.

Do not overwrite existing consultation final summary behavior.

13. Define order integration

Investigations, prescriptions, procedures, theatre requests and tasks should remain in their existing shared workflows.

Design how O&G consultation actions link to those workflows:

- Orders should retain consultation session/visit context.
- Maternity context IDs may be added as secondary source metadata where safe.
- Do not create separate maternity investigation, prescription or procedure engines.
- Do not duplicate orders between Consultation and Maternity.
- Results should be visible from both contexts through shared relationships or projections.

14. Define billing de-duplication rules

Phase 14.1 maternity billing remains preview-only.

Do not enable Phase 14.2 posting until reconciliation is approved.

Audit possible double billing between:

- General consultation service charge.
- Obstetrics specialty consultation charge.
- ANC registration/follow-up charge.
- Labor observation charge.
- Delivery charge.
- Postnatal care charge.

Propose an explicit policy matrix.

Possible policy options may include:

- consultation_only
- maternity_event_only
- both_when_configured
- manual_selection

Choose safe defaults.

Requirements:

- One clinical action must not silently create two charges.
- Recording ANC from the Obstetrics workspace must still have one maternity source record.
- Billing source identity must remain linked to the real source record.
- Consultation billing and maternity-event billing must remain distinguishable.
- Do not reintroduce a billing card into the doctor consultation workspace if the current workspace intentionally excludes it.
- Billing preview/posting should remain permission-controlled outside normal clinical editing where appropriate.

15. Define admission, emergency and inpatient behavior

Design these scenarios:

A. Obstetrics outpatient consultation

- Consultation session opened.
- Pregnancy profile linked.
- ANC visit optionally recorded.
- Orders placed through existing systems.
- Consultation completed normally.

B. Gynaecology consultation with no pregnancy

- No maternity context required.
- Normal specialty consultation flow continues.

C. Gynaecology consultation discovers pregnancy

- Clinician explicitly creates/links pregnancy profile.
- Original Gynaecology consultation remains intact.
- Optional transition/referral to Obstetrics/Maternity.

D. Emergency obstetric case

- Emergency remains operational owner of the emergency episode.
- Maternity context is linked.
- Labor/admission handoff uses existing services.
- No duplicate emergency or labor record.

E. Admitted obstetric patient

- Admission owns bed, nursing and discharge.
- Maternity owns pregnancy/labor/delivery/postnatal.
- Obstetrics specialist consultation owns the specialist encounter.
- The consultation is linked to the active admission and maternity context.

F. Postnatal consultation/review

- Consultation note remains encounter-level.
- Mother/newborn observations remain postnatal records.
- Follow-up plan is linked without duplicating the postnatal case.

16. Define historical-data reconciliation

Audit existing Obstetrics/Gynaecology specialty entries that contain maternity-owned fields.

Propose a safe reconciliation strategy:

- Inventory existing duplicate field values.
- Detect conflicts with current Maternity records.
- Do not overwrite either side automatically.
- Classify records:
  - safe to link
  - safe to migrate
  - conflict requiring review
  - historical-only
  - insufficient context
- Preserve original specialty-entry values for audit.
- Use dry-run reconciliation before any backfill.
- Produce a review report before applying changes.

Do not run any automatic backfill in this phase.

17. Define routes and URL behavior

Preserve current consultation route behavior and department-specific URLs.

Do not rename current consultation routes.

Design links between:

- Consultation specialty workspace.
- Maternity pregnancy profile.
- ANC visit.
- Labor episode.
- Delivery record.
- Newborn record.
- Postnatal case.
- Admission workspace.

Return links should preserve the originating consultation session and workspace where practical.

18. Define permissions

Audit existing Consultation and Maternity permissions.

Propose additive bridge permissions such as:

- `consultation.maternity_context.view`
- `consultation.maternity_context.link`
- `consultation.maternity_context.unlink`
- `consultation.maternity_context.create_profile`
- `consultation.maternity_context.record_anc`
- `consultation.maternity_context.start_labor`
- `consultation.maternity_context.open_postnatal`
- `consultation.maternity_context.summary.view`

Do not duplicate permissions already adequately covered.

Clinical users should only receive actions appropriate to their role.

19. Define localisation

Plan EN/FR keys for:

- Maternity context.
- Link pregnancy profile.
- Create pregnancy profile.
- Active pregnancy.
- No active pregnancy profile.
- Multiple active pregnancy profiles.
- Select maternity context.
- ANC context.
- Labor context.
- Delivery context.
- Newborn context.
- Postnatal context.
- Open maternity workspace.
- Record ANC visit.
- Start labor episode.
- Context linked.
- Context unlinked.
- Historical context.
- Source-of-truth warning.
- Duplicate-data warning.
- Advisory readiness warning.

20. Define tests

Propose targeted tests for the future implementation phases.

Required scenarios:

- Obstetrics workspace loads without maternity context.
- Obstetrics workspace displays linked pregnancy profile.
- Gynaecology workspace does not force maternity context.
- Gynaecology workspace can explicitly link a pregnancy profile.
- Multiple active profiles require explicit selection.
- Consultation cannot silently create pregnancy profile.
- ANC recorded from Consultation creates one `AntenatalVisit`.
- ANC data is not duplicated into generic specialty entries.
- Labor started from Consultation creates one `LaborEpisode`.
- Delivery/newborn/postnatal summaries display from source records.
- Consultation summary includes maternity projection.
- Existing consultation completion still works.
- Existing maternity pages still work.
- Existing admission workflow still works.
- Existing emergency workflow still works.
- Billing remains preview-only.
- No duplicate billing source is introduced.
- EN/FR localisation remains in parity.

21. Produce implementation phases

After the audit, propose a staged implementation plan.

Recommended phases:

Phase 14R.1:
Audit, source-of-truth matrix and bridge architecture.

Phase 14R.2:
Consultation–Maternity bridge foundation and context resolver.

Phase 14R.3:
Obstetrics workspace integration.

Phase 14R.4:
Gynaecology workspace separation and explicit pregnancy transition.

Phase 14R.5:
Admission, emergency, labor, delivery and postnatal handoffs.

Phase 14R.6:
Readiness, summary projection, historical reconciliation and billing de-duplication.

Phase 14R.7:
Manual test data and wider regression.

Each phase must include:

- Goal.
- Files likely to change.
- Models/tables/services.
- UI behavior.
- Permissions.
- Localisation.
- Targeted tests.
- Risks.
- Rollback/compatibility notes.

22. Documentation deliverables

Create:

- `docs/maternity/OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md`
- `docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`
- `docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md`
- `docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md`

The source-of-truth matrix must be field-level, not only module-level.

The integration plan must be specific enough for the next implementation phase.

23. Testing instructions for this audit phase

Do not run the full suite.

If this phase is documentation-only, state that no runtime changes were made.

If small code inspection helpers are added, run only:

- Relevant consultation specialty tests.
- Maternity foundation tests where necessary.
- Route/view/config checks.
- `git diff --check -- . ':!docs/prompt.md'`
- PHP syntax checks on changed files.

Do not run `composer test:wide` in this phase.

24. Boundaries

Do not merge Maternity tables into Consultation tables.
Do not duplicate Maternity fields into specialty entries.
Do not delete current Obstetrics/Gynaecology sections.
Do not auto-create pregnancy profiles.
Do not auto-link ambiguous pregnancy profiles.
Do not auto-start ANC, labor, delivery or postnatal.
Do not enable maternity billing posting.
Do not change invoice/accounting behavior.
Do not rewrite Consultation.
Do not rewrite Maternity.
Do not rewrite Admission.
Do not rewrite Emergency.
Do not rewrite Theatre.
Do not create new investigation, procedure, prescription or pharmacy engines.
Do not run historical backfills.
Do not touch docs/prompt.md.
Do not run the full suite.

25. Final response

At the end, provide a concise report with:

- What was audited.
- Duplicate/overlapping data discovered.
- Proposed source-of-truth decisions.
- Proposed bridge architecture.
- Obstetrics target behavior.
- Gynaecology target behavior.
- Readiness/summary strategy.
- Billing de-duplication strategy.
- Historical-data reconciliation strategy.
- Documentation created.
- Tests/checks run.
- Risks and unresolved decisions.
- Next recommended phase.

Do not begin the broad bridge implementation until the audit and source-of-truth matrix are reviewed.
