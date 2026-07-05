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
* Phase 11: Newborn Records and Birth Outcome Workflow.

Current maternity chain now supports:

Pregnancy Profile
→ ANC Visits
→ Labor Episode
→ Labor Observations
→ Delivery Record
→ Newborn Records
→ Optional newborn patient linking

Now implement Phase 12: Postnatal Care Workflow.

Goal:
Add postnatal care for both mother and newborn after delivery, linked to delivery records, newborn records, pregnancy profiles, labor episodes, maternity cases, admissions, visits, patients, nursing care, and discharge readiness.

This phase should support:

* Opening a postnatal case after delivery.
* Tracking mother postnatal observations.
* Tracking newborn postnatal observations.
* Mother/newborn danger signs.
* Breastfeeding and feeding status.
* Postnatal follow-up planning.
* Mother and newborn readiness for discharge.
* Referral/escalation notes.
* Dashboard visibility.
* Safe integration with existing admission discharge readiness without rewriting it.

Important:
Do not implement maternity package billing yet.
Do not implement newborn billing yet.
Do not implement official birth registration/civil registry integration.
Do not rewrite admission discharge readiness.
Do not rewrite newborn records.
Do not rewrite labor/delivery records.
Do not rewrite admission, nursing, MAR, billing, emergency, or theatre workflows.
Do not force postnatal care onto every delivery automatically unless the action is explicit and safe.
Do not run the full test suite.
Do not touch `docs/prompt.md`; it was already modified in the working tree and should remain untouched unless explicitly instructed.

1. Audit existing maternity/newborn foundation first

Before coding, review:

* `PregnancyProfile`
* `MaternityCase`
* `AntenatalVisit`
* `LaborEpisode`
* `DeliveryRecord`
* `NewbornRecord`
* Delivery newborn pending/completion behavior
* Newborn dashboard/profile summaries
* Admission discharge readiness services
* Nursing care services
* Admission workspace tabs
* Existing follow-up/appointment patterns
* Existing activity logging
* Existing permissions/localisation conventions

Document what exists and avoid duplicating it.

2. Add Postnatal Case model and migration

Create a first-class postnatal case.

Recommended table: `postnatal_cases`

Recommended fields:

* id
* delivery_record_id
* labor_episode_id nullable
* pregnancy_profile_id
* maternity_case_id nullable
* mother_patient_id
* visit_id nullable
* admission_id nullable
* department_id nullable
* opened_by nullable user
* opened_at nullable timestamp
* status string
* risk_level nullable string
* mother_status nullable string
* newborn_status nullable string
* breastfeeding_status nullable string
* discharge_readiness_status nullable string
* follow_up_date nullable date
* referral_required boolean default false
* referral_reason nullable text
* clinical_summary nullable text
* closed_by nullable user
* closed_at nullable timestamp
* closure_reason nullable text
* timestamps
* soft deletes if project convention supports it

Recommended statuses:

* open
* under_observation
* mother_ready
* newborn_ready
* ready_for_discharge
* referred
* transferred
* closed
* cancelled

3. Add Postnatal Mother Observation model

Recommended table: `postnatal_mother_observations`

Recommended fields:

* id
* postnatal_case_id
* delivery_record_id nullable
* pregnancy_profile_id
* mother_patient_id
* visit_id nullable
* admission_id nullable
* recorded_by nullable user
* observed_at timestamp
* blood_pressure_systolic nullable integer
* blood_pressure_diastolic nullable integer
* pulse nullable integer
* temperature nullable decimal
* respiratory_rate nullable integer
* bleeding_status nullable string
* uterus_condition nullable string
* pain_score nullable integer
* wound_condition nullable string
* breastfeeding_status nullable string
* mobility_status nullable string
* urination_status nullable string
* mental_wellbeing_note nullable text
* danger_signs nullable json
* risk_flags nullable json
* assessment nullable text
* plan nullable text
* counselling nullable text
* status string
* timestamps
* soft deletes if project convention supports it

Suggested statuses:

* recorded
* reviewed
* escalated
* cancelled

4. Add Postnatal Newborn Observation model

Recommended table: `postnatal_newborn_observations`

Recommended fields:

* id
* postnatal_case_id
* newborn_record_id
* delivery_record_id nullable
* mother_patient_id
* newborn_patient_id nullable
* visit_id nullable
* admission_id nullable
* recorded_by nullable user
* observed_at timestamp
* temperature nullable decimal
* weight_kg nullable decimal
* feeding_status nullable string
* breathing_status nullable string
* cord_status nullable string
* jaundice_status nullable string
* stooling_status nullable string
* urination_status nullable string
* activity_status nullable string
* danger_signs nullable json
* risk_flags nullable json
* immunisation_note nullable text
* assessment nullable text
* plan nullable text
* counselling nullable text
* status string
* timestamps
* soft deletes if project convention supports it

