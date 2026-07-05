You are working inside the UHMS Laravel project.

We have completed:

* Phase 1: Emergency vs Admission gap analysis.
* Phase 2: Admission Workflow Foundation.
* Phase 4: Bed, Ward, Reservation, Transfer, and Location History Workflow.
* Phase 5: Ward Operations, Reservation Expiry, Cleaning Workflow, and Capacity Board.
* Phase 6: Nursing and Inpatient Care Layer.
* Phase 7: Discharge Readiness, Clearance, and Discharge Summary Workflow.
* Phase 8: Maternity Foundation and Pregnancy Profile.

Current maternity foundation now includes:

* `pregnancy_profiles`
* `maternity_cases`
* `PregnancyProfile`
* `MaternityCase`
* Pregnancy profile CRUD
* Maternity dashboard shell
* Maternity case detail page
* Manual maternity admission request hook using `source_type=maternity`
* Maternity permissions
* Sidebar entry
* Activity logging under `MATERNITY`
* EN/FR maternity translations
* Optional pregnancy workflow that does not affect general admissions

Now implement Phase 9: Antenatal Care Workflow.

Goal:
Build the first real maternity sub-workflow: Antenatal Care, linked to pregnancy profiles and maternity cases, without implementing labor, delivery, newborn, or postnatal workflows yet.

This phase should make UHMS able to register and follow ANC visits, track pregnancy progress, identify risk/danger signs, schedule next ANC visits, and prepare safe referrals to admission, emergency, or later labor workflow.

Important:
Do not implement labor episodes in this phase.
Do not implement partograph observations in this phase.
Do not implement delivery records in this phase.
Do not implement newborn records in this phase.
Do not implement postnatal care in this phase.
Do not implement maternity package billing posting yet.
Do not force ANC onto all pregnancy profiles.
Do not force pregnancy profiles onto general admissions.
Do not break existing Admission/Ward/Emergency/Visit/Consultation/Billing workflows.
Do not run the full test suite.
Do not touch `docs/prompt.md`; it was already modified in the working tree and should remain untouched unless explicitly instructed.

Primary objectives:

1. Audit current Phase 8 maternity foundation first

Before coding, review the current implementation for:

* `PregnancyProfile`
* `MaternityCase`
* Pregnancy profile service
* Maternity case service
* Maternity overview service
* Maternity dashboard
* Pregnancy profile show page
* Maternity case show page
* Maternity permissions
* Maternity translations
* Maternity admission request hook
* Existing patient/visit/admission relationships
* Existing investigation request patterns
* Existing appointment/follow-up patterns
* Existing activity logging patterns
* Existing dashboard/service card patterns

Document what already exists and avoid duplication.

2. Add Antenatal Visit model and migration

Create a first-class antenatal visit record linked to pregnancy profile.

Recommended table: `antenatal_visits`

Recommended fields:

* id
* pregnancy_profile_id
* maternity_case_id nullable
* patient_id
* visit_id nullable
* admission_id nullable
* department_id nullable
* recorded_by nullable user
* visit_number nullable integer
* visit_date date or datetime
* gestational_age_weeks nullable integer
* gestational_age_days nullable integer
* weight_kg nullable decimal
* blood_pressure_systolic nullable integer
* blood_pressure_diastolic nullable integer
* pulse nullable integer
* temperature nullable decimal
* respiratory_rate nullable integer
* fundal_height_cm nullable decimal
* fetal_heart_rate nullable integer
* fetal_movement nullable string
* presentation nullable string
* urine_protein nullable string
* urine_glucose nullable string
* oedema nullable string
* haemoglobin nullable decimal
* danger_signs nullable json
* risk_flags nullable json
* assessment nullable text
* plan nullable text
* counselling nullable text
* supplements nullable json or text
* immunisations nullable json or text
* next_visit_date nullable date
* referral_type nullable string
* referral_reason nullable text
* status string
* created_by nullable user
* updated_by nullable user if project convention supports it
* timestamps
* soft deletes if project convention supports it

Recommended statuses:

* recorded
* follow_up_scheduled
* referred
* high_risk
* closed
* cancelled

Important:

