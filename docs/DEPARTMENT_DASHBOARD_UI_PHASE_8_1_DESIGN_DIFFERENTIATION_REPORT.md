# Department Dashboard UI — Phase 8.1: Design Differentiation, Naming & Personalisation

## Summary

Department dashboards were department-aware and scoped but **looked alike** — every
type rendered the same `hero → 4 KPIs → chart → services → stock → activity`
sequence and a generic title. Phase 8.1 makes them visually and structurally
different **by department type**, and personalises each with the **actual
department name**, following the core rule:

```
Department Type → dashboard name + layout family + menu identity (personality)
Department Name → dashboard identity / personalisation
Department ID   → data scope (unchanged; already enforced in Phases 6–8)
```

So a Laboratory user (type `investigation`) sees an **Investigation Dashboard** in
a **diagnostic_workbench** layout titled to **Laboratory Department**, while an
X‑Ray user (type `radiology`) sees a **Radiology Dashboard** in an
**imaging_workbench** layout titled to **X‑Ray Unit** — different name, different
layout, different identity, each scoped to its own `department_id`.

Permissions/modules remain the sole security layer; department type/name never
grant access. No department backfill was applied; the wide full suite was not run.

## Problem fixed

All department dashboards shared one Blade body, so only colours/metrics changed.
Emergency felt like Pharmacy; Radiology felt like Investigation. Phase 8.1
introduces **layout families** (structure/emphasis) chosen by department type, plus
type-driven naming and department-name personalisation.

## Dashboard naming strategy

- Names come from the **department type** (not the coarser dashboard key), so
  radiology ≠ investigation. Resolved via
  `departments.dashboards.{type}.name` (all 19 + a `generic` fallback), EN/FR.
- Subtitle = `:department · :type` (e.g. "Laboratory Department · Investigation").
- No more generic "My Dashboard" title; no hardcoded English in Blade.

## Department name personalisation

The hero/subtitle/scope badge/menu heading surface the **actual department name**:
- title (type-driven), subtitle (`name · type`), welcome (`Welcome, :name`),
  scope message (`You are viewing :department data only.`), and a personalised
  menu heading (`Laboratory Department Workbench`).
- No-department users get a safe fallback message; admins in global preview get a
  "Global Preview" badge instead of a fake scoped message.

## Layout family registry

`App\Services\Department\DepartmentDashboardLayoutRegistry` was extended to return,
per department type: `layout_family`, `hero_variant`, `name_key`,
`menu_heading_template`, plus the existing card profile. New resolver methods:
`layoutFamilyFor()`, `heroVariantFor()`, `menuHeadingTemplateFor()`, `nameKeyFor()`,
`hasLayoutFamilyForEveryDepartmentType()`.

### Type → layout family

| family | types |
|--------|-------|
| `clinical_queue` | consultation, treatment, procedure |
| `emergency_command` | emergency, ambulance |
| `diagnostic_workbench` | investigation, blood_bank |
| `imaging_workbench` | radiology |
| `surgery_board` | theatre |
| `ward_board` | nursing, inpatient, maternity |
| `dispensing_stock` | pharmacy |
| `finance_control` | finance |
| `stores_inventory` | stores |
| `records_office` | records |
| `generic_department` | mortuary, support, administrative (+ any future type) |

## Distinct dashboard personalities

Each family is a Blade partial under
`resources/views/admin/dashboards/department/partials/layouts/` that reuses the
shared low-level card partials but arranges them differently:

- **emergency_command** — red priority alert strip, queue-first main, breakdown +
  trend side-by-side, rapid-actions rail.
- **diagnostic_workbench** — "Samples" request pipeline + status breakdown, services/stock in the side column.
- **imaging_workbench** — "Imaging Schedule" volume chart first, then imaging queue (distinct from diagnostic).
- **surgery_board** — "Surgery Schedule" emphasis.
- **ward_board** — "Bed Occupancy" + admissions queue.
- **dispensing_stock** — "Dispensing Queue" first, stock usage promoted into the main column.
- **finance_control** — revenue trend first, "Cashier Sessions" table, restricted cards in side.
- **stores_inventory** — "Stock Movements" + stock usage emphasis.
- **records_office** — "Folder Requests" / recent activity first.
- **clinical_queue** — patient queue first, KPIs under it, clinical action rail.
- **generic_department** — the original balanced layout (safe fallback).

The low-level cards (`kpi-card`, `work-queue-card`, `services-card`,
`stock-usage-card`, `quick-actions`, etc.) are unchanged and reused; two tiny DRY
helpers (`_primary-cards`, `_secondary-cards`) render the KPI rows. `show.blade.php`
now just dispatches: `@includeFirst([layouts.{family}, layouts.generic_department])`.

## Menu management strategy

`DepartmentMenuProfileService::headingForType(?DepartmentType, ?string $name)`
returns a personalised heading from a template + department name
(`Laboratory Department Workbench`, `Emergency / Casualty Command Center`,
`Main Pharmacy Operations`, `Billing Office Control Room`, …). With no department
name it falls back to the type's dashboard name. The existing section
**prioritisation** (permission/module-filtered, reorder-only) is unchanged — the
heading is identity, never access.

## Department-scoped data behaviour

Unchanged from Phases 6–8: `DepartmentDashboardDataService` scopes every metric,
queue, service and stock panel by `current_department_id`. Verified by a test that
a Laboratory user sees Laboratory services and **not** X‑Ray services.

## Localisation

Added to `lang/{en,fr}/departments.php`: `dashboards.{type}.name` (19 + generic),
`dashboard.*` (subtitle/welcome/scope/preview/no-department), `menu_profiles.*`
(heading templates), `families.*`, `sections.*`. **EN/FR parity passes; localisation
audit reports 0 active runtime candidates.**

## Tests added

`tests/Feature/Departments/DepartmentDashboardDesignDifferentiationTest.php` (8,
all pass): 19 translated names; every type resolves a known layout family (+ the
key mappings); every family has a Blade partial; menu heading personalises/varies;
lab user → Investigation identity + diagnostic_workbench; radiology user distinct
(Radiology + imaging_workbench); services scoped to own department; no-department
fallback.

## Focused tests run

```
php artisan test tests/Feature/Departments/DepartmentDashboardDesignDifferentiationTest.php   # 8 passed
php artisan test tests/Feature/Departments tests/Feature/DepartmentDashboardTest.php tests/Feature/DepartmentMenuProfileTest.php   # 44 passed
```

## Minimal verification run

```
php artisan view:cache / view:clear     # all layout families compile
php scripts/localisation-parity-check.php  # EN/FR parity OK
php scripts/localisation-audit.php         # Active runtime candidates: 0
php -l (changed PHP)                        # clean
```

## Known limitations

- Some highly specialised dashboards (maternity, blood bank, mortuary, ambulance)
  use generic card metrics under their family layout; deeper module-specific
  widgets are a later enhancement.
- A drag-and-drop custom dashboard builder and predictive analytics are deferred.
- The wide full-suite regression was **not** run (per instructions).
- `departments:backfill-types --apply` was **not** run.

## Next recommended phase

Phase 8.2 — per-type metric depth: add module-specific data builders + widgets
(e.g. real bed-occupancy, transfusion safety, dispatch board) behind the families
already in place, and surface the personalised menu heading in the sidebar.
