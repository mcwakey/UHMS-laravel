You are working inside the UHMS Laravel project.

We have completed the Admission/Ward strengthening batch up to Phase 7:

* Phase 1: Emergency vs Admission gap analysis.
* Phase 2: Admission Workflow Foundation.
* Phase 4: Bed, Ward, Reservation, Transfer, and Location History Workflow.
* Phase 5: Ward Operations, Reservation Expiry, Cleaning Workflow, and Capacity Board.
* Phase 6: Nursing and Inpatient Care Layer.
* Phase 7: Discharge Readiness, Clearance, and Discharge Summary Workflow.

Current Admission/Ward foundation now includes:

* First-class `AdmissionRequest` lifecycle.
* Emergency-to-admission request creation/reuse.
* Admission request conversion through existing admission creation flow.
* Durable bed reservations.
* Admission location history.
* Bed transfer workflow.
* Reservation expiry command.
* Ward capacity board.
* Configurable bed release after discharge.
* Nursing notes.
* Nursing tasks.
* Admission care flags.
* Nursing handover/checklist.
* Discharge planning.
* Discharge clearances.
* Structured discharge summary.
* Advisory discharge readiness service.
* Optional discharge enforcement config disabled by default.

Now implement Phase 8: Maternity Foundation and Pregnancy Profile.

Goal:
Introduce Maternity as a first-class optional clinical workflow foundation, starting with pregnancy profiles and maternity case structure, while safely integrating with the existing Patient, Visit, Admission, Department, Billing, Ward, Nursing, and Dashboard foundations.

Important:
This phase is the maternity foundation only.
Do not implement the full Antenatal visit workflow yet.
Do not implement labor/delivery workflow yet.
Do not implement newborn records yet.
Do not implement postnatal workflow yet.
Do not implement maternity-specific discharge summaries yet.
Do not force maternity workflow on every female patient.
Do not break existing Admission/Ward/Emergency/Visit/Consultation/Billing workflows.
Do not run the full test suite.
Do not touch `docs/prompt.md`; it was already modified in the working tree and should remain untouched unless explicitly instructed.

Primary objectives:

1. Audit existing maternity-related surfaces first

Before coding, review current project surfaces for:

* Department type `maternity`
* Department dashboard routing for maternity
* Ward-style routing for maternity departments
* Existing seed data for maternity ward/department
* Pregnancy-related complaints or ICD references
* Emergency pregnancy/triage flags
* Blood bank screening references
* Admission request source compatibility for `maternity`
* Existing patient demographics fields
* Existing visit/admission relationships
* Existing billing service mapping patterns
* Existing permissions/localisation conventions
* Existing dashboard registry and department capability services

Document what already exists and avoid duplication.

2. Add pregnancy profile model and migration

Create a first-class pregnancy profile structure.

Recommended table: `pregnancy_profiles`

Recommended fields:

* id
* patient_id
* visit_id nullable
* admission_id nullable
* department_id nullable
* created_by nullable user
* updated_by nullable user if project convention supports it
* gravida nullable integer
* para nullable integer
* abortions nullable integer
* living_children nullable integer
* last_menstrual_period nullable date
* estimated_due_date nullable date
* gestational_age_weeks nullable integer
* gestational_age_days nullable integer
* blood_group nullable string
* rhesus_status nullable string
* known_risks nullable json or text
* allergies_snapshot nullable text/json if safe
* previous_caesarean boolean default false
* previous_postpartum_haemorrhage boolean default false
* hypertensive_disorder_risk boolean default false
* diabetes_risk boolean default false
* multiple_pregnancy boolean default false
* profile_status string
* closed_at nullable timestamp
* closed_by nullable user
* closure_reason nullable text
* timestamps
* soft deletes if project convention supports it

Recommended statuses:

* active
* high_risk
* delivered
* closed
* transferred

Important:

* Do not store sensitive fields such as HIV status unless the project already has a safe policy and access model.
* If HIV status or other sensitive screening is needed later, defer it or store through existing confidential clinical records.
* Pregnancy profile must be optional.
* Do not require pregnancy profile on general admission.
* Do not require pregnancy profile for all female patients.

