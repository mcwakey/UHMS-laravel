You are working inside the UHMS Laravel project.

We have completed:

* Phase 1: Emergency vs Admission gap analysis.
* Phase 2: Admission Workflow Foundation.
* Phase 4: Bed, Ward, Reservation, Transfer, and Location History Workflow.
* Phase 5: Ward Operations, Reservation Expiry, Cleaning Workflow, and Capacity Board.
* Phase 6: Nursing and Inpatient Care Layer.
* Phase 7: Discharge Readiness, Clearance, and Discharge Summary Workflow.
* Phase 8: Maternity Foundation and Pregnancy Profile.
* Phase 9: Antenatal Care Workflow.
* Phase 10: Labor and Delivery Foundation.

Current maternity/labor foundation now includes:

* Pregnancy profiles.
* Maternity cases.
* ANC visits.
* Labor episodes.
* Labor observations.
* Delivery records.
* Advisory labor risk/danger signs.
* Theatre/emergency escalation flags.
* Explicit maternity/labor admission request hook.
* `newborn_records_pending` flag on delivery records.
* Maternity dashboard labor/delivery metrics.
* Pregnancy profile Labor and Delivery panel.
* ANC-to-labor explicit start action.
* EN/FR translations and targeted feature tests.

Now implement Phase 11: Newborn Records and Birth Outcome Workflow.

Goal:
Add newborn records and birth outcome workflow linked to delivery records, labor episodes, pregnancy profiles, maternity cases, patients, visits, and admissions.

This phase should support:

* Creating one or more newborn records from a completed delivery record.
* Multiple birth support: twins/triplets/etc.
* Newborn demographic and birth details.
* APGAR scoring.
* Birth weight and basic newborn condition.
* Resuscitation flag/details.
* Newborn risk flags and danger signs.
* Newborn outcome/status tracking.
* Optional patient-record creation/linking for newborn where safe.
* Newborn summary display on delivery record, labor episode, pregnancy profile, and maternity dashboard.
* Preparing newborn records for postnatal workflow in the next phase.

Important:
This is newborn records and birth outcome workflow only.
Do not implement full postnatal care yet.
Do not implement maternity-specific discharge summaries yet.
Do not implement newborn billing or package billing yet.
Do not implement official birth registration/civil registry integration.
Do not force every newborn to become a separate patient record unless safely configurable and explicitly triggered.
Do not rewrite delivery records.
Do not rewrite labor workflow.
Do not rewrite admission, nursing, MAR, discharge, billing, theatre, or emergency workflows.
Do not run the full test suite.
Do not touch `docs/prompt.md`; it was already modified in the working tree and should remain untouched unless explicitly instructed.

Primary objectives:

1. Audit current labor/delivery foundation first

Before coding, review current implementation for:

* `PregnancyProfile`
* `MaternityCase`
* `AntenatalVisit`
* `LaborEpisode`
* `LaborObservation`
* `DeliveryRecord`
* Delivery record complete workflow
* `newborn_records_pending`
* Maternity dashboard metrics
* Pregnancy profile show page
* Labor episode detail page
* Delivery record detail page
* Existing patient creation patterns
* Existing patient relationship/family/guardian patterns if any
* Existing admission/visit relationships
* Existing activity logging patterns
* Existing permissions/localisation conventions

Document what already exists and avoid duplication.

2. Add Newborn Record model and migration

Create a first-class newborn record linked to delivery records.

Recommended table: `newborn_records`

Recommended fields:

* id
* delivery_record_id
* labor_episode_id
* pregnancy_profile_id
* maternity_case_id nullable
* mother_patient_id
* newborn_patient_id nullable
* visit_id nullable
* admission_id nullable
* department_id nullable
* recorded_by nullable user
* baby_number integer nullable
* birth_order integer nullable
* sex nullable string
* birth_time nullable timestamp
* birth_weight_kg nullable decimal
* length_cm nullable decimal
* head_circumference_cm nullable decimal
* apgar_1_min nullable integer
* apgar_5_min nullable integer
* apgar_10_min nullable integer
* cried_at_birth nullable boolean
* resuscitation_required boolean default false
* resuscitation_details nullable text
* congenital_concerns nullable text
* feeding_status nullable string
* temperature nullable decimal
* breathing_status nullable string
* cord_status nullable string
* colour nullable string
* risk_flags nullable json
* danger_signs nullable json
* neonatal_condition nullable string
* outcome nullable string
* status string
* transferred_to nullable string
* notes nullable text
* created_by nullable user
* updated_by nullable user if project convention supports it
* closed_at nullable timestamp
* closed_by nullable user
* closure_reason nullable text
* timestamps
* soft deletes if project convention supports it

