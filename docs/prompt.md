You are working inside the UHMS Laravel project.

Phase 14R.1 is complete.

The audit established:

- No consultation-to-maternity bridge currently exists.
- Obstetrics duplicates Pregnancy Profile and ANC data through writable specialty JSON entries.
- The duplication is structural but not yet materialised in the audited environment.
- Consultation must own the encounter.
- Maternity must own the longitudinal pregnancy journey.
- Admission must own ward, nursing and discharge.
- Existing order systems must continue owning investigations, procedures, prescriptions and tasks.

Approved architecture:

- Use `consultation_maternity_links`.
- Use explicit nullable foreign keys, not a polymorphic context ID.
- Resolve maternity context in this order:
  1. Explicit active consultation link.
  2. Same visit.
  3. Same admission.
  4. Single active pregnancy profile.
  5. None.
- Multiple candidate pregnancy profiles must return `ambiguous`.
- Never auto-create or auto-link a pregnancy profile.
- Never auto-start ANC, labor, delivery, newborn or postnatal workflows.

Approved clinical decisions:

R2:
- Gynaecology `menstrual_history.lmp` remains Consultation-owned.
- Never auto-sync it to Pregnancy Profile.
- A later explicit one-way “Use this LMP for pregnancy dating” action is allowed.
- `dating_method` will become Pregnancy Profile-owned in Phase 14R.3, not this phase.

R4:
- Gynaecology obstetric history remains editable encounter history when no pregnancy profile is linked.
- When a profile is linked, gravida/para/abortions/living children/previous caesarean become read-only Maternity projections.
- Existing consultation entries remain preserved.

R6:
- Completed consultations will eventually receive an immutable versioned Maternity Context snapshot.
- Snapshot implementation is deferred to Phase 14R.6.
- Phase 14R.2 must not implement summary snapshots.

R5:
- Order-set retargeting is deferred to Phase 14R.4.

Goal of Phase 14R.2:

Create the additive Consultation–Maternity bridge table, model, context DTO, resolver and link service.

This phase must not change the Obstetrics or Gynaecology workspace UI.
It must not convert specialty sections.
It must not change consultation completion.
It must not change maternity billing.
It must not run historical reconciliation or backfills.

1. Audit the actual encounter model before migration

Confirm that the project’s consultation encounter key is:

`visit_consultation_routes.id`

Confirm:

- `ConsultationSpecialtyEntry.consultation_id` points to that key.
- The related model is `VisitConsultationRoute` or the project’s actual equivalent.
- Patient, visit and admission context can be resolved safely from this model.

Use the actual project names and relationships.

Do not create a second consultation-session identity.

2. Add bridge enums

Add enums or equivalent typed constants following project convention:

`ConsultationMaternityContextType`

Values:

- pregnancy_profile
- maternity_case
- anc_visit
- labor
- delivery
- newborn
- postnatal

`ConsultationMaternityLinkRole`

Values:

- primary
- reviewed
- created
- handoff
- historical

Do not accept arbitrary context types or roles.

3. Add `consultation_maternity_links`

Create an additive migration such as:

`database/migrations/2026_XX_XX_XXXXXX_create_consultation_maternity_links_table.php`

Recommended columns:

- id
- consultation_route_id
- pregnancy_profile_id nullable
- maternity_case_id nullable
- antenatal_visit_id nullable
- labor_episode_id nullable
- delivery_record_id nullable
- newborn_record_id nullable
- postnatal_case_id nullable
- context_type
- link_role
- linked_by nullable
- linked_at
- unlinked_by nullable
- unlinked_at nullable
- reason nullable
- metadata nullable JSON
- active_slot nullable tiny integer
- timestamps

`active_slot` behavior:

- Active link: `active_slot = 1`
- Unlinked historical row: `active_slot = null`

Add a unique index on:

- consultation_route_id
- context_type
- active_slot

Reason:
MySQL does not support a normal partial unique index using
`WHERE unlinked_at IS NULL`.