3. Add maternity case model and migration

Create a lightweight maternity case foundation that can later connect ANC, labor, delivery, newborn, and postnatal.

Recommended table: `maternity_cases`

Recommended fields:

* id
* pregnancy_profile_id nullable
* patient_id
* visit_id nullable
* admission_id nullable
* department_id nullable
* source_type nullable string
* source_id nullable
* case_type nullable string
* status string
* priority nullable string
* risk_level nullable string
* opened_by nullable user
* opened_at nullable timestamp
* closed_by nullable user
* closed_at nullable timestamp
* reason nullable text
* clinical_summary nullable text
* timestamps
* soft deletes if project convention supports it

Recommended case types:

* pregnancy_profile
* antenatal
* maternity_admission
* labor_observation
* postnatal_observation
* emergency_referral

Recommended statuses:

* open
* under_observation
* admitted
* referred
* transferred
* closed
* cancelled

Important:

* This is a foundation only.
* Do not implement full labor/delivery/postnatal logic yet.
* Do not duplicate admission episodes. A maternity admission should link to the existing admission spine.

4. Add enums/constants

Follow project convention and add enums/constants where appropriate:

* `PregnancyProfileStatus`
* `MaternityCaseStatus`
* `MaternityCaseType`
* `MaternityRiskLevel`
* `MaternitySourceType` if not reusing existing source enum

Suggested risk levels:

* low
* moderate
* high
* emergency

Risk level should be advisory only in this phase.
Do not enforce hard clinical rules yet.

5. Add relationships

Add relationships safely.

Patient:

* hasMany pregnancy profiles
* hasMany maternity cases
* activePregnancyProfile helper if safe

Visit:

* hasMany pregnancy profiles
* hasMany maternity cases

Admission:

* hasMany pregnancy profiles or belongsTo active pregnancy profile if project style prefers
* hasMany maternity cases
* Do not require maternity data on admission.

Department:

* hasMany maternity cases if safe

PregnancyProfile:

* belongsTo patient
* belongsTo visit nullable
* belongsTo admission nullable
* hasMany maternity cases

MaternityCase:

* belongsTo pregnancy profile nullable
* belongsTo patient
* belongsTo visit nullable
* belongsTo admission nullable
* belongsTo department nullable

6. Add maternity services

Create services such as:

* `app/Services/Maternity/PregnancyProfileService.php`
* `app/Services/Maternity/MaternityCaseService.php`
* `app/Services/Maternity/MaternityOverviewService.php`

PregnancyProfileService should handle:

* Creating pregnancy profile
* Updating pregnancy profile
* Closing pregnancy profile
* Marking high risk
* Reopening only if safe and permitted
* Calculating gestational age from LMP where practical
* Calculating EDD from LMP where practical
* Logging profile creation/update/status changes

MaternityCaseService should handle:

* Opening maternity case
* Linking case to pregnancy profile
* Linking case to admission or visit
* Updating case priority/risk/status
* Closing/cancelling case
* Logging status changes

MaternityOverviewService should compose:

* active pregnancy profile
* risk summary
* latest maternity case
* admission link if any
* visit link if any
* profile warnings
* missing key data warnings
* maternity dashboard counts if useful

Do not put business logic directly in controllers or Blade.

7. Add routes/controllers

Create protected routes under an admin maternity namespace.

Suggested routes:

* `GET admin/maternity`
* `GET admin/maternity/pregnancies`
* `GET admin/maternity/pregnancies/create`
* `POST admin/maternity/pregnancies`
* `GET admin/maternity/pregnancies/{pregnancyProfile}`
* `GET admin/maternity/pregnancies/{pregnancyProfile}/edit`
* `PATCH admin/maternity/pregnancies/{pregnancyProfile}`
* `PATCH admin/maternity/pregnancies/{pregnancyProfile}/status`
* `POST admin/maternity/cases`
* `GET admin/maternity/cases/{maternityCase}`
* `PATCH admin/maternity/cases/{maternityCase}`