Suggested statuses:

* recorded
* reviewed
* escalated
* cancelled

5. Add enums/constants

Follow project enum conventions.

Suggested enums:

* `PostnatalCaseStatus`
* `PostnatalRiskLevel`
* `PostnatalMotherObservationStatus`
* `PostnatalNewbornObservationStatus`
* `PostnatalMotherDangerSign`
* `PostnatalNewbornDangerSign`
* `PostnatalMotherRiskFlag`
* `PostnatalNewbornRiskFlag`
* `BleedingStatus`
* `UterusCondition`
* `WoundCondition`
* `BreastfeedingStatus`
* `JaundiceStatus`

Suggested mother danger signs:

* heavy_bleeding
* severe_headache
* blurred_vision
* fever
* severe_abdominal_pain
* convulsions
* foul_smelling_discharge
* breathing_difficulty
* severe_weakness
* wound_infection_signs

Suggested newborn danger signs:

* difficulty_breathing
* fever
* hypothermia
* poor_feeding
* convulsions
* jaundice
* lethargy
* cord_infection
* cyanosis
* bleeding

Risk logic must remain advisory.

6. Add relationships

DeliveryRecord:

* hasOne or hasMany postnatal cases depending on project convention

PregnancyProfile:

* hasMany postnatal cases

MaternityCase:

* hasMany postnatal cases

NewbornRecord:

* hasMany postnatal newborn observations
* belongsTo postnatal case through observations where useful

PostnatalCase:

* belongsTo delivery record
* belongsTo pregnancy profile
* belongsTo maternity case nullable
* belongsTo labor episode nullable
* belongsTo mother patient
* belongsTo visit nullable
* belongsTo admission nullable
* belongsTo department nullable
* hasMany mother observations
* hasMany newborn observations
* hasMany newborn records through delivery record if useful

PostnatalMotherObservation:

* belongsTo postnatal case
* belongsTo mother patient
* belongsTo delivery record nullable
* belongsTo admission nullable
* belongsTo recordedBy user

PostnatalNewbornObservation:

* belongsTo postnatal case
* belongsTo newborn record
* belongsTo mother patient
* belongsTo newborn patient nullable
* belongsTo admission nullable
* belongsTo recordedBy user

7. Add postnatal services

Create services such as:

* `app/Services/Maternity/PostnatalCaseService.php`
* `app/Services/Maternity/PostnatalMotherObservationService.php`
* `app/Services/Maternity/PostnatalNewbornObservationService.php`
* `app/Services/Maternity/PostnatalRiskAssessmentService.php`
* `app/Services/Maternity/PostnatalOverviewService.php`

PostnatalCaseService should handle:

* Opening postnatal case from delivery record
* Linking delivery, labor, pregnancy, maternity case, admission, visit, mother patient, and department
* Updating postnatal status
* Marking mother ready
* Marking newborn ready
* Marking ready for discharge
* Marking referral required
* Closing/cancelling case
* Logging actions

MotherObservationService should handle:

* Recording mother observation
* Updating/cancelling observation
* Updating postnatal case risk/status where safe
* Logging actions

NewbornObservationService should handle:

* Recording newborn observation
* Updating/cancelling observation
* Updating newborn/postnatal risk where safe
* Logging actions

PostnatalRiskAssessmentService should:

* Review mother and newborn danger signs/risk flags
* Produce advisory warnings
* Suggest referral/escalation visibility
* Avoid hard blocking

PostnatalOverviewService should compose:

* active postnatal cases
* mother readiness
* newborn readiness
* latest mother observation
* latest newborn observations
* danger signs summary
* discharge readiness summary
* follow-up status
* dashboard metrics

8. Add routes/controllers

Add protected routes under maternity namespace.

Suggested routes:

Postnatal cases:

* `GET admin/maternity/postnatal`
* `POST admin/maternity/deliveries/{deliveryRecord}/postnatal`
* `GET admin/maternity/postnatal/{postnatalCase}`
* `PATCH admin/maternity/postnatal/{postnatalCase}`
* `PATCH admin/maternity/postnatal/{postnatalCase}/status`
* `PATCH admin/maternity/postnatal/{postnatalCase}/close`
* `PATCH admin/maternity/postnatal/{postnatalCase}/cancel`

Mother observations:

* `GET admin/maternity/postnatal/{postnatalCase}/mother-observations/create`
* `POST admin/maternity/postnatal/{postnatalCase}/mother-observations`
* `GET admin/maternity/postnatal/mother-observations/{observation}`
* `GET admin/maternity/postnatal/mother-observations/{observation}/edit`
* `PATCH admin/maternity/postnatal/mother-observations/{observation}`
* `PATCH admin/maternity/postnatal/mother-observations/{observation}/cancel`

