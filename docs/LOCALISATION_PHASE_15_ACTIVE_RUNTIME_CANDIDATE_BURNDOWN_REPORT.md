# UHMS Localisation Phase 15 - Active Runtime Candidate Burn-Down Report

Date: 2026-06-13

## Outcome

Phase 15 replaced the previous path-only audit classification with a route/controller-aware Blade inventory. It then translated the highest-confidence patient, consultation, visit, dashboard, shared-component, and inline JavaScript candidates found in that active set.

| Measure | Before | After |
|---|---:|---:|
| Original active-runtime candidates | 4,302 | 519 |
| Route-aware active baseline | 650 | 519 |
| Confirmed active candidates fixed | - | 131 |
| Candidates reclassified from the original active bucket | - | 3,652 |
| Demo/template candidates | 13,403 mixed known false positives | 15,788 |
| Known false positives | 13,403 mixed total | 1,383 |
| Service-title manual-review candidates | 394 | 394, all assigned an A-E class |

The 3,652 classification reduction is not claimed as translated UI. It comes from proving that legacy theme/demo Blade files and their dependencies are not reachable from live routes/controllers. The 131 reduction from 650 to 519 represents confirmed active-runtime fixes.

## Audit Improvements

`scripts/localisation-audit.php` now:

- discovers literal Blade views from live routes, controllers, and view components;
- resolves active `@extends`, `@include`, `@component`, and Blade component dependencies;
- excludes Blade and HTML comments;
- separates dormant demo/template views from active runtime views;
- records route-linked and shared-component status;
- emits the required active-runtime worklist;
- assigns every service candidate an A-E review class.

The generated worklist is in `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

## Files And Modules Fixed

High-confidence translations were completed in:

- patient profile, insurance, emergency contact, billing, registration, empty-state, and AJAX surfaces;
- consultation confirmations, forms, options, loading/error states, follow-up controls, and inline JavaScript;
- visit create, edit, index, preview, show, and shared visit-summary surfaces;
- admin and doctor dashboards;
- patient card and patient visit header partials;
- the shared action-menu component.

Paired language changes:

- `lang/en/consultations.php`
- `lang/fr/consultations.php`
- `lang/en/patients.php`
- `lang/fr/patients.php`

## Frontend And Shared Surfaces

Inline consultation and patient JavaScript now receives translated strings through module-level Blade JSON maps. The shared action-menu default now uses `common.actions`.

`resources/js/script.js` retains 23 scanner candidates. They are legacy selector-conditioned demo option lists and notification examples whose matching modal markup is not in the route-aware Blade graph. They are deferred rather than wired into a second frontend localisation mechanism. The existing global DataTables/date-range strings continue to use `window.UHMS_I18N`.

`SidebarMenuBuilder` was reviewed. Section and item labels pass through `translateLabel()`, which resolves `menu.*` keys and preserves the English fallback. Route, module, permission, hierarchy, and active-pattern behavior were not changed.

## Service Candidate Review

All 394 service-title manual-review candidates are individually marked in the detailed audit:

| Class | Meaning | Count | Phase 15 action |
|---|---|---:|---|
| A | Potential user-facing service output | 67 | Deferred for targeted service-by-service translation |
| B | Internal audit/event text | 4 | Left unchanged |
| C | Stored canonical event/title | 62 | Deferred to preserve stored semantics |
| D | SQL/internal expression | 0 | False positive when present |
| E | Translated downstream | 261 | Confirmed downstream handling, including sidebar labels |

No stored event, audit, workflow, accounting, or SQL semantics were changed.

## Dynamic Label Sweep

Safe active enum displays were changed from `label()` to `translatedLabel()` across patient, visit, appointment, dashboard, insurance, invoice, gender, blood-group, marital-status, priority, consultation-mode, and status surfaces.

Remaining `ucfirst()`, `typeLabel()`, and similar calls are mixed with database content, historical records, or domain-specific display helpers. They remain documented for Phase 15B rather than being converted without a verified translation contract.

## Remaining Active Work

Final active-runtime candidates: **519**.

Top remaining files:

| File | Candidates |
|---|---:|
| `resources/js/script.js` | 23 |
| `resources/views/admin/analyzers/diagnostics.blade.php` | 22 |
| `resources/views/admin/analyzers/index.blade.php` | 19 |
| `resources/views/pharmacy/dispense.blade.php` | 19 |
| `resources/views/admin/analyzers/show.blade.php` | 17 |
| `resources/views/pharmacy/drug-history.blade.php` | 17 |
| `resources/views/theatre/rooms/index.blade.php` | 16 |
| `resources/views/admin/procedures/index.blade.php` | 14 |
| `resources/views/admin/procedures/schedule.blade.php` | 14 |
| `resources/views/admin/product-stock/ledger.blade.php` | 12 |

The principal deferred modules are analyzers/laboratory integration, pharmacy, theatre/procedures, wards, stock/store, prescriptions, investigations, emergency, and accounting. These are route-linked and should be processed in bounded module batches.

## Verification

| Check | Result |
|---|---|
| `php artisan view:clear` | Passed |
| `php artisan config:clear` | Passed |
| `php artisan cache:clear` | Passed |
| `php artisan route:list` | Passed, 714 routes |
| `php artisan view:cache` | Passed |
| Final `php artisan view:clear` | Passed |
| All `lang/en/*.php` and `lang/fr/*.php` lint | Passed |
| Recursive EN/FR parity | Passed |
| `php -l scripts/localisation-audit.php` | Passed |
| `git diff --check` | Passed; line-ending warnings only |
| `php scripts/localisation-audit.php` | Passed |

No new package, localisation system, business-logic change, workflow change, or permission change was introduced.

## Recommendation

**Needs Phase 15B before the Full Test Suite.**

The active set is now trustworthy and substantially smaller, but 519 route-aware candidates remain. Phase 15B should process the top remaining clinical/operational modules and the 67 class-A service candidates before declaring runtime localisation complete.