MySQL unique indexes permit multiple NULL values, so this pattern allows:

- one active link per consultation/context type
- unlimited historical unlinked rows

The link service must always update:

- `unlinked_at`
- `unlinked_by`
- `active_slot = null`

inside the same transaction.

Do not use a simple unique index on:

- consultation_route_id
- context_type
- is_active

because that would prevent retaining multiple inactive historical rows.

Add indexes for:

- pregnancy_profile_id
- maternity_case_id
- antenatal_visit_id
- labor_episode_id
- delivery_record_id
- newborn_record_id
- postnatal_case_id
- linked_at
- unlinked_at

Use the project’s normal foreign-key deletion conventions while preserving audit history where practical.

4. Add `ConsultationMaternityLink` model

Add:

`app/Models/ConsultationMaternityLink.php`

Relationships:

- consultationRoute
- pregnancyProfile
- maternityCase
- antenatalVisit
- laborEpisode
- deliveryRecord
- newbornRecord
- postnatalCase
- linkedBy
- unlinkedBy

Scopes/helpers:

- active
- historical
- forConsultation
- forContextType
- isActive
- targetRecord
- rootPregnancyProfile

Add relationships on the existing encounter model:

- maternityLinks
- activeMaternityLinks

Add reverse relationships to maternity models where useful, but do not create unnecessary eager-loading chains.

5. Enforce link consistency

A link row must be created through the link service, not by accepting arbitrary foreign-key combinations from a controller.

For every supported target, derive IDs from the actual model:

PregnancyProfile:
- context_type = pregnancy_profile
- pregnancy_profile_id = target ID

MaternityCase:
- context_type = maternity_case
- maternity_case_id = target ID
- pregnancy_profile_id derived from target where available

AntenatalVisit:
- context_type = anc_visit
- antenatal_visit_id = target ID
- pregnancy_profile_id derived from target

LaborEpisode:
- context_type = labor
- labor_episode_id = target ID
- pregnancy_profile_id derived from target

DeliveryRecord:
- context_type = delivery
- delivery_record_id = target ID
- pregnancy_profile_id derived from target

NewbornRecord:
- context_type = newborn
- newborn_record_id = target ID
- pregnancy_profile_id derived from target
- mother patient context derived from target

PostnatalCase:
- context_type = postnatal
- postnatal_case_id = target ID
- pregnancy_profile_id derived from target

Do not allow the caller to provide mismatched target IDs.

If a row is inconsistent or the target chain is invalid, fail closed.

6. Validate patient ownership

The consultation patient must match the maternity mother/pregnancy patient.

Rules:

- Pregnancy, ANC, labor, delivery and postnatal targets must belong to the same patient as the consultation route.
- Newborn context in this O&G bridge must match the consultation’s mother through `mother_patient_id`.
- A future newborn-to-paediatrics bridge is out of scope.
- Explicit links may span different visits because the maternity record is longitudinal.
- Visit mismatch alone must not block a valid same-patient explicit link.
- Patient mismatch must always block the link.

Return a clear validation/domain exception.

7. Add context result DTO

Create a typed result such as:

`ConsultationMaternityContext`

It should expose:

- status:
  - resolved
  - ambiguous
  - none
  - invalid
- resolution_source:
  - explicit
  - visit
  - admission
  - active_profile
  - none
- consultation route
- pregnancy profile
- maternity case
- latest/relevant ANC visit
- labor episode
- delivery record
- newborn records collection or primary newborn where relevant
- postnatal case
- admission context where available
- active link records
- warnings
- candidate pregnancy profiles for ambiguous results

Do not return a loosely structured array if the project supports typed DTOs.

8. Add `ConsultationMaternityContextResolver`

Create:

`app/Services/Consultation/Maternity/ConsultationMaternityContextResolver.php`

Resolution behavior:

A. Explicit links

- Load active explicit links for the consultation.
- Validate their target chains.
- Aggregate them into one maternity context bundle.
- Explicit valid links take precedence over inferred visit/admission/profile context.
- Do not silently ignore an inconsistent explicit link; return an invalid warning/result.