Newborn observations:

* `GET admin/maternity/postnatal/{postnatalCase}/newborns/{newbornRecord}/observations/create`
* `POST admin/maternity/postnatal/{postnatalCase}/newborns/{newbornRecord}/observations`
* `GET admin/maternity/postnatal/newborn-observations/{observation}`
* `GET admin/maternity/postnatal/newborn-observations/{observation}/edit`
* `PATCH admin/maternity/postnatal/newborn-observations/{observation}`
* `PATCH admin/maternity/postnatal/newborn-observations/{observation}/cancel`

Use route names consistent with project convention.

Do not remove Phase 8/9/10/11 maternity routes.

9. Add postnatal UI

Add Postnatal workflow pages.

Postnatal list/dashboard page:

* Active postnatal cases
* Mother patient
* Delivery record
* Newborn count
* Mother status
* Newborn status
* Risk level
* Follow-up date
* Referral required
* Latest observation time
* Actions

Postnatal case detail page:

* Mother/patient banner
* Delivery summary
* Labor summary
* Newborn summary
* Mother observation summary
* Newborn observation summary
* Risk/danger warnings
* Breastfeeding/feeding summary
* Discharge readiness card
* Follow-up/referral card
* Quick action: record mother observation
* Quick action: record newborn observation
* Quick action: mark mother ready
* Quick action: mark newborn ready
* Quick action: mark ready for discharge
* Quick action: close case
* Placeholder for future maternity-specific discharge summary

Mother observation form:

* Observed at
* BP
* Pulse
* Temperature
* Respiratory rate
* Bleeding status
* Uterus condition
* Pain score
* Wound condition
* Breastfeeding status
* Mobility
* Urination
* Mental wellbeing note
* Danger signs checklist
* Risk flags checklist
* Assessment
* Plan
* Counselling

Newborn observation form:

* Observed at
* Newborn record selector/context
* Temperature
* Weight
* Feeding status
* Breathing status
* Cord status
* Jaundice status
* Stooling
* Urination
* Activity
* Danger signs checklist
* Risk flags checklist
* Immunisation note
* Assessment
* Plan
* Counselling

Delivery record detail page should show:

* Start/open postnatal care action
* Existing postnatal case link
* Postnatal status summary

Newborn detail page should show:

* Latest postnatal observation
* Postnatal case link
* Postnatal readiness placeholder/status

Pregnancy profile page should show:

* Postnatal case summary after delivery
* Latest mother/newborn observations

10. Integrate with admission discharge readiness safely

Do not rewrite existing discharge readiness.

Add safe advisory hooks:

* Admission discharge readiness may show postnatal case status if the admission has linked postnatal care.
* If mother/newborn not ready, display warning.
* Do not block admission discharge unless existing enforcement config is explicitly extended and disabled by default.

Suggested config:

```php
'discharge' => [
    'require_postnatal_ready_before_discharge' => env('ADMISSION_REQUIRE_POSTNATAL_READY_BEFORE_DISCHARGE', false),
],
```

Default must be false.

If too risky, add only a visible warning and document enforcement as deferred.

11. Add maternity dashboard postnatal metrics

Update maternity dashboard with:

* Active postnatal cases
* Mother observations today
* Newborn observations today
* Mothers ready for discharge
* Newborns ready for discharge
* Postnatal danger signs flagged
* Referrals required
* Follow-ups due this week
* Recent postnatal cases

Keep queries safe.

12. Add referral/follow-up behavior

Add lightweight fields/actions:

* Mark referral required
* Referral reason
* Follow-up date
* Follow-up instructions if simple

If appointment module integration is safe:

* Add link/placeholder to create appointment.
* Do not build a new appointment module.

If not safe:

* Store follow-up date and instructions on postnatal case.
* Document appointment integration as deferred.

13. Add billing hooks only

Do not post billing.

Add placeholders/warnings only for future mappings:

* postnatal mother care
* postnatal newborn care
* neonatal observation
* immunisation
* newborn consumables

Do not create invoices.
Do not post charges.
Do not recalculate invoices.
Do not add pharmacy dispensing.

14. Add permissions

Add permissions additively.

Suggested permissions:

* `maternity.postnatal.view`
* `maternity.postnatal.open`
* `maternity.postnatal.update`
* `maternity.postnatal.close`
* `maternity.postnatal.cancel`
* `maternity.postnatal.mother.record`
* `maternity.postnatal.mother.update`
* `maternity.postnatal.newborn.record`
* `maternity.postnatal.newborn.update`
* `maternity.postnatal.risk.manage`
* `maternity.postnatal.discharge.manage`
* `maternity.postnatal.referral.manage`
* `maternity.postnatal.reports.view`

Keep all Phase 8/9/10/11 permissions.