* Keep fields nullable where operationally safe.
* Do not require every measurement for every ANC visit.
* Do not store highly sensitive screening results unless the project already has an access policy for them.
* If sensitive screening is required later, defer it to a confidential clinical record pattern.

3. Add ANC enums/constants

Follow project enum conventions.

Add enums/constants such as:

* `AntenatalVisitStatus`
* `AntenatalReferralType`
* `AntenatalDangerSign`
* `AntenatalRiskFlag`
* `FetalPresentation`
* `UrineProteinResult`
* `UrineGlucoseResult`

Suggested referral types:

* none
* consultation
* emergency
* admission
* maternity_admission
* external_referral

Suggested danger signs:

* severe_headache
* blurred_vision
* vaginal_bleeding
* severe_abdominal_pain
* reduced_fetal_movement
* convulsions
* fever
* swollen_face_hands
* leaking_liquor
* breathlessness
* severe_vomiting

Suggested risk flags:

* high_blood_pressure
* low_haemoglobin
* previous_caesarean
* previous_postpartum_haemorrhage
* multiple_pregnancy
* diabetes_risk
* hypertensive_disorder_risk
* young_mother
* advanced_maternal_age
* grand_multiparity
* rhesus_negative
* breech_or_abnormal_presentation

Risk flags should be advisory in this phase.
Do not enforce hard clinical rules yet.

4. Add relationships

PregnancyProfile:

* hasMany antenatal visits
* latestAntenatalVisit helper if useful
* nextAntenatalVisit helper if useful

MaternityCase:

* hasMany antenatal visits where applicable

Patient:

* hasMany antenatal visits

Visit:

* hasMany antenatal visits if safe

Admission:

* hasMany antenatal visits if safe

AntenatalVisit:

* belongsTo pregnancy profile
* belongsTo maternity case nullable
* belongsTo patient
* belongsTo visit nullable
* belongsTo admission nullable
* belongsTo department nullable
* belongsTo recordedBy user

5. Add ANC service layer

Create services such as:

* `app/Services/Maternity/AntenatalVisitService.php`
* `app/Services/Maternity/AntenatalRiskAssessmentService.php`
* `app/Services/Maternity/AntenatalOverviewService.php`

AntenatalVisitService should handle:

* Creating ANC visit
* Updating ANC visit
* Cancelling/closing ANC visit if needed
* Scheduling next visit
* Linking visit to pregnancy profile
* Updating pregnancy profile gestational age/EDD only when safe
* Creating or linking maternity case if needed
* Logging ANC visit actions

AntenatalRiskAssessmentService should:

* Review vitals and danger signs
* Suggest risk level
* Suggest danger warnings
* Suggest referral/admission warning where appropriate
* Mark the pregnancy profile high-risk only through explicit action or safe service rule
* Avoid automatic hard clinical decisions

AntenatalOverviewService should compose:

* ANC visit count
* latest ANC visit
* next ANC date
* missed ANC indicator
* high-risk indicator
* danger sign summary
* pending referral state
* profile completeness warnings
* dashboard counts

Do not put risk logic directly into Blade.

6. Add ANC routes/controllers

Add protected routes under maternity namespace.

Suggested routes:

* `GET admin/maternity/pregnancies/{pregnancyProfile}/antenatal`
* `GET admin/maternity/pregnancies/{pregnancyProfile}/antenatal/create`
* `POST admin/maternity/pregnancies/{pregnancyProfile}/antenatal`
* `GET admin/maternity/antenatal/{antenatalVisit}`
* `GET admin/maternity/antenatal/{antenatalVisit}/edit`
* `PATCH admin/maternity/antenatal/{antenatalVisit}`
* `PATCH admin/maternity/antenatal/{antenatalVisit}/cancel`
* `POST admin/maternity/antenatal/{antenatalVisit}/referral`
* `POST admin/maternity/antenatal/{antenatalVisit}/admission-request` if safe

Keep route names consistent with project convention.

Do not remove Phase 8 routes.
Do not alter admission, emergency, ward, nursing, discharge, billing, or medication routes.

7. Add ANC UI

Add ANC functionality into the maternity area.

Pregnancy profile show page should gain an ANC panel/tab:

* ANC visit count
* latest ANC visit date
* next scheduled ANC visit
* missed visit warning
* latest blood pressure
* latest fetal heart rate
* latest fundal height
* latest risk level
* danger sign warnings
* quick action: record ANC visit
* quick action: view ANC history
* quick action: refer/admission request if permitted

ANC visit list page:

* Visit number
* Visit date
* Gestational age
* BP
* Weight
* Fundal height
* Fetal heart rate
* Danger signs
* Risk flags
* Next visit date
* Status
* Recorded by
* Actions

ANC create/edit form:

* Visit date
* Gestational age
* Weight
* BP
* Pulse
* Temperature
* Fundal height
* Fetal heart rate
* Fetal movement
* Presentation
* Urine protein
* Urine glucose
* Oedema
* Haemoglobin
* Danger signs checklist
* Risk flags checklist
* Assessment
* Plan
* Counselling
* Supplements
* Immunisations
* Next visit date
* Referral type/reason

ANC detail page:

* Patient banner
* Pregnancy summary
* ANC observations
* Danger signs
* Risk assessment
* Referral/admission request panel
* Next visit plan
* Related maternity case
* Activity/timeline if safe
* Placeholder for future lab/ultrasound results if integration is deferred

Keep UI simple and clear.
Do not build full partograph or delivery UI here.

8. Add ANC dashboard improvements

Improve maternity dashboard with ANC metrics:

* ANC visits today
* ANC visits this week
* High-risk pregnancies
* Missed ANC visits
* Expected deliveries this month
* Danger signs flagged
* Referrals pending
* Profiles without ANC visits
* Recent ANC visits

Keep queries safe.
Use overview services where possible.

9. Add ANC referral/admission request hook

Add safe referral behavior.

From ANC visit, user should be able to:

* Mark referral type and reason.
* Create maternity admission request if referral type is `admission` or `maternity_admission`.
* Create emergency-source handoff only if the existing emergency workflow supports safe creation; otherwise document as deferred.
* Link the admission request to the maternity case or ANC visit using source fields where safe.

Rules:

* Do not auto-admit.
* Do not auto-bill.
* Do not create emergency case automatically unless existing emergency service supports it safely.
* Do not force every danger sign into admission.
* User action should be required for admission request creation.
* Existing admission request lifecycle must remain unchanged.

10. Add investigation/ultrasound hooks only if safe

ANC often needs lab and ultrasound requests.

In this phase:

* If the project has a safe, reusable investigation request pattern, add a simple “Request investigation/ultrasound” link or hook from ANC detail.
* If not safe, show a placeholder panel and document it for a future phase.

Do not build a new lab/radiology workflow.
Do not post billing.
Do not create service charges from ANC in this phase unless existing request flow already does that safely.

11. Add supplements/immunisation tracking

Implement lightweight tracking inside ANC visit fields or JSON.

Suggested supplements:

* iron_folate
* calcium
* multivitamin
* other

Suggested immunisations:

* tetanus_diphtheria_1
* tetanus_diphtheria_2
* malaria_prevention if locally appropriate and already supported by clinical policy
* other

Keep it simple.
Do not build a full immunisation module.
Do not build pharmacy dispensing from supplements yet.

12. Add permissions

Add permissions additively.

Suggested permissions:

* `maternity.anc.view`
* `maternity.anc.record`
* `maternity.anc.update`
* `maternity.anc.cancel`
* `maternity.anc.risk.manage`
* `maternity.anc.referral.create`
* `maternity.anc.admission.request`
* `maternity.anc.reports.view`

Keep Phase 8 permissions:

* `maternity.view`
* `maternity.dashboard.view`
* `maternity.pregnancy.view`
* `maternity.pregnancy.create`
* `maternity.pregnancy.update`
* `maternity.pregnancy.close`
* `maternity.pregnancy.risk.manage`
* `maternity.case.view`
* `maternity.case.create`
* `maternity.case.update`
* `maternity.case.close`
* `maternity.admission.request`
* `maternity.reports.view`
* `maternity.settings.manage`

Recommended:

* Admin/super admin gets all.
* Doctor/physician assistant can view/create/update ANC.
* Nurse/ward nurse/midwife can view/create/update ANC.
* Reception should not get detailed ANC permissions unless project role convention allows it.

Do not remove existing permissions.