B. Same visit fallback

When no explicit active links exist:

- Collect maternity records associated with the consultation visit.
- Derive unique pregnancy-profile candidates.
- One unique profile: resolve it.
- More than one profile: return ambiguous.
- Zero: continue to admission fallback.

C. Same admission fallback

- Resolve the consultation/admission relationship using existing project relationships.
- Collect maternity records associated with that admission.
- Derive unique pregnancy-profile candidates.
- One unique profile: resolve it.
- More than one: ambiguous.
- Zero: continue.

D. Single active profile fallback

- Load active pregnancy profiles for the patient.
- Exactly one: resolve as inferred active-profile context.
- More than one: return ambiguous.
- None: return none.

Rules:

- Never persist a link during resolution.
- Never create a pregnancy profile.
- Never select “latest” when several candidate profiles exist.
- Never infer pregnancy from patient sex.
- Never infer pregnancy from complaint, diagnosis, pregnancy test or specialty.
- Never start ANC/labor/delivery/postnatal.
- Use existing Maternity overview services to assemble summaries where appropriate instead of duplicating domain logic.
- Keep queries eager-loaded and bounded.

9. Add `ConsultationMaternityLinkService`

Create:

`app/Services/Consultation/Maternity/ConsultationMaternityLinkService.php`

Required operations:

- link
- relink
- unlink
- getActiveLink
- getActiveLinks
- validateTarget
- deriveContextPayload

Suggested signatures should use actual project style, but behavior must include:

`link`:

- Validate supported target model.
- Validate patient ownership.
- Derive context type and all foreign keys.
- Start transaction.
- Lock active link rows for the consultation/context type.
- If the same target is already active, return it idempotently.
- If a different target is active, require explicit relink behavior rather than silently replacing it.
- Insert one active row with `active_slot = 1`.
- Write ActivityLog.

`relink`:

- Require reason.
- Transactionally soft-unlink the old active row.
- Set old `active_slot = null`.
- Create the new active row.
- Preserve the old row.
- Write ActivityLog with old/new IDs.

`unlink`:

- Require reason.
- Set unlinked actor/time and `active_slot = null`.
- Never delete the row.
- Write ActivityLog.

Concurrency:

- Use transaction and row locking.
- Allow the unique active-slot index to serve as the final race-condition guard.
- Convert duplicate-key races into a clear domain error or idempotent result.

10. Add activity logging

Use the existing activity-log conventions.

Log actions such as:

- CONSULTATION_MATERNITY_CONTEXT_LINKED
- CONSULTATION_MATERNITY_CONTEXT_RELINKED
- CONSULTATION_MATERNITY_CONTEXT_UNLINKED

Metadata should include:

- consultation route ID
- context type
- link role
- target record ID
- pregnancy profile ID
- old link/target ID when relinking
- actor
- reason where required

Do not log clinical notes or sensitive maternity content.

11. Add permissions

Add permissions additively:

- `consultation.maternity_context.view`
- `consultation.maternity_context.link`
- `consultation.maternity_context.unlink`

Role behavior:

- Admin/super-admin receives all.
- Clinical role assignment must follow existing Consultation and Maternity role conventions.
- Do not grant bridge permissions to a role that cannot view the underlying consultation or maternity records.
- The bridge must never escalate access to Maternity.
- Future UI actions will require both:
  - bridge permission
  - underlying maternity permission

Do not add create-profile, record-ANC or start-labor permissions until the workspace phase that uses them.

12. Add EN/FR localisation

Create or update a dedicated file such as:

- `lang/en/consultation_maternity.php`
- `lang/fr/consultation_maternity.php`

Include strict parity for:

- maternity context
- context types
- link roles
- context linked
- context relinked
- context unlinked
- link reason
- unlink reason
- patient mismatch
- inconsistent context
- no maternity context
- ambiguous maternity context
- multiple active pregnancy profiles
- explicit link
- same visit context
- same admission context
- active pregnancy context
- invalid target
- unsupported target

