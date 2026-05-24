# UHMS Remediation Implementation Plan

Date created: 2026-05-24  
Evidence source: `docs/UHMS_FULL_PROJECT_ANALYSIS_2026-05-24.md`  
Strategy: stabilize first, then push core operations toward true Inertia pages.

## Summary

This plan is the live implementation reference for closing the gaps found in the full project analysis. It covers failing tests, frontend runtime errors, blank Inertia shell states, security leaks, module enforcement, workflow consistency, billing/payment drift, documentation drift, dashboard/design issues, and production hardening.

Implementation will be phased. Each phase should update the progress log before moving to the next phase.

## Progress Log

| Date | Phase | Status | Notes |
| --- | --- | --- | --- |
| 2026-05-24 | Reference plan | Complete | Live remediation document created from the approved implementation plan. |
| 2026-05-24 | P0 stabilization | Complete | Inertia/legacy shell safety improved, shared design CSS loaded in both layouts, legacy globals/scripts ordered, `uhms-loading` fallback added, raw visit creation exception details removed, module middleware added to direct routes, billing item snapshots fixed, NHIS claim item fields fixed, stale patient/visit/user/report tests aligned with current business behavior, and visible theme customizer/product-sales residue reduced. |
| 2026-05-24 | Test baseline recovery | Complete | `php artisan test` now passes: 174 tests, 450 assertions. `npm run build` passes and the unresolved `card-bg-02.png` warning is gone. Touched PHP files pass Pint. Full repo-wide `vendor/bin/pint --test` still reports pre-existing formatting/line-ending debt outside this remediation scope. |
| 2026-05-24 | Browser smoke | Partial | Authenticated desktop smoke passes for dashboard, patients, visits, visit creation, consultations, invoices, payments after reload, lab requests, pharmacy dispensing, and admissions with no new console errors after the rebuilt assets and cleared service-worker cache. Browser navigation timeouts can interrupt large legacy responses before completion, so manual reload was used to validate the payment screen. Mobile-width smoke was started but timed out in the browser harness and should be rerun in the next pass. |

## Phase 1: Reference Plan and Baseline

- Keep this file as the active execution reference.
- Keep `docs/UHMS_FULL_PROJECT_ANALYSIS_2026-05-24.md` as the evidence source.
- Run and record the starting baseline:
  - `php artisan test`
  - `npm run build`
  - `vendor/bin/pint --test`
  - Browser smoke checks for login, dashboard, patients, visits, billing, lab, pharmacy, and admissions.
- Do not treat older conflicting documents as authoritative until they are reviewed and marked current or superseded.

## Phase 2: P0 Stabilization

- Fix the Inertia/legacy shell:
  - shared design CSS loads in classic Blade and Inertia layouts.
  - legacy scripts load after required globals.
  - page scripts initialize idempotently.
  - post-login navigation never leaves a blank `uhms-loading` page.
- Remove production-facing raw exception details from visit creation and similar controller failure paths.
- Fix billing/payment drift:
  - legacy invoice creation populates required invoice-item pricing fields.
  - payment tests and fixtures match the current item-allocation model.
- Lock official patient number behavior to the current UHMS format.
- Add route-level module enforcement using existing module slugs.
- Remove visible template residue such as theme customizer and product-sales language from production UI.

## Phase 3: P1 Workflow Consistency

- Make `VisitWorkflowService` the canonical path for normal visit status transitions.
- Replace raw visit status strings with enum values.
- Define `visit_services` as an active service-assignment and audit snapshot while invoice items remain the billing source of truth.
- Normalize invoice item creation through one billing helper.
- Move high-risk inline validation into Form Requests.
- Split the large admin route file into module route files while preserving route names and URLs.

## Phase 4: P2 Inertia Core Operations Push

Convert core operations first, in this order:

1. Dashboard.
2. Patient list and search.
3. Visit creation.
4. Triage.
5. Consultation workbench.
6. Payment and cashier workflow.

Rules:

- Preserve existing route names and permissions.
- New Inertia pages receive server-owned props for filters, rows, metrics, lookups, permissions, and flash data.
- Business rules remain on the server.
- Vue pages handle interaction, validation display, filtering controls, and workflow actions.
- Existing Inertia pages are aligned with the same prop, layout, and design conventions.

## Phase 5: P3 Production Hardening and Documentation

- Add CSP in report-only mode after console errors are cleared.
- Add direct-access tests for disabled modules, unauthorized actions, and sensitive patient workflows.
- Profile dashboard and report queries before optimizing.
- Consolidate living docs into architecture, workflows, design system, testing, and deployment references.
- Add deployment checks for:
  - `APP_DEBUG=false`
  - storage link
  - queue and scheduler health
  - backups
  - cache config
  - required production permissions

## Public Interfaces and Behavior

- Existing route names and user-facing URLs are preserved unless a route is proven dead and documented before removal.
- `VisitWorkflowService` is the public workflow interface for visit state changes.
- `BillingService` keeps backward-compatible invoice creation entry points, but invoice item writes use the canonical pricing/item behavior internally.
- Module middleware is an access boundary; disabled modules block direct URLs.
- Inertia pages use server-provided props and existing Laravel validation/authorization.
- No separate external API version is introduced.

## Test Plan

- Backend:
  - run targeted tests after each subsystem fix.
  - run full `php artisan test` before phase completion.
- Formatting/static quality:
  - run `vendor/bin/pint --test`.
  - only run mutating Pint deliberately.
- Frontend:
  - run `npm run build`.
  - remove unresolved asset warnings.
- Browser smoke:
  - login.
  - dashboard.
  - patients.
  - visit creation.
  - triage.
  - consultation.
  - invoice/payment.
  - lab requests.
  - pharmacy dispensing.
  - admissions.

## Acceptance Criteria

- Full PHP test suite passes.
- Vite build passes with no unresolved asset warnings.
- No console errors on core screens.
- Login never lands on a blank page.
- Disabled module URLs are blocked directly.
- Billing supports manual invoice items, visit service invoice items, partial payment, full payment, insured pricing, and cash pricing.
- Visit workflow covers scheduled, confirmed, waiting, triage, waiting consultation, consulting, billing, completed, cancelled, rescheduled, no-show, emergency, admitted, and discharged paths.
- Dashboard, patient table, visit creation, and payment workflow are usable at desktop and mobile widths.

## Assumptions

- Implementation is phased, not one large uncontrolled rewrite.
- Stale tests and stale docs are corrected when they conflict with current valid business behavior.
- Core operations are the first Inertia conversion wave.
- Blade remains supported during migration, but new high-interaction work moves toward true Inertia pages.
- The full analysis document remains the evidence record; this file is the active execution reference.
