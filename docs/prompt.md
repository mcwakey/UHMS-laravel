You are working inside the UHMS Laravel project.

We have completed the Admission/Ward/Maternity workflow strengthening batch up to Phase 12:

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
* Phase 12: Postnatal Care Workflow.

Current clinical chain now supports:

Pregnancy Profile
→ ANC Visits
→ Labor Episode
→ Labor Observations
→ Delivery Record
→ Newborn Records
→ Postnatal Care

Now implement Phase 13: Maternity Reports, Billing Mapping Readiness, Manual Test Data, and Final Wide Regression.

Goal:
Close this implementation batch by adding reporting, operational visibility, safe billing-mapping readiness, rich manual test data, final documentation, and one wider regression pass.

Important:
This phase may run the wider regression/full-suite checks at the end because this is the batch-closing phase.
Do not post real billing charges unless explicitly safe and already supported by existing billing services.
Do not create invoices from maternity workflows unless this phase explicitly implements mapping readiness only.
Do not rewrite existing billing, stock, pharmacy, emergency, theatre, admission, discharge, MAR, or nursing workflows.
Do not touch `docs/prompt.md`; it was already dirty before this phase and should remain untouched unless explicitly instructed.

1. Audit current completed maternity workflow first

Before implementation, review:

* Pregnancy profiles
* Maternity cases
* ANC visits
* Labor episodes
* Labor observations
* Delivery records
* Newborn records
* Postnatal cases
* Mother observations
* Newborn observations
* Admission request integration
* Admission discharge readiness integration
* Maternity dashboard
* Existing report patterns
* Existing export patterns
* Existing billing mapping/configuration patterns
* Existing manual test seeder/fixture patterns
* Existing role/permission seeders
* Existing localisation conventions

Document what exists and avoid duplication.

2. Add maternity report service layer

Create services such as:

* `app/Services/Maternity/MaternityReportService.php`
* `app/Services/Maternity/MaternityReportExportService.php`
* `app/Services/Maternity/MaternityBillingReadinessService.php`
* `app/Services/Maternity/MaternityManualTestDataService.php`

Reports should use service/query classes, not heavy Blade logic.

3. Add maternity reports dashboard

Add a report area under maternity.

Suggested route group:

* `GET admin/maternity/reports`
* `GET admin/maternity/reports/antenatal`
* `GET admin/maternity/reports/labor`
* `GET admin/maternity/reports/deliveries`
* `GET admin/maternity/reports/newborns`
* `GET admin/maternity/reports/postnatal`
* `GET admin/maternity/reports/risk`
* `GET admin/maternity/reports/export`

Reports should support filters where practical:

* date range
* department
* staff/recorded by
* risk level
* status
* outcome
* referral state
* admission-linked vs outpatient
* delivery mode
* newborn outcome
* postnatal readiness

Keep queries safe and paginate where needed.

4. Add antenatal reports

ANC report should show:

* ANC visits by period
* New pregnancy profiles
* Profiles without ANC
* Missed ANC visits
* High-risk pregnancies
* Danger signs flagged
* Referral counts
* Maternity admission requests from ANC
* Expected delivery due this month/week
* Recent ANC visits

5. Add labor reports

Labor report should show:

* Active labor episodes
* Labor episodes by stage
* Labor observations count
* Danger signs/risk flags
* Theatre escalation required
* Emergency escalation required
* Labor admission requests
* Delivered vs transferred/referred/cancelled
* Recent observations

6. Add delivery reports

Delivery report should show:

* Deliveries by period
* Delivery mode breakdown
* Delivery outcome breakdown
* Estimated blood loss warnings if available
* Maternal condition summary
* Newborn records pending
* Deliveries missing newborn records
* Caesarean/theatre handoff placeholders
* Recent delivery records

7. Add newborn reports

Newborn report should show:

* Newborns recorded by period
* Live births
* Stillbirths
* Neonatal deaths if recorded
* Multiple births
* Low birth weight count
* Resuscitation required count
* Poor APGAR advisory count
* Newborns under observation
* Newborn records without linked patient
* Newborn outcome summary

8. Add postnatal reports

Postnatal report should show:

* Active postnatal cases
* Mother observations today/by period
* Newborn observations today/by period
* Mother ready for discharge
* Newborn ready for discharge
* Ready for discharge
* Referrals required
* Follow-ups due this week
* Danger signs flagged
* Cases without recent observations
* Recent postnatal cases

9. Add risk and safety report

Add one cross-maternity risk report that combines:

* High-risk pregnancy profiles
* ANC danger signs
* Labor escalation flags
* Delivery complications
* Newborn risk flags
* Postnatal danger signs
* Referrals required
* Admission requests pending
* Postnatal cases not ready for discharge

This should be advisory and operational, not a clinical rule engine.

10. Add CSV export where safe

Add simple CSV export for reports if existing export patterns exist.

Suggested exports:

* ANC report CSV
* Labor report CSV
* Delivery report CSV
* Newborn report CSV
* Postnatal report CSV
* Risk report CSV

Do not add heavy Excel/PDF dependency unless already used in the project.
CSV is enough for this phase.

11. Add billing mapping readiness

Do not post charges in this phase unless existing billing service mapping patterns make it completely safe.

Add a readiness/configuration view for maternity billing mappings.

Suggested categories:

* ANC registration/package
* ANC follow-up
* maternity admission
* labor observation
* normal delivery
* assisted delivery
* caesarean/theatre handoff
* delivery consumables
* newborn care
* neonatal observation
* newborn resuscitation
* postnatal mother care
* postnatal newborn care
* immunisation placeholder
* ultrasound placeholder
* maternity consumables

The readiness view should show:

* mapping configured or missing
* mapped service name/code if available
* active/inactive service state if available
* warning if missing
* warning if service inactive
* warning if duplicate mapping
* last updated by/time if available

Important:

* Do not auto-bill historical records.
* Do not recalculate invoices.
* Do not post charges from ANC, labor, delivery, newborn, or postnatal records in this phase.
* If mapping storage does not already exist, add config/table placeholders only.
* Missing mappings should show warnings, not fatal errors.

12. Add optional mapping configuration table if needed

If project billing mapping already has a pattern, reuse it.

If not, add a safe table such as:

`maternity_service_mappings`

Recommended fields:

* id
* mapping_key
* service_id nullable
* is_active boolean default true
* description nullable
* configured_by nullable user
* configured_at nullable timestamp
* timestamps

Do not use this table to post charges yet.
This is readiness/configuration only.

13. Add manual test data command/seeder

Add large manual test data for maternity without affecting default launch seeders.

Create a dedicated command/seeder, for example:

* `php artisan maternity:seed-manual-test-data`
* or `php artisan uhms:seed-maternity-manual-data`

The command should be explicit and never run from default seeders unless manually invoked.

Seed realistic data for:

* Low-risk pregnancy profile
* High-risk pregnancy profile
* Pregnancy with no ANC
* Pregnancy with ANC visits
* ANC danger sign/referral case
* Maternity admission request from ANC
* Labor episode from ANC
* Labor episode linked to admission
* Labor observations with normal progression
* Labor observation with escalation flags
* Delivery record normal vaginal delivery
* Assisted delivery
* Caesarean/theatre escalation placeholder
* Twin delivery
* Newborn live birth
* Newborn stillbirth
* Newborn low birth weight
* Newborn resuscitation required
* Postnatal mother/newborn stable
* Postnatal danger sign/referral required
* Postnatal follow-up due
* Discharge readiness advisory warning

Rules:

* Do not affect default production seeders.
* Use clearly identifiable fake/manual-test names.
* Avoid overwriting real data.
* Add option flags if useful:

  * `--count=`
  * `--fresh-manual`
  * `--department=`
* If deleting seeded test data, only delete records with a clear manual-test marker.

14. Add manual testing guide update

Update:

`docs/manual-testing/ADMISSION_MATERNITY_MANUAL_TESTING_PLAN.md`

Add end-to-end scenarios:

* Pregnancy profile → ANC → referral → admission request
* Pregnancy profile → ANC → labor
* Labor → observations → delivery
* Delivery → newborn records
* Twin delivery
* Stillbirth delivery
* Newborn patient linking
* Delivery → postnatal case
* Postnatal mother observation
* Postnatal newborn observation
* Postnatal readiness → admission discharge readiness warning
* Maternity reports and exports
* Billing mapping readiness warnings
* Manual seed command verification

15. Add final batch summary documentation

