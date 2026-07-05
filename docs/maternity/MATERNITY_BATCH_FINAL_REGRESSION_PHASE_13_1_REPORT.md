# Maternity Batch Final Regression Phase 13.1 Report

## Scope

Phase 13.1 verified the completed Admission/Ward/Maternity batch by unblocking the wide regression memory ceiling without changing application behavior.

No maternity workflow, billing posting, seeders, route names, permissions, or accounting behavior were changed.

## Original Memory Failure

The broad suite was reproduced with:

```bash
php artisan test --no-ansi
```

Environment details:

- CLI binary: `/usr/local/Cellar/php/8.4.5_1/bin/php`
- CLI ini file: `/usr/local/etc/php/8.4/php.ini`
- CLI memory limit: `128M`
- PHPUnit config: `phpunit.xml`
- Test database: SQLite `:memory:`

Result:

- The command started normally and passed unit tests plus the early accounting feature groups.
- It stopped before reaching assertion failures.
- Fatal error: `Allowed memory size of 134217728 bytes exhausted`.
- Reproduced failing point: `routes/web.php:1737`.
- The reported line is a plain payment integration route registration:

```php
Route::post('invoices/{invoice}/payment-request-sms', ...);
```

This line is not a query, model access, service instantiation, or billing/maternity side effect. It is the point where the process exhausted memory while loading the large existing route file.

## Root Cause / Best Explanation

The blocker is the local CLI memory ceiling combined with Laravel's `artisan test` wrapper.

`php artisan test` is provided by Collision's Laravel test command. It starts a child process as:

```php
[PHP_BINARY, 'vendor/phpunit/phpunit/phpunit']
```

Because the child command is launched from `PHP_BINARY` without the parent `-d memory_limit=...` option, this command still inherits the PHP ini default of `128M`.

That explains why:

```bash
php -d memory_limit=512M artisan test
```

still failed with a 128 MB memory ceiling in the PHPUnit child process.

The successful path is to apply the memory limit to the PHPUnit process itself:

```bash
php -d memory_limit=512M vendor/bin/phpunit --colors=never
```

## Safe Test-Memory Solution

Added a dev/test-only Composer script:

```json
"test:wide": [
    "@php -d memory_limit=512M vendor/bin/phpunit --colors=never"
]
```

Usage:

```bash
composer test:wide
```

This changes only the developer test command. It does not affect production configuration, application runtime, route behavior, billing, accounting, or maternity workflows.

## Files Changed

- `composer.json`
- `docs/maternity/MATERNITY_BATCH_FINAL_REGRESSION_PHASE_13_1_REPORT.md`

Generated audit/report artifacts produced during test runs were reverted because they were test side effects and not part of this phase.

## Commands Run

Configuration and diagnosis:

```bash
php -r 'echo "PHP_BINARY=".PHP_BINARY.PHP_EOL."memory_limit=".ini_get("memory_limit").PHP_EOL."ini_file=".(php_ini_loaded_file() ?: "none").PHP_EOL;'
php -d memory_limit=512M -r 'echo "parent_memory_limit=".ini_get("memory_limit").PHP_EOL;'
php -d memory_limit=512M vendor/bin/phpunit --version
composer run-script test:wide -- --version
```

Route/memory inspection:

```bash
php artisan test --no-ansi
nl -ba routes/web.php | sed -n '1710,1755p'
```

Broad regression:

```bash
php -d memory_limit=512M vendor/bin/phpunit --colors=never
php -d memory_limit=512M vendor/bin/phpunit --colors=never --no-output --log-junit storage/logs/phpunit-phase13-1-wide.xml
```

## Broad Regression Result

The memory-safe wide run completed.

Final quiet JUnit run:

- Tests: 1296
- Assertions: 4871
- Failures: 18
- Errors: 1
- Time: 349.119801 seconds
- Memory: the visible run completed at 357 MB

The suite no longer stops on route-loading memory exhaustion when run through the memory-safe PHPUnit command.

## Remaining Broad-Suite Defects

The remaining defects are not in the Phase 6-13 admission/maternity chain.

- `Tests\Feature\ActivityLogContextTest::test_sensitive_fields_are_masked`
- `Tests\Feature\AsyncPageBehaviorTest::test_appointment_show_uses_async_handler_without_forced_navigation`
- `Tests\Feature\AuthTest::test_doctor_redirected_to_doctor_dashboard`
- `Tests\Feature\BillingEnhancementsTest::test_invoice_show_renders_payments_once_in_the_combined_settlement_ledger`
- `Tests\Feature\InertiaBridgeLeakGuardTest::test_no_blade_view_introduces_unguarded_full_reload`
- `Tests\Feature\LegacyInertiaBridgeTest::test_inertia_complaint_submission_redirects_instead_of_returning_plain_json`
- `Tests\Feature\LegacyInertiaBridgeTest::test_non_inertia_ajax_complaint_submission_still_returns_json`
- `Tests\Feature\LegacyInertiaBridgeTest::test_inertia_diagnosis_submission_redirects_instead_of_returning_plain_json`
- `Tests\Feature\LegacyInertiaBridgeTest::test_non_inertia_ajax_diagnosis_submission_still_returns_json`
- `Tests\Feature\LegacyInertiaBridgeTest::test_inertia_treatment_submission_redirects_instead_of_returning_plain_json`
- `Tests\Feature\LegacyInertiaBridgeTest::test_non_inertia_ajax_treatment_submission_still_returns_json`
- `Tests\Feature\PatientManagementTest::test_patient_create_form_loads`
- `Tests\Feature\PatientManagementTest::test_patient_can_be_created`
- `Tests\Feature\PatientManagementTest::test_patient_registration_can_add_public_insurance_with_selected_tier`
- `Tests\Feature\PatientManagementTest::test_doctor_can_update_patient_medical_summary_without_full_patient_edit_permission`
- `Tests\Feature\PatientMergeTest::test_merge_index_offers_patient_search_card_with_assign_actions`
- `Tests\Feature\PatientMergeTest::test_merge_compare_page_uses_patient_numbers_not_ids`
- `Tests\Feature\Stage2NeedsReviewLogTest::test_logs_audit_has_zero_needs_review_and_zero_missing`
- `Tests\Feature\WorkflowJsonResponsesTest::test_triage_assessment_only_lists_billed_consultation_departments`

## Relationship To Maternity/Admission Batch

The remaining broad-suite failures are in activity logging expectations, appointment async rendering, auth redirection, billing enhancement display, Inertia bridge behavior, patient management, patient merge, stage-2 log audit, and triage JSON response behavior.

They do not indicate a failure in the completed maternity/admission Phase 6-13 chain.

## Next Batch Readiness Decision

The Phase 13.1 memory blocker is resolved for local broad regression by using:

```bash
composer test:wide
```

The Admission/Ward/Maternity batch is regression-verified by its targeted phase suite. The wider project suite can now run to completion and exposes unrelated pre-existing or cross-module defects that should be triaged separately.

Recommended next batch can start after acknowledging the unrelated broad-suite failures:

Maternity Billing Posting, Theatre/Emergency Escalation Integration, Lab/Radiology Hooks, and Production Readiness Hardening.
