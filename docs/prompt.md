You are working inside the UHMS Laravel project.

We have completed and regression-verified the Admission/Ward/Maternity workflow batch through Phase 13.1.

Completed chain:

Pregnancy Profile
→ ANC Visits
→ Labor Episode
→ Labor Observations
→ Delivery Record
→ Newborn Records
→ Postnatal Care
→ Reports / CSV Export / Billing Mapping Readiness / Manual Test Data

Phase 13.1 also added a memory-safe broad regression command:

```bash
composer test:wide
```

Important regression status:

* The required maternity/admission targeted suite passed.
* The broad suite now runs to completion with the memory-safe command.
* The broad suite currently has unrelated failures outside the Phase 6–13 admission/maternity chain.
* Do not claim the entire project suite is green until those unrelated failures are triaged separately.

Now begin the next implementation batch:

Phase 14: Maternity Billing Posting, Theatre/Emergency Escalation Integration, Lab/Radiology Hooks, and Production Readiness Hardening.

Goal:
Safely move from maternity workflow capture into controlled operational integrations:

1. Billing posting through existing billing services.
2. Theatre/procedure handoff for caesarean and delivery complications.
3. Emergency handoff for maternity/labor/postnatal escalation.
4. Lab/radiology request hooks from ANC and maternity workflows.
5. Production readiness hardening.

Important:
This batch must be implemented carefully and in smaller phases.
Do not post billing until mapping readiness and duplicate-prevention rules are in place.
Do not rewrite billing/accounting.
Do not rewrite theatre.
Do not rewrite emergency.
Do not rewrite lab/radiology.
Do not rewrite pharmacy or stock consumption.
Do not modify default launch seeders.
Do not touch `docs/prompt.md`.
Do not run the full wide suite until the end of this batch; use targeted tests during phases.

Known broad-suite status:
`composer test:wide` currently completes but has unrelated existing failures outside the maternity/admission chain. Keep that documented. If final wide regression is run at the end of this batch, distinguish new failures from existing unrelated failures.

Phase 14.1: Billing Posting Design and Safety Audit

This first phase is audit/design-first. Do not implement broad billing posting yet.

1. Audit existing billing architecture

Review existing billing/invoice/accounting patterns:

* Invoice creation
* Invoice item creation
* Visit service billing
* Admission billing
* Emergency billing
* Procedure/theatre billing
* Investigation/radiology billing
* Pharmacy billing
* Service catalog
* Service mapping patterns
* Duplicate charge prevention patterns
* Reversal/credit note/write-off behavior
* Insurance/NHIS/cash pricing behavior
* Activity logging
* Billing permissions
* Existing billing tests

Document the safest existing billing service to reuse.

2. Audit maternity billing readiness from Phase 13

Review:

* `maternity_service_mappings`
* `MaternityServiceMapping`
* `MaternityBillingReadinessService`
* Billing readiness UI
* Mapping keys:

  * ANC registration/package
  * ANC follow-up
  * Maternity admission
  * Labor observation
  * Normal delivery
  * Assisted delivery
  * Caesarean/theatre handoff
  * Delivery consumables
  * Newborn care
  * Neonatal observation
  * Newborn resuscitation
  * Postnatal mother care
  * Postnatal newborn care
  * Immunisation placeholder
  * Ultrasound placeholder
  * Maternity consumables

Confirm that readiness remains warning-only unless billing posting is explicitly triggered.

3. Design maternity billing events

Define which maternity events may eventually post charges.

Suggested billable events:

* ANC registration/package
* ANC follow-up visit
* Maternity admission
* Labor observation/care
* Normal delivery
* Assisted delivery
* Caesarean/theatre handoff
* Delivery consumables
* Newborn care
* Neonatal observation
* Newborn resuscitation
* Postnatal mother care
* Postnatal newborn care
* Immunisation placeholder
* Ultrasound placeholder
* Maternity consumables

For each event, define:

* source model
* source ID
* mapping key
* invoice context
* patient responsible for charge
* mother patient vs newborn patient policy
* visit/admission context
* duplicate prevention key
* whether charge is automatic or manual
* whether charge is allowed when mapping missing
* audit log action

4. Design billing posting service

Create a design for a service such as:

`app/Services/Maternity/MaternityBillingPostingService.php`

The service should eventually support:

* preview charge
* post charge
* skip if already posted
* detect duplicate source charge
* explain missing mapping
* explain inactive service
* log charge posting
* return structured result

Do not implement full posting yet unless safe and explicitly scoped.

Recommended design structure:

* `previewForSource($sourceModel, string $mappingKey)`
* `postForSource($sourceModel, string $mappingKey, User $actor)`
* `alreadyPosted($sourceModel, string $mappingKey)`
* `resolveMapping(string $mappingKey)`
* `resolveBillingContext($sourceModel)`
* `buildInvoiceItemPayload(...)`

5. Add billing posting ledger table if needed

If existing invoice item metadata can safely store source references, reuse it.

If not, propose a lightweight table such as:

`maternity_billing_events`

Recommended fields:

