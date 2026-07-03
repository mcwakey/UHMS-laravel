# Consultation Workflow Stabilisation Phase 2 JS Lifecycle Report

## Summary

Phase 2 moves the consultation page away from a large executable Blade script and into a single Vite page module with one lifecycle entrypoint.

The Blade page now provides:

- markup
- permissions
- server-rendered initial state
- one JSON config block
- declarative `data-*` attributes
- the consultation page Vite entrypoint

The JavaScript module now owns:

- initialization and teardown
- delegated event handling
- route context injection
- AJAX form submission
- modal form submission
- section and summary refreshes
- idempotency key refresh
- dynamic select loading
- prescription row handling
- edit/delete actions

## JS Files Created

Created:

- `resources/js/Pages/consultation-show.js`

Updated Vite input:

- `vite.config.js`

The module exposes a small compatibility namespace:

- `window.UHMSConsultation`

The public namespace includes:

- `init`
- `destroy`
- `routeContext`
- `ajaxForms`
- `modals`
- `sectionRefresh`
- `selectLoader`
- `idempotency`
- `toasts`
- `prescriptions`

## Inline Handlers Removed

Removed consultation workflow usage of:

- `onclick=`
- `onchange=`
- `onsubmit=`

Replaced with:

- `data-consultation-action`
- `data-consultation-form`
- `data-refresh-section`
- `data-route-context-required`
- `data-modal-form`
- `data-confirm`
- `data-preserve-tab`

Procedure, investigation, and lab department pickers now use delegated data-action handlers.

## Legacy Handlers Still Remaining

No inline `onclick`, `onchange`, or `onsubmit` handlers remain in `resources/views/consultations/show.blade.php`.

Some legacy CSS classes remain intentionally for styling and compatibility:

- `ajax-delete`
- `edit-entry-btn`
- `mark-final-btn`
- `set-primary-btn`
- `viewResultBtn`
- `apply-pattern-btn`

These no longer own binding directly; they are paired with `data-consultation-action`.

## AJAX Helper Design

The module centralizes consultation AJAX submission in `ajaxForms`.

It handles:

- route context injection
- idempotency key handling
- submit button loading state
- JSON response handling
- validation and authorization errors
- modal close on success
- form reset
- section refresh
- summary refresh
- active tab preservation

Forms continue to support `data-ajax-form`, but the preferred contract is now `data-consultation-form`.

## Modal Helper Design

The modal helper centralizes modal lifecycle cleanup.

It handles:

- clearing stale validation errors on open/close
- injecting route context into modal forms
- closing the containing modal after successful AJAX form submission
- keeping the send-session picker initialized when its modal opens

The lab request modal and route-to-investigation modal now use the shared modal AJAX form path.

## Route Context Helper Design

The route context helper reads the current consultation route ID from the JSON config and injects it into consultation forms before submit.

It ensures:

- hidden `consultation_route_id` inputs are present
- route IDs do not go stale after reset/refresh
- required route context blocks AJAX submit with a clear error
- standard non-AJAX consultation forms also receive route context

This complements the Phase 1 server-side guard.

## Idempotency Frontend Handling

The module centralizes idempotency refresh in `idempotency.refresh(form)`.

Rules:

- successful AJAX submissions regenerate that form's idempotency key
- failed validation does not regenerate the key
- network/server errors do not regenerate the key
- form reset preserves the regenerated key via `defaultValue`

## Section Refresh Lifecycle

`sectionRefresh` now owns consultation section refreshes.

It:

- fetches the current page with the active route context
- extracts the relevant section list from the returned HTML
- updates the section badge
- refreshes the consultation summary fragment
- preserves the active tab
- keeps route context synchronized after DOM replacement

## Select Loader Design

`selectLoader` centralizes dynamic option/content loading for:

- investigation department to services
- procedure department to procedure services
- lab department to catalog/free-text request items

It provides consistent loading, empty, and error states.

## Duplicate DOM IDs Fixed

Fixed the duplicate `id="investigationDeptSelect"` pattern.

The active investigation department picker keeps:

- `id="investigationDeptSelect"`

The fallback hidden select now uses:

- `id="investigationDeptFallbackSelect"`

## Localisation Changes

Added EN/FR keys in:

- `lang/en/consultations.php`
- `lang/fr/consultations.php`

New groups:

- `consultations.ajax.*`
- `consultations.modal.*`

These cover validation, session expiry, forbidden actions, network/server errors, section refresh failures, duplicate replay messaging, missing route context, submit-in-progress text, saved-successfully text, and modal loading/close copy.

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php`

Coverage includes:

- consultation page includes the JS module entrypoint
- Vite config includes the page entry
- consultation page exposes safe JSON config
- large inline script responsibilities are absent from Blade
- no inline `onclick`, `onchange`, or `onsubmit`
- procedure/investigation/lab pickers use data-action handlers
- consultation forms use shared data attributes
- idempotency keys are still present
- duplicate investigation department IDs are removed
- shared module helpers are present
- view cache compiles

Updated:

- `tests/Feature/ConsultationRouteSessionWorkflowTest.php`

The old inline-global assertion now checks the Phase 2 module/config contract.

## Verification Commands Run

Passed:

```bash
npm run build
php artisan view:cache
php artisan view:clear
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php
php artisan test tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php
php artisan test tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php
```

Results:

- `npm run build` passed and generated the consultation bundle
- view cache compiled and cleared successfully
- permissions audit passed with zero missing route permissions and zero unprotected admin mutation routes
- localisation audit reported active runtime candidates: 0
- localisation parity passed
- Phase 1 safety tests passed: 10 tests, 38 assertions
- Phase 2 lifecycle tests passed: 9 tests, 46 assertions
- focused neighbouring workflow tests passed: 49 tests, 213 assertions

Observed existing warning:

- permissions audit still reports the pre-existing possible duplicate permission names

## Known Limitations

- the large Blade file may still need structural splitting
- the large controller remains
- prescription clinical safety checks are deferred
- the completion checklist is deferred
- full UI redesign is deferred
- wide full suite was not run because Phase 2 requested focused tests only
- no Playwright consultation browser smoke test was added in this phase

## Next Recommended Phase

Phase 3 should split the large consultation controller and Blade sections only after this frontend lifecycle settles.

Recommended next targets:

- extract consultation action controllers by clinical domain
- introduce section partials for the largest tab panes
- add browser smoke tests for procedure selection, lab modal submit, prescription row add/remove, and section refresh
- move remaining hardcoded clinical labels into localisation in smaller batches