13. Add localisation

Add EN/FR keys for all user-facing ANC text.

Include keys for:

* Antenatal care
* ANC visit
* Record ANC visit
* ANC history
* Visit number
* Gestational age
* Weight
* Blood pressure
* Pulse
* Temperature
* Fundal height
* Fetal heart rate
* Fetal movement
* Presentation
* Urine protein
* Urine glucose
* Oedema
* Haemoglobin
* Danger signs
* Risk flags
* Assessment
* Plan
* Counselling
* Supplements
* Immunisations
* Next visit date
* Missed ANC visit
* High-risk ANC
* Referral type
* Referral reason
* Create admission request
* Investigation/ultrasound placeholder
* No ANC visits yet
* ANC visits today
* ANC visits this week
* Profiles without ANC
* Danger signs flagged
* Success/error/validation messages

Maintain EN/FR localisation parity.

14. Activity logging

Log ANC actions under the MATERNITY module:

* ANC visit recorded
* ANC visit updated
* ANC visit cancelled
* ANC referral recorded
* ANC admission request created
* Pregnancy profile risk level updated from ANC if implemented

Do not copy long clinical notes into activity metadata unless project convention allows it.
Prefer IDs, statuses, risk flags count, danger signs count, actor, and timestamps.

15. Tests

Do not run the full test suite.

Add targeted test file:

`tests/Feature/AntenatalCarePhase9Test.php`

Recommended tests:

* ANC visit can be recorded for pregnancy profile by permitted user.
* ANC visit links to pregnancy profile, patient, visit/admission where available.
* ANC visit can be updated.
* ANC visit calculates or stores gestational age safely.
* Danger signs can be stored.
* Risk flags can be stored.
* High-risk warning appears when risk flags/danger signs exist.
* Next ANC visit date can be scheduled.
* ANC visit appears on pregnancy profile show page.
* ANC visit list renders.
* ANC detail page renders.
* Non-permitted user cannot record ANC visit.
* ANC referral can be recorded.
* ANC admission request can be created if implemented.
* ANC dashboard metrics render.
* General pregnancy profile creation still works.
* General admission still does not require pregnancy profile or ANC visit.
* Existing MaternityFoundationPhase8Test still passes.
* Existing AdmissionDischargeReadinessPhase7Test still passes.
* Existing AdmissionNursingCarePhase6Test still passes.
* Existing AdmissionWorkflowFoundationTest still passes.

Run targeted checks only:

* `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
* `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
* `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
* `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
* `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
* `php artisan route:list --name=maternity`
* `php artisan route:list --name=admissions.requests`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check`
* PHP syntax checks on new/changed PHP files

Do not run the full suite.

16. Documentation

Create:

`docs/maternity/ANTENATAL_CARE_PHASE_9_REPORT.md`

The report must include:

* What was implemented
* Existing maternity foundation audited
* Files changed
* New migrations/tables/columns
* New models/enums
* New services
* New routes/controllers
* New permissions
* New localisation keys
* ANC visit behavior
* ANC dashboard behavior
* Risk/danger sign behavior
* Referral/admission request behavior
* Investigation/ultrasound hook behavior or deferral
* Supplements/immunisation behavior
* Billing behavior, especially confirming no billing is posted
* Existing workflows protected
* Tests/checks run
* Known risks
* Intentionally deferred items
* Next recommended phase

17. Boundaries

Do not implement labor episodes.
Do not implement partograph observations.
Do not implement delivery records.
Do not implement newborn records.
Do not implement postnatal records.
Do not implement maternity-specific discharge summaries.
Do not implement maternity package billing posting.
Do not build a new investigation/radiology workflow.
Do not build a full immunisation module.
Do not build pharmacy dispensing from supplements.
Do not force ANC onto every pregnancy profile.
Do not require pregnancy profile or ANC for general admission.
Do not change existing admission billing.
Do not change existing discharge readiness behavior.
Do not change existing nursing/MAR behavior.
Do not break Emergency-to-Admission compatibility.
Do not remove existing routes.
Do not alter default launch seeders for mass manual testing.
Do not run the full test suite.
Do not touch `docs/prompt.md`.

18. Final response

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
Phase 10: Labor and Delivery Foundation.