Create:

`docs/maternity/MATERNITY_WORKFLOW_BATCH_CLOSURE_REPORT.md`

Include:

* Phases completed
* Major models/tables added
* Major services/controllers/routes added
* Permissions added
* Reports added
* Billing readiness behavior
* Manual seed command behavior
* Existing workflows protected
* Known risks
* Deferred items
* Recommended next batch

16. Permissions

Add permissions additively.

Suggested permissions:

* `maternity.reports.view`
* `maternity.reports.export`
* `maternity.billing_readiness.view`
* `maternity.billing_readiness.manage`
* `maternity.manual_seed.run`

Keep all previous maternity permissions.

Recommended:

* Admin/super admin gets all.
* Maternity clinical roles get reports view.
* Finance/billing roles get billing readiness view/manage if existing roles support it.
* Manual seed command should be admin-only or console-only.

Do not remove existing permissions.

17. Localisation

Add EN/FR keys for:

* Maternity reports
* ANC report
* Labor report
* Delivery report
* Newborn report
* Postnatal report
* Risk report
* Export CSV
* Billing mapping readiness
* Mapping configured
* Mapping missing
* Service inactive
* Manual test data
* Seed manual data
* Report filters
* Date range
* No records found
* Summary metrics
* Follow-ups due
* Records pending
* Records complete
* Success/error messages

Maintain EN/FR localisation parity.

18. Activity logging

Log actions under MATERNITY:

* report exported
* billing mapping created/updated
* manual test data seeded
* manual test data cleared if implemented

Do not log clinical details unnecessarily.

19. Tests

Add targeted tests first, then run wider regression at the end.

Recommended new test file:

`tests/Feature/MaternityReportsBillingReadinessPhase13Test.php`

Recommended targeted tests:

* Maternity reports index renders.
* ANC report renders.
* Labor report renders.
* Delivery report renders.
* Newborn report renders.
* Postnatal report renders.
* Risk report renders.
* CSV export returns expected response.
* Billing readiness page renders missing mappings.
* Billing readiness can save mapping if implemented.
* No billing is posted from readiness view.
* Manual seed command runs.
* Manual seed command creates identifiable records.
* Default seeders are not modified to call manual maternity seed.
* Existing PostnatalCarePhase12Test still passes.
* Existing NewbornBirthOutcomePhase11Test still passes.
* Existing LaborDeliveryFoundationPhase10Test still passes.
* Existing AntenatalCarePhase9Test still passes.
* Existing MaternityFoundationPhase8Test still passes.
* Existing AdmissionDischargeReadinessPhase7Test still passes.
* Existing AdmissionNursingCarePhase6Test still passes.
* Existing AdmissionWorkflowFoundationTest still passes.

Run targeted checks:

* `php artisan test tests/Feature/MaternityReportsBillingReadinessPhase13Test.php`
* `php artisan test tests/Feature/PostnatalCarePhase12Test.php`
* `php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php`
* `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
* `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
* `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
* `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
* `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
* `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
* `php artisan route:list --name=maternity`
* `php artisan route:list --name=admissions`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check`
* PHP syntax checks on new/changed PHP files

Then, because this is the batch-closing phase, run one wider regression pass.

Use the project’s normal safe command for the wider test suite.

If a full suite is too large or environment-dependent, run the broadest practical project-safe suite and document what could not run.

The final report must clearly say:

* Full suite run: yes/no
* Command used
* Passed/failed counts
* Any failures
* Whether failures are related to this batch or pre-existing
* Recommended follow-up

20. Boundaries

Do not implement maternity billing posting.
Do not implement newborn billing.
Do not implement pharmacy dispensing.
Do not implement stock consumption.
Do not implement civil birth registry integration.
Do not rewrite admission discharge readiness.
Do not rewrite admission billing.
Do not rewrite emergency/theatre workflows.
Do not modify default launch seeders to include mass maternity data.
Do not remove existing routes.
Do not touch `docs/prompt.md`.

21. Final response

At the end, provide a concise completion report with:

* Summary of changes
* Files changed
* Migrations/routes/config/commands added
* Permissions added
* Reports added
* Billing readiness behavior
* Manual seed behavior
* Tests/checks run
* Wide regression result
* What was intentionally not changed
* Known risks
* Recommended next batch