Recommended statuses:

* active
* under_observation
* stable
* at_risk
* transferred
* deceased
* discharged
* closed
* cancelled

Recommended outcomes:

* live_birth
* stillbirth
* neonatal_death
* transferred
* unknown

Important:

* Keep `newborn_patient_id` nullable.
* Newborn record should not require a separate patient record by default.
* Newborn patient creation/linking should be explicit and permission-protected.
* Do not implement full neonatal admission workflow yet.
* Do not implement postnatal care yet.

3. Add newborn enums/constants

Follow project enum conventions.

Add enums/constants such as:

* `NewbornRecordStatus`
* `NewbornOutcome`
* `NewbornSex`
* `NewbornFeedingStatus`
* `NewbornBreathingStatus`
* `NewbornCordStatus`
* `NewbornCondition`
* `NewbornRiskFlag`
* `NewbornDangerSign`

Suggested risk flags:

* low_birth_weight
* premature
* resuscitation_required
* congenital_concern
* maternal_high_risk
* meconium_exposure
* multiple_birth
* poor_apgar
* temperature_instability
* feeding_difficulty

Suggested danger signs:

* difficulty_breathing
* fever
* hypothermia
* poor_feeding
* convulsions
* lethargy
* jaundice
* bleeding
* cyanosis
* infection_signs

Risk/danger signs should be advisory only in this phase.

4. Add relationships

DeliveryRecord:

* hasMany newborn records
* newbornRecordsPending helper/status should update when newborn records exist

LaborEpisode:

* hasMany newborn records through delivery records if useful

PregnancyProfile:

* hasMany newborn records

MaternityCase:

* hasMany newborn records

Patient:

* as mother: hasMany newborn records through `mother_patient_id`
* as newborn patient: hasOne newborn record through `newborn_patient_id` if safe

Visit:

* hasMany newborn records

Admission:

* hasMany newborn records

NewbornRecord:

* belongsTo delivery record
* belongsTo labor episode
* belongsTo pregnancy profile
* belongsTo maternity case nullable
* belongsTo mother patient
* belongsTo newborn patient nullable
* belongsTo visit nullable
* belongsTo admission nullable
* belongsTo department nullable
* belongsTo recordedBy user

5. Add newborn services

Create services such as:

* `app/Services/Maternity/NewbornRecordService.php`
* `app/Services/Maternity/NewbornRiskAssessmentService.php`
* `app/Services/Maternity/NewbornOverviewService.php`

NewbornRecordService should handle:

* Creating newborn record
* Creating multiple newborn records from a delivery record
* Updating newborn record
* Closing/cancelling newborn record
* Linking or creating newborn patient record only when explicitly requested and safe
* Updating delivery record `newborn_records_pending`
* Logging newborn actions

NewbornRiskAssessmentService should:

* Review APGAR, birth weight, resuscitation, temperature, danger signs, and risk flags
* Suggest advisory risk level/warnings
* Avoid hard clinical enforcement

NewbornOverviewService should compose:

* newborn count for delivery
* records pending state
* newborn risk summary
* low birth weight count
* resuscitation count
* newborns under observation
* dashboard metrics
* pregnancy profile newborn summary
* labor/delivery newborn summary

Do not place business logic in Blade.

6. Add routes/controllers

Add protected routes under maternity namespace.

Suggested routes:

* `GET admin/maternity/deliveries/{deliveryRecord}/newborns`
* `GET admin/maternity/deliveries/{deliveryRecord}/newborns/create`
* `POST admin/maternity/deliveries/{deliveryRecord}/newborns`
* `POST admin/maternity/deliveries/{deliveryRecord}/newborns/bulk-create`
* `GET admin/maternity/newborns/{newbornRecord}`
* `GET admin/maternity/newborns/{newbornRecord}/edit`
* `PATCH admin/maternity/newborns/{newbornRecord}`
* `PATCH admin/maternity/newborns/{newbornRecord}/status`
* `PATCH admin/maternity/newborns/{newbornRecord}/close`
* `POST admin/maternity/newborns/{newbornRecord}/link-patient` if safe
* `POST admin/maternity/newborns/{newbornRecord}/create-patient` if safe and consistent with existing patient creation patterns

Use route names consistent with project convention.

Do not remove Phase 8/9/10 maternity routes.
Do not alter admission, emergency, ward, nursing, discharge, billing, medication, ANC, labor, or delivery routes.

7. Add newborn UI

Add newborn workflow pages.

Delivery record detail page should show:

* Newborn records pending indicator
* Expected newborn count from delivery record
* Existing newborn records
* Missing newborn records warning
* Quick action: create newborn record
* Quick action: create multiple newborn records
* Newborn outcome summary
* Placeholder for future postnatal workflow