No workspace UI is required yet, but service/domain messages should be localisable.

13. Update architecture documents with approved decisions

Update:

- `docs/maternity/OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`
- `docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md`
- `docs/maternity/OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md`

Record that:

R2:
- Gynaecology LMP remains Consultation-owned.
- Explicit one-way adoption is approved.
- `dating_method` will become Pregnancy Profile-owned in 14R.3.

R4:
- Gynaecology obstetric history is RW when unlinked and RO projection when linked.

R6:
- Immutable versioned completion-time Maternity snapshot is approved for 14R.6.

R5:
- Order-set automatic specialty-entry patches will be retargeted in 14R.4.

Record that the explicit-FK bridge design is approved.

14. Tests

Add:

`tests/Feature/ConsultationMaternityBridgePhase14R2Test.php`

Required tests:

- Bridge can link a pregnancy profile to a consultation.
- Bridge can link an ANC visit and derives the correct pregnancy profile.
- Bridge can link labor, delivery, newborn and postnatal targets.
- Unsupported model target is rejected.
- Mismatched patient target is rejected.
- Newborn target validates against mother patient.
- Same target link is idempotent.
- A different target of the same context type requires relink.
- Relink preserves old historical row.
- Only one active row exists per consultation/context type.
- Multiple inactive historical rows are allowed.
- Unlink requires a reason.
- Link/relink/unlink activity logs are written.
- Resolver returns explicit link first.
- Resolver falls back to same visit.
- Resolver falls back to same admission.
- Resolver falls back to a single active profile.
- Resolver returns `none` when no context exists.
- Resolver returns `ambiguous` for multiple active profiles.
- Resolver does not create a pregnancy profile.
- Positive pregnancy test, diagnosis and patient sex do not cause creation or linking.
- Explicit link survives consultation completion.
- No specialty entry is created by bridge operations.
- No maternity record is created by resolver operations.

Run targeted checks only:

- `php artisan test tests/Feature/ConsultationMaternityBridgePhase14R2Test.php`
- Relevant existing Consultation Specialty tests
- `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
- `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
- `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
- `php artisan route:list --name=consultation`
- `php artisan route:list --name=maternity`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check -- . ':!docs/prompt.md'`
- PHP syntax checks on all changed PHP files

Do not run `composer test:wide`.

15. Documentation report

Create:

`docs/maternity/OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`

Include:

- What was implemented
- Files changed
- Migration/table design
- MySQL active-link uniqueness strategy
- Context types and roles
- Link validation
- Resolver behavior
- Ambiguity behavior
- Link/relink/unlink behavior
- Activity logging
- Permissions
- Localisation
- Tests/checks run
- Existing workflows protected
- Known risks
- What was intentionally deferred
- Next recommended phase

16. Boundaries

Do not change Obstetrics workspace behavior.
Do not change Gynaecology workspace behavior.
Do not convert specialty sections.
Do not add maternity ribbon or panels.
Do not create summary projection.
Do not create completion snapshots.
Do not retarget order sets.
Do not add explicit LMP adoption UI.
Do not add `dating_method` yet.
Do not create ANC/labor actions in Consultation.
Do not change consultation readiness.
Do not enable maternity billing posting.
Do not modify Phase 14.1 preview behavior.
Do not run reconciliation or backfill.
Do not auto-link inferred contexts.
Do not create profiles or maternity records from the resolver.
Do not rename existing routes.
Do not touch `docs/prompt.md`.
Do not run the full suite.

17. Final response

At the end, provide a concise completion report with:

- Summary of implementation
- Files changed
- Migration and uniqueness design
- Resolver result behavior
- Link service behavior
- Permissions/localisation
- Tests/checks run
- Existing workflows protected
- Known risks
- Intentionally deferred items
- Next phase recommendation

Recommended next phase:

Phase 14R.3 — Obstetrics Stage-Aware Workspace Integration.