Recommended:

* Admin/super admin gets all.
* Doctor/physician assistant can view/update postnatal cases and manage referrals/readiness.
* Nurse/ward nurse/midwife can record observations and update readiness where role convention allows.
* Reception should not get clinical postnatal permissions unless explicitly appropriate.

Do not remove existing permissions.

15. Add localisation

Add EN/FR keys for all user-facing postnatal text.

Include keys for:

* Postnatal care
* Postnatal case
* Open postnatal care
* Mother observation
* Newborn observation
* Mother ready
* Newborn ready
* Ready for discharge
* Follow-up date
* Referral required
* Referral reason
* Bleeding status
* Uterus condition
* Wound condition
* Breastfeeding status
* Jaundice status
* Feeding status
* Cord status
* Mother danger signs
* Newborn danger signs
* Mother risk flags
* Newborn risk flags
* Record mother observation
* Record newborn observation
* No postnatal case yet
* No mother observations yet
* No newborn observations yet
* Postnatal danger signs flagged
* Follow-ups due
* Success/error/validation messages

Maintain EN/FR localisation parity.

16. Activity logging

Log postnatal actions under MATERNITY:

* postnatal case opened
* postnatal case updated
* postnatal case closed/cancelled
* mother observation recorded
* mother observation updated/cancelled
* newborn observation recorded
* newborn observation updated/cancelled
* mother marked ready
* newborn marked ready
* postnatal ready for discharge
* referral required/updated
* follow-up updated

Do not copy long clinical notes into activity metadata unless project convention allows it.
Prefer IDs, statuses, risk counts, actor, timestamps.

17. Tests

Do not run the full test suite.

Add targeted test file:

`tests/Feature/PostnatalCarePhase12Test.php`

Recommended tests:

* Postnatal case can be opened from delivery record by permitted user.
* Postnatal case links delivery, pregnancy profile, mother patient, visit/admission where available.
* Postnatal case lists newborn records from delivery.
* Mother observation can be recorded.
* Mother observation stores vitals and danger signs.
* Newborn observation can be recorded for newborn record.
* Newborn observation stores temperature, weight, feeding, jaundice, cord, and danger signs.
* Postnatal case can mark mother ready.
* Postnatal case can mark newborn ready.
* Postnatal case can mark ready for discharge.
* Referral required and follow-up date can be recorded.
* Postnatal case appears on delivery detail page.
* Newborn detail page shows postnatal observation summary.
* Pregnancy profile page shows postnatal summary.
* Maternity dashboard shows postnatal metrics.
* Admission discharge readiness shows postnatal warning if implemented.
* Non-permitted user cannot open postnatal case.
* Stillbirth newborn records do not require newborn postnatal observation.
* Existing NewbornBirthOutcomePhase11Test still passes.
* Existing LaborDeliveryFoundationPhase10Test still passes.
* Existing AntenatalCarePhase9Test still passes.
* Existing MaternityFoundationPhase8Test still passes.
* Existing AdmissionDischargeReadinessPhase7Test still passes.

Run targeted checks only:

* `php artisan test tests/Feature/PostnatalCarePhase12Test.php`
* `php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php`
* `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
* `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
* `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
* `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
* `php artisan route:list --name=maternity`
* `php artisan route:list --name=admissions`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check`
* PHP syntax checks on new/changed PHP files

Do not run the full suite.

18. Documentation

Create:

`docs/maternity/POSTNATAL_CARE_PHASE_12_REPORT.md`

The report must include:

* What was implemented
* Existing newborn/delivery foundation audited
* Files changed
* New migrations/tables/columns
* New models/enums
* New services
* New routes/controllers
* New permissions
* New localisation keys
* Postnatal case behavior
* Mother observation behavior
* Newborn observation behavior
* Mother/newborn discharge readiness behavior
* Referral/follow-up behavior
* Admission discharge readiness integration behavior
* Dashboard behavior
* Billing behavior, especially confirming no billing is posted
* Existing workflows protected
* Tests/checks run
* Known risks
* Intentionally deferred items
* Next recommended phase

19. Boundaries

Do not implement maternity package billing.
Do not implement newborn billing.
Do not implement pharmacy dispensing.
Do not implement official birth registration/civil registry integration.
Do not implement maternity-specific discharge summary sections unless very small and safe; prefer deferring.
Do not rewrite admission discharge readiness.
Do not force postnatal readiness to block discharge unless config is explicitly added and default false.
Do not rewrite newborn records.
Do not rewrite labor/delivery records.
Do not rewrite admission, nursing, MAR, billing, theatre, or emergency workflows.
Do not alter default launch seeders for mass manual testing.
Do not remove existing routes.
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
Phase 13: Maternity Reports, Billing Mapping Readiness, Manual Test Data, and Final Wide Regression.