Newborn create/edit form should include:

* Baby number / birth order
* Sex
* Birth time
* Birth weight
* Length
* Head circumference
* APGAR 1/5/10
* Cried at birth
* Resuscitation required/details
* Congenital concerns
* Feeding status
* Temperature
* Breathing status
* Cord status
* Colour
* Risk flags
* Danger signs
* Neonatal condition
* Outcome
* Status
* Transfer destination
* Notes

Newborn detail page should show:

* Mother/patient banner
* Delivery summary
* Labor summary
* Newborn summary
* APGAR card
* Birth measurements
* Risk/danger warnings
* Resuscitation details
* Outcome/status
* Linked newborn patient record if any
* Quick action: link/create patient record if permitted
* Placeholder for future postnatal observations
* Placeholder for future newborn discharge summary

Pregnancy profile page should show:

* Delivery/newborn summary
* Newborn records linked to completed deliveries
* Newborn records pending warning

Labor episode page should show:

* Delivery record newborn status
* Newborn records list/summary

Maternity dashboard should show newborn metrics.

Keep UI simple and clear.

8. Add multiple birth support

Delivery record already has `newborn_count`.

Implement support for:

* Creating newborn records one by one.
* Bulk creating placeholder newborn records based on newborn_count.
* Preventing duplicate birth order for the same delivery.
* Warning when actual newborn records count is less than `newborn_count`.
* Warning when count is more than `newborn_count`, but do not hard block unless safe.

Rules:

* Twins/triplets must be represented as separate newborn records.
* Each newborn record has its own APGAR, weight, condition, outcome, and status.
* Stillbirth records should be supported as birth outcomes without forcing postnatal workflow.

9. Add optional newborn patient creation/linking

If the existing patient creation workflow is safely reusable:

* Add a permission-protected action to create a patient record from newborn record.
* Link it through `newborn_patient_id`.
* Use existing patient numbering rules.
* Copy only safe fields:

  * name placeholder or generated baby label based on project convention
  * sex
  * date/time of birth if patient model supports it
  * mother relationship if supported
* Do not invent fields that do not exist.
* Do not auto-create patient records by default.

If not safe:

* Add `newborn_patient_id` link field and a placeholder note.
* Document patient creation/linking as deferred.

Important:

* Do not create duplicate newborn patient records if already linked.
* Do not create patient records from stillbirth unless the project policy supports it.
* Do not change general patient creation workflow.

10. Add birth outcome workflow

Delivery record completion currently marks newborn records pending.

Improve birth outcome visibility:

* Delivery record should show newborn outcome completion state.
* Newborn records should update delivery-level newborn pending state.
* Delivery record can be considered newborn-complete when:

  * expected newborn count is matched, and
  * all newborn records have outcome/status recorded.
* This should be advisory; do not block existing delivery completion.

Add helper/status where safe:

* newborn_records_pending
* newborn_records_complete
* newborn_count_expected
* newborn_count_recorded
* newborn_outcome_summary

11. Add maternity dashboard newborn metrics

Update maternity dashboard with:

* Newborn records pending
* Newborns recorded today
* Live births today
* Stillbirths today
* Newborns under observation
* Newborns at risk
* Resuscitation required today
* Low birth weight count if easy
* Multiple births recorded

Keep queries safe.

12. Add postnatal preparation hooks only

Do not implement postnatal workflow.

Add placeholders/actions only:

* “Start postnatal care” placeholder on newborn detail and delivery record detail.
* If service structure is easy, prepare method signatures only, but do not implement postnatal tables yet.
* Document postnatal as Phase 12.

13. Add billing hooks only

Do not post billing.

Add warning/helper placeholders only for future mappings:

* newborn care
* neonatal observation
* resuscitation care
* newborn consumables
* newborn admission if needed later

Do not create invoices.
Do not post charges.
Do not recalculate invoices.
Do not add pharmacy dispensing.

14. Add permissions

Add permissions additively.

Suggested permissions:

* `maternity.newborn.view`
* `maternity.newborn.record`
* `maternity.newborn.update`
* `maternity.newborn.close`
* `maternity.newborn.link_patient`
* `maternity.newborn.create_patient`
* `maternity.newborn.risk.manage`
* `maternity.newborn.reports.view`
* `maternity.birth_outcome.view`
* `maternity.birth_outcome.manage`

Keep Phase 8/9/10 permissions.

Recommended:

* Admin/super admin gets all.
* Doctor/physician assistant can view/update newborn outcomes.
* Nurse/ward nurse/midwife can record newborn records and observations where role convention allows.
* Reception should not get clinical newborn permissions unless specifically appropriate for patient registration only.