Add route names consistent with project convention.

Do not remove or alter existing admission, emergency, visit, consultation, ward, bed, nursing, discharge, medication, or billing routes.

8. Add basic maternity UI

Add safe, clear UI pages:

Maternity dashboard shell:

* Active pregnancy profiles
* High-risk profiles
* Open maternity cases
* Maternity admissions if linked
* Expected delivery this month if easy
* Recent cases
* Quick links

Pregnancy profile list:

* Patient
* Age/sex
* Gravida/para
* LMP
* EDD
* Gestational age
* Risk level/status
* Linked visit/admission
* Last updated
* Actions

Pregnancy profile create/edit:

* Patient search/select using existing patient lookup pattern if available
* Gravida
* Para
* Abortions
* Living children
* LMP
* EDD
* Gestational age
* Blood group
* Rhesus
* Risk flags
* Clinical notes/known risks
* Status

Pregnancy profile show:

* Patient banner
* Pregnancy summary
* Risk flags
* Linked visit/admission
* Related maternity cases
* Placeholder panels for future ANC, labor, delivery, newborn, and postnatal workflows
* Activity/timeline if existing audit data can be displayed safely
* Quick actions:

  * Open maternity case
  * Link to admission if already admitted
  * Create admission request if safe
  * Mark high risk
  * Close profile

Maternity case show:

* Patient banner
* Pregnancy profile summary
* Case type
* Case status
* Source
* Risk/priority
* Linked admission/visit
* Clinical summary
* Placeholder for future workflow-specific records

Keep UI simple and safe.
Do not build full ANC forms yet.

9. Add admission/maternity integration hooks

Admission request already supports source type `maternity`.

Add safe integration:

* From a pregnancy profile or maternity case, allow creating an admission request with source `maternity` if the user has permission.
* Admission request should carry:

  * patient
  * visit if available
  * source_type = maternity
  * source_id = maternity case or pregnancy profile ID depending on implementation
  * requested ward if maternity ward is selected
  * provisional diagnosis/clinical summary from maternity context where safe
* Do not auto-admit.
* Do not auto-bill.
* Do not force maternity admission into all pregnancies.
* Do not create admission request without user action unless a very explicit workflow exists.

If direct integration is too risky, add the service method and document UI hook as deferred.

10. Add department/dashboard integration

Maternity already exists as a department type and dashboard category.

Improve safely:

* Add maternity dashboard route/view if not present.
* Ensure maternity department users can see maternity dashboard if permitted.
* Add maternity cards to department dashboard registry only if consistent with existing architecture.
* Show counts from pregnancy profiles and maternity cases:

  * active pregnancy profiles
  * high-risk pregnancy profiles
  * open maternity cases
  * maternity-linked admissions
  * expected delivery this month

Do not redesign the whole department dashboard system.

11. Add billing/service mapping hooks only

Do not post maternity billing in this phase.

Add configuration placeholders or warning helpers only if safe:

Potential future service mappings:

* ANC registration/package
* ANC follow-up
* maternity admission fee
* normal delivery
* assisted delivery
* caesarean/theatre handoff
* postnatal package
* newborn care
* ultrasound
* maternity consumables

This phase may show “maternity billing mappings not configured” warnings only if the project already has a safe billing readiness pattern.

Do not create invoices.
Do not recalculate invoices.
Do not post charges from pregnancy profile or maternity case creation.

12. Add permissions

Add permissions additively.

Suggested permissions:

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

Recommended role assignment:

* Admin/super admin gets all.
* Doctor/physician assistant can view/create/update pregnancy profiles and cases.
* Nurse/ward nurse/midwife role, if present, can view/create/update maternity profiles and cases.
* Reception may view/create basic pregnancy profile only if project role conventions allow it.
* Finance should not get clinical maternity permissions unless already conventionally allowed.

Do not remove existing permissions.

13. Add localisation

Add EN/FR keys for all user-facing maternity text.