* id
* mapping_key
* source_type
* source_id
* patient_id
* visit_id nullable
* admission_id nullable
* invoice_id nullable
* invoice_item_id nullable
* service_id nullable
* amount nullable decimal
* status
* posted_by nullable user
* posted_at nullable timestamp
* skipped_reason nullable text
* metadata nullable json
* timestamps

Suggested statuses:

* previewed
* posted
* skipped
* failed
* reversed

Important:

* This table must not replace the real invoice system.
* It only tracks maternity source-to-billing linkage and duplicate prevention.
* Do not create it if existing billing metadata already solves this safely.

6. Define mother/newborn billing policy

This is important.

Design the default policy:

* Pregnancy/ANC/labor/delivery/postnatal mother care charges bill to the mother’s visit/admission.
* Newborn care charges may bill to:

  * mother visit/admission by default, or
  * newborn patient/visit if linked and site policy enables it.
* Newborn patient billing must be configurable and disabled by default unless the hospital wants separate newborn accounts.
* Stillbirth billing rules must be conservative and configurable.

Suggested config:

```php
'maternity_billing' => [
    'enabled' => env('MATERNITY_BILLING_ENABLED', false),
    'auto_post' => env('MATERNITY_BILLING_AUTO_POST', false),
    'newborn_billing_policy' => env('MATERNITY_NEWBORN_BILLING_POLICY', 'mother'),
]
```

Allowed newborn policies:

* `mother`
* `newborn_if_linked`
* `disabled`

Defaults must avoid unexpected billing.

7. Add UI preview only if safe

If safe, add a billing preview panel on relevant maternity pages.

Pages:

* ANC visit detail
* Labor episode detail
* Delivery record detail
* Newborn record detail
* Postnatal case detail

Panel should show:

* relevant mapping keys
* configured/missing service
* already posted or not
* estimated amount if available
* warning that posting is disabled unless enabled
* future post button placeholder if not implemented

Do not add active post buttons yet unless this phase explicitly implements posting.

8. Permissions

Add permissions additively if needed:

* `maternity.billing.preview`
* `maternity.billing.post`
* `maternity.billing.override`
* `maternity.billing.audit.view`

Admin/super admin gets all.
Billing/finance roles may get preview/post depending on existing role conventions.
Clinical maternity roles may view preview but should not post unless project convention allows.

Do not remove existing permissions.

9. Localisation

Add EN/FR keys for:

* Maternity billing
* Billing preview
* Billing posting disabled
* Mapping missing
* Service inactive
* Already posted
* Ready to post
* Duplicate prevented
* Mother billing
* Newborn billing
* Newborn billing disabled
* Newborn billing to mother
* Newborn billing if linked
* Billing event
* Billing audit
* Success/error messages

Maintain localisation parity.

10. Documentation

Create:

`docs/maternity/MATERNITY_BILLING_POSTING_PHASE_14_1_DESIGN_REPORT.md`

The report must include:

* Existing billing architecture reviewed
* Maternity billing mappings reviewed
* Proposed billable events
* Proposed duplicate-prevention strategy
* Proposed mother/newborn billing policy
* Proposed service design
* Proposed tables/columns if any
* Proposed config flags
* UI preview behavior
* What was implemented, if anything
* What was intentionally deferred
* Risks
* Next phase recommendation

11. Tests/checks

Since this is design-first, tests depend on whether code changes are made.

If code changes are made, add targeted tests.

Recommended test file:

`tests/Feature/MaternityBillingPostingPhase14_1Test.php`

Recommended tests:

* Billing readiness page still renders.
* Billing preview shows missing mappings.
* Billing preview shows configured mappings.
* Billing preview does not post invoice items.
* Maternity billing config defaults to disabled.
* Newborn billing policy defaults to mother or disabled as chosen.
* Existing Phase 13 billing readiness tests still pass.

Run targeted checks:

* `php artisan test tests/Feature/MaternityBillingPostingPhase14_1Test.php` if added
* `php artisan test tests/Feature/MaternityReportsBillingReadinessPhase13Test.php`
* `php artisan test tests/Feature/PostnatalCarePhase12Test.php`
* `php artisan route:list --name=maternity`
* `php artisan view:clear`
* `php artisan config:clear`
* `git diff --check -- . ':!docs/prompt.md'`
* PHP syntax checks on changed PHP files

Do not run the full wide suite in this phase.

12. Boundaries

Do not enable automatic maternity billing.
Do not post invoices unless explicitly scoped and safely tested.
Do not recalculate historical invoices.
Do not change accounting ledger behavior.
Do not change credit note/write-off behavior.
Do not change insurance pricing behavior.
Do not post stock consumption.
Do not dispense pharmacy items.
Do not rewrite theatre/emergency/lab/radiology workflows.
Do not touch `docs/prompt.md`.
Do not run the full suite.

13. Final response

At the end, provide a concise report with:

* What was audited
* What was designed
* Files changed
* Config/permissions added
* Tests/checks run
* What was intentionally not changed
* Risks
* Next phase recommendation

Recommended next phase:
Phase 14.2: Controlled Maternity Billing Posting for Manual Actions Only.