Do not remove existing permissions.

15. Add localisation

Add EN/FR keys for all user-facing newborn text.

Include keys for:

* Newborn records
* Birth outcome
* Baby number
* Birth order
* Sex
* Birth time
* Birth weight
* Length
* Head circumference
* APGAR 1 minute
* APGAR 5 minutes
* APGAR 10 minutes
* Cried at birth
* Resuscitation required
* Resuscitation details
* Congenital concerns
* Feeding status
* Temperature
* Breathing status
* Cord status
* Colour
* Neonatal condition
* Newborn outcome
* Newborn status
* Transferred to
* Risk flags
* Danger signs
* Low birth weight
* Poor APGAR
* Newborn records pending
* Newborn records complete
* Create newborn record
* Bulk create newborn records
* Link newborn patient
* Create newborn patient
* Multiple birth
* Live birth
* Stillbirth
* Neonatal death
* Start postnatal care placeholder
* No newborn records yet
* Success/error/validation messages

Maintain EN/FR localisation parity.

16. Activity logging

Log newborn actions under the MATERNITY module:

* newborn record created
* newborn records bulk created
* newborn record updated
* newborn record status changed
* newborn record closed/cancelled
* newborn patient linked
* newborn patient created
* birth outcome completed/updated

Do not copy long clinical notes into activity metadata unless project convention allows it.
Prefer IDs, statuses, outcome, birth order, actor, and timestamps.

17. Tests

Do not run the full test suite.

Add targeted test file:

`tests/Feature/NewbornBirthOutcomePhase11Test.php`

Recommended tests:

* Newborn record can be created from delivery record by permitted user.
* Newborn record links to delivery, labor episode, pregnancy profile, mother patient, visit/admission where available.
* Multiple newborn records can be created for twins.
* Duplicate birth order is prevented for the same delivery.
* APGAR and birth weight fields are stored.
* Resuscitation required flag/details are stored.
* Danger signs and risk flags are stored.
* Newborn record can be updated.
* Newborn record status/outcome can be changed.
* Delivery record newborn pending flag updates when records are created.
* Delivery detail shows newborn records pending/complete state.
* Pregnancy profile shows newborn summary.
* Maternity dashboard shows newborn metrics.
* Optional newborn patient creation/linking works if implemented.
* Non-permitted user cannot create newborn record.
* Stillbirth outcome is supported without forcing postnatal workflow.
* Existing LaborDeliveryFoundationPhase10Test still passes.
* Existing AntenatalCarePhase9Test still passes.
* Existing MaternityFoundationPhase8Test still passes.
* Existing AdmissionWorkflowFoundationTest still passes.

Run targeted checks only:

* `php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php`
* `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
* `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
* `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
* `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
* `php artisan route:list --name=maternity`
* `php artisan route:list --name=admissions.requests`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check`
* PHP syntax checks on new/changed PHP files

Do not run the full suite.

18. Documentation

Create:

`docs/maternity/NEWBORN_BIRTH_OUTCOME_PHASE_11_REPORT.md`

The report must include:

* What was implemented
* Existing labor/delivery foundation audited
* Files changed
* New migrations/tables/columns
* New models/enums
* New services
* New routes/controllers
* New permissions
* New localisation keys
* Newborn record behavior
* Multiple birth behavior
* Birth outcome behavior
* Optional newborn patient linking behavior
* Dashboard behavior
* Postnatal preparation behavior
* Billing behavior, especially confirming no billing is posted
* Existing workflows protected
* Tests/checks run
* Known risks
* Intentionally deferred items
* Next recommended phase

19. Boundaries

Do not implement full postnatal records.
Do not implement mother/newborn postnatal observation workflow.
Do not implement maternity-specific discharge summaries.
Do not implement newborn billing/package billing.
Do not implement official birth registration/civil registry integration.
Do not auto-create newborn patient records by default.
Do not force newborn patient creation for stillbirths.
Do not rewrite delivery record completion.
Do not rewrite labor workflow.
Do not rewrite admission, nursing, MAR, discharge, billing, theatre, or emergency workflows.
Do not change existing admission billing.
Do not remove existing routes.
Do not alter default launch seeders for mass manual testing.
Do not run the full test suite.
Do not touch `docs/prompt.md`.

20. Final response

At the end, provide a concise completion report with:

* Summary of changes
* Files changed
* Migrations/routes/config added
* Permissions added
* Tests/checks run
* What was intentionally not changed
* Known risks
* Next phase recommendation

Recommended next phase after this:
Phase 12: Postnatal Care Workflow.