Include keys for:

* Maternity
* Pregnancy profile
* Maternity case
* Gravida
* Para
* Abortions
* Living children
* LMP
* EDD
* Gestational age
* Blood group
* Rhesus status
* Known risks
* Previous caesarean
* Previous postpartum haemorrhage
* Hypertensive disorder risk
* Diabetes risk
* Multiple pregnancy
* Risk level
* Profile status
* Case type
* Case status
* Open maternity case
* Close pregnancy profile
* Mark high risk
* Create maternity admission request
* Expected delivery this month
* Active pregnancies
* High-risk pregnancies
* Open maternity cases
* Future ANC workflow placeholder
* Future labor workflow placeholder
* Future delivery workflow placeholder
* Future newborn workflow placeholder
* Future postnatal workflow placeholder
* Success/error messages
* Validation messages

Maintain EN/FR localisation parity.

14. Activity logging

Log sensitive maternity foundation actions:

* pregnancy profile created
* pregnancy profile updated
* pregnancy profile marked high risk
* pregnancy profile closed
* maternity case opened
* maternity case updated
* maternity case closed/cancelled
* maternity admission request created

Do not copy long clinical notes into activity log metadata unless existing project convention allows it.
Prefer IDs, statuses, actor, timestamps, and risk level.

15. Tests

Do not run the full test suite.

Add targeted test file:

`tests/Feature/MaternityFoundationPhase8Test.php`

Recommended tests:

* Pregnancy profile can be created by permitted user.
* Pregnancy profile creation calculates EDD from LMP if implemented.
* Pregnancy profile creation calculates gestational age if implemented.
* Pregnancy profile can be updated.
* Pregnancy profile can be marked high risk.
* Pregnancy profile can be closed.
* Non-permitted user cannot create pregnancy profile.
* Maternity case can be opened from pregnancy profile.
* Maternity case can link to patient/visit/admission where available.
* Maternity case can be closed/cancelled.
* Pregnancy profile page renders placeholders for future ANC/labor/delivery/newborn/postnatal.
* Maternity dashboard renders.
* Maternity admission request can be created from maternity case if implemented.
* General admission still does not require pregnancy profile.
* Existing AdmissionDischargeReadinessPhase7Test still passes.
* Existing AdmissionNursingCarePhase6Test still passes.
* Existing AdmissionBedWorkflowPhase5Test still passes.
* Existing AdmissionWorkflowFoundationTest still passes.
* Existing WardAdmissionTest still passes.

Run targeted checks only:

* `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
* `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
* `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
* `php artisan test tests/Feature/AdmissionBedWorkflowPhase5Test.php`
* `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
* `php artisan test tests/Feature/WardAdmissionTest.php`
* `php artisan route:list --name=maternity`
* `php artisan route:list --name=admissions.requests`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check`
* PHP syntax checks on new/changed PHP files

Do not run the full suite.

16. Documentation

Create:

`docs/maternity/MATERNITY_FOUNDATION_PHASE_8_REPORT.md`

The report must include:

* What was implemented
* Existing maternity surfaces audited
* Files changed
* New migrations/tables/columns
* New models/enums
* New services
* New routes/controllers
* New permissions
* New localisation keys
* Pregnancy profile behavior
* Maternity case behavior
* Admission request integration behavior
* Dashboard behavior
* Billing behavior, especially confirming no billing is posted
* Existing workflows protected
* Tests/checks run
* Known risks
* Intentionally deferred items
* Next recommended phase

17. Boundaries

Do not implement ANC visit records yet.
Do not implement labor episodes yet.
Do not implement partograph observations yet.
Do not implement delivery records yet.
Do not implement newborn records yet.
Do not implement postnatal records yet.
Do not implement maternity-specific discharge summary sections yet.
Do not implement maternity package billing posting yet.
Do not implement ultrasound/lab ordering from ANC yet.
Do not force maternity workflows on all female patients.
Do not require pregnancy profile for general admission.
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
Phase 9: Antenatal Care Workflow.
