# UHMS Department Type Expansion — Phase 0 Gap Analysis & Adaptation Plan

> **Status:** Analysis / planning only. No production data changed, no migrations
> run, no full test suite executed. Implementation code was **not** modified
> (this report is the only deliverable).

---

## 1. Executive summary

UHMS already has **one canonical source of truth** for department types — the
string-backed PHP enum `App\Enums\DepartmentType` — and the `departments.type`
column is already a **plain string** (not a DB enum). That is the right
foundation and means the expansion needs **no schema change**.

However, the enum is currently **half-migrated**: the 11 new cases have already
been *added* to the enum, but several `match()` expressions that switch on a
`DepartmentType` were **not** updated and have **no `default` arm**. Because a
PHP `match` throws `\UnhandledMatchError` when nothing matches, the system will
**fatal-error (HTTP 500)** the moment a new type is encountered.

**This is already happening** for at least one screen: the Services settings page
iterates `DepartmentType::cases()` and calls `->label()` on every case, so it
500s today (the new cases have no `label()` arm).

The headline work for Phase 1 is therefore **defensive, not additive**: make every
`DepartmentType` consumer exhaustive (or give it a safe fallback), add
translations, fix the seeder/demo mappings, and ship a safe backfill command —
*before* any real department is reclassified.

### Critical breakages found (ordered by severity)

| # | Location | Symptom | Trigger |
|---|----------|---------|---------|
| 1 | `DepartmentType::label()` | `UnhandledMatchError` | any of the 11 new cases |
| 2 | `resources/views/admin/services/index.blade.php:187` | **page 500 right now** | calls `->label()` over all `cases()` |
| 3 | `resources/views/vitals/record.blade.php:159` | 500 when a dept has a new type | `$dept->type->label()` |
| 4 | `DepartmentType::color()` | `UnhandledMatchError` | new cases (no current caller, latent) |
| 5 | `DepartmentType::toVisitStatus()` | `UnhandledMatchError` | new cases (no current caller, latent/dead) |
| 6 | `App\Services\Dashboard\DepartmentDashboardResolver::resolveKey()` (`match($type)`) | `/admin/my-dashboard` 500s | user assigned to a new-type department |

---

## 2. Current source of truth

**Single, canonical:** `app/Enums/DepartmentType.php` (string-backed enum), cast on
`App\Models\Department::$casts['type'] => DepartmentType::class`.

No competing definitions were found — there is **no** config file, model-constant
list, DB lookup table, JS array, or migration enum column duplicating the values.
Validation uses `Rule::enum(DepartmentType::class)`, so it tracks the enum
automatically. This is healthy: **one source of truth.**

Secondary surfaces that consume the enum (not separate sources, just consumers):
- `Department` model scopes (`scopeConsultation`, `scopeAcceptsRequests`).
- `service_catalog.department_type` (a denormalised string copy on services).
- Blade `@foreach(DepartmentType::cases() …)` option lists.
- Tests/factories use `DepartmentType::X->value`.

---

## 3. Current vs. proposed type list

**Old list** (still the only arms in `label()`/`color()`/`toVisitStatus()`, and
preserved as comments at the top of the enum) — 8 values:
`consultation, investigation, procedure, treatment, pharmacy, radiology, support, administrative`.

**Active enum cases today** — 19 values (old 8 **+** 11 new):
`consultation, emergency, investigation, radiology, procedure, theatre, treatment,
nursing, pharmacy, inpatient, maternity, blood_bank, mortuary, ambulance, records,
finance, stores, support, administrative`.

So the "expansion" of values is effectively **already done in the enum**; what is
missing is everything that *reads* the enum.

---

## 4. Files / classes using department types

### 4.1 Enum internals needing exhaustive handling
- `app/Enums/DepartmentType.php` — `label()`, `color()`, `toVisitStatus()` (all 8-arm, no default), plus `translatedLabel()` which points at `statuses.default.{value}`.

### 4.2 Services / resolvers
- `app/Services/Dashboard/DepartmentDashboardResolver.php` — **`match($type)` with no default (break #6)**; also a role→key map.
- `app/Services/Dashboard/DepartmentDashboardService.php` — `build($key)` **is safe** (`default => generic()`); has builders for consultation/pharmacy/investigation/theatre/billing/stock/accounting/emergency/admission/blood_bank/claims/hr/reception.
- `app/Services/ConsultationRouteService.php`, `ConsultationFollowUpService.php` — `where('type', CONSULTATION)`.
- `app/Services/VisitService.php` — consultation routing keyed on `DepartmentType::CONSULTATION` + `ServiceType` (service category).

### 4.3 Controllers (type-filtered queries — all keyed on OLD values)
- `Doctor\ConsultationController` (CONSULTATION ×3), `Admin\Visits\VisitController` (CONSULTATION), `Admin\Patients\TriageController` (CONSULTATION), `Admin\AdmissionsWard\VitalController` (CONSULTATION + `->type->label()`), `Admin\Lab\LabTestController` (INVESTIGATION), `Admin\Lab\InvestigationItemController` (INVESTIGATION+RADIOLOGY), `Admin\Procedures\ProcedureConsumablesController` (PROCEDURE), `Admin\Store\DepartmentConsumablesController` (TREATMENT), `Admin\Emergency\EmergencyCaseController` (PROCEDURE), `Admin\Settings\DepartmentController` (`cases()` + `Rule::enum`).
- `app/Http/Requests/Concerns/ValidatesVisitServiceRoutes.php` (CONSULTATION).
- Console: `LinkInvestigationItemsToProductsCommand` (INVESTIGATION+RADIOLOGY), `LinkDrugsToProductsCommand` (PHARMACY), `MigrateConsultationRoutesToDepartmentsCommand`.

### 4.4 Views
- `admin/services/index.blade.php` (**break #2**), `vitals/record.blade.php` (**break #3**), `components/department-services-card.blade.php`, `consultations/show.blade.php` (CONSULTATION filter).

### 4.5 Tests / factories
~20 test files reference `DepartmentType::*` (mostly CONSULTATION/INVESTIGATION/PROCEDURE/PHARMACY/TREATMENT). `database/factories/DepartmentFactory.php`. These are not broken by the new values but encode the **old generic mappings** (e.g. `UnifiedInventoryWorkflowTest` types an "Emergency Department" as `SUPPORT` and a "Theatre" as `PROCEDURE`).

---

## 5. Database gap analysis

`departments.type` — **`string`, nullable** (`2026_04_18_024049_add_type_to_departments…`). No DB enum. ✅ No schema change required to add values.

| Table | Stores `department_id` | Stores `department_type` | Notes |
|-------|:---:|:---:|-------|
| `departments` | — | ✅ `type` (string) | source of truth row |
| `service_catalog` | ✅ | ✅ `department_type` (string, denormalised) | `2026_04_20_000001` |
| `visit_consultation_routes` | ✅ | — | infers via department |
| `queue_entries` | ✅ (nullable) | — | triage = null dept |
| `visit_department_history` | ✅ | — | |
| `lab_requests` | ✅ `target_department_id` | — | |
| `users` | ✅ (single) | — | **one** dept per user |
| `wards` / `beds` | (ward → dept by convention) | — | wards not explicitly typed `inpatient` |
| `emergency_cases`, `admissions`, `blood_*`, `theatre_*`, `stock_*` | via department/module | — | infer type from module, not `department_type` |

**Backfill candidates:** every `departments` row whose `type` is a *generic* old
value that now has a precise new value (see §11). `service_catalog.department_type`
copies may also need re-sync after department remap. No other table stores a
department-type string, so backfill scope is small and contained.

---

## 6. Seeders affected

`database/seeders/DepartmentSeeder.php` maps real departments onto **old generic
types** and is the main demo-data offender:

| Seeded department | Current type | Should be |
|-------------------|--------------|-----------|
| Emergency / Casualty | `consultation` | `emergency` |
| Radiology / X-Ray, Ultrasound | `investigation` | `radiology` |
| Theatre / Procedures | `procedure` | `theatre` (or split theatre vs procedure) |
| Physiotherapy | `treatment` | `treatment` ✓ (or `nursing`) |
| Male/Female/Paediatric/Surgical Ward, ICU, NICU | `administrative` | `inpatient` |
| Maternity Ward, Antenatal/Postnatal | `administrative` / `consultation` | `maternity` |
| Records | `administrative` | `records` |
| Billing | `administrative` | `finance` |

Also `ServiceCatalogSeeder`/factory data and `DepartmentFactory` default type.

---

## 7. Validation affected

`Admin\Settings\DepartmentController@store/@update` use
`['type' => ['nullable', Rule::enum(DepartmentType::class)]]` — **auto-accepts the
new values**, no change needed. No other FormRequest hardcodes a type allow-list.
(Service forms post a free `department_type` string; consider validating it against
`Rule::enum(DepartmentType::class)` in Phase 1.)

---

## 8. Forms / views affected

- **Crash now:** `admin/services/index.blade.php` (option list via `->label()`).
- **Crash if dept retyped:** `vitals/record.blade.php`.
- Option lists that are safe today (use `->value`) but show poor labels for new
  types until §15 is done: `components/department-services-card.blade.php`,
  department create/edit select in settings.

---

## 9. Dashboard impact

Infrastructure already exists and is mostly sound:
`DepartmentDashboardController` → `DepartmentDashboardResolver::resolveKey()` →
`DepartmentDashboardService::build($key)` (data-driven widgets, `default→generic`).

- **Already have a dashboard key:** management, consultation, pharmacy,
  investigation, theatre, billing, stock, accounting, emergency, admission,
  blood_bank, claims, hr, reception, generic.
- **Resolver type→key map (break #6)** only covers the old 8 and **throws** for
  new types. It also resolves emergency/blood_bank/etc. by **role**, not by
  department type — so those dashboards are reachable today only via role.
- **Missing/needs-mapping for new types:** `emergency→emergency`,
  `theatre→theatre`, `inpatient→admission`, `nursing→admission` (or new nursing),
  `maternity→admission` (or new), `blood_bank→blood_bank`, `records→reception`,
  `finance→accounting`/`billing`, `stores→stock`, `mortuary→generic`,
  `ambulance→generic`. Dedicated dashboards for nursing/maternity/mortuary/
  ambulance/records can start as generic or share the nearest existing one.

**Future direction:** keep `build()` as the registry; replace the resolver
`match` with an exhaustive `type→key` map (or add `default`).

---

## 10. Menu impact

`app/Services/SidebarMenuBuilder.php` is **role + permission + module** driven
(`permission`, `module`, `active_patterns` keys). It does **not** read department
type, so the expansion does **not** break the menu. There is currently **no**
department-type menu grouping, and `users` carry a **single** department.

**Future direction (non-breaking):** keep permission/module checks as the security
layer; use department type only to *group/prioritise* sections (e.g. surface the
user's department-type dashboard first). Add this in a `DepartmentMenuProfile`
helper or extend `SidebarMenuBuilder`; never hide a permitted route just because
department mapping is missing.

---

## 11. Workflow routing impact

Most clinical routing filters on **specific old type values**, e.g.
`Department::where('type', DepartmentType::CONSULTATION->value)`. The **migration
risk is behavioural, not structural**: reclassifying a department changes which
type-filters it matches.

| Workflow | Current filter | Effect of retyping |
|----------|----------------|--------------------|
| OPD consultation queue / triage / vitals | `type = consultation` | "Emergency/Casualty" (today `consultation`) **drops out** if retyped `emergency`. Emergency already has its own `EmergencyCase` flow, so verify the casualty-as-OPD path before retyping. |
| Lab | `type = investigation` | Radiology retyped `radiology` leaves the lab list — usually **desired** (separates lab from imaging), but confirm. |
| Investigation items / products | `[investigation, radiology]` | Already covers both — **safe**. |
| Procedure consumables / emergency procedure picker | `type = procedure` | Theatre retyped `theatre` drops out of procedure pickers — intended split, but audit. |
| Pharmacy product linking | `type = pharmacy` | Unchanged. |

**Rule to preserve (from prompt):** department type ≠ service type. A *service*
(via `ServiceType`/category) stays the billable item; department type only routes
the service to the right workflow/dashboard. Do **not** replace service-type
checks with department-type checks.

**Action:** before any retype, broaden single-value `where('type', X)` filters to
**type groups** (e.g. a `DepartmentType::clinicalConsultationTypes()` helper) so a
remap doesn't silently empty a queue.

---

## 12. Billing impact

- Services carry both `department_id` and `department_type` (`service_catalog`),
  plus their own `ServiceType` category — billing routing is **service-driven**,
  which is correct.
- Emergency consultation billing is created in `EmergencyCaseService` /
  `VisitService::createConsultationBilling()` by finding a `ServiceType::CONSULTATION`
  service — partially **convention-based**; should become explicitly configurable
  per the prompt (don't hardcode a magic consultation service).
- Radiology vs laboratory currently share `investigation` billing routing; theatre
  vs procedure share `procedure`. Splitting the department types enables splitting
  these later, but **billing does not need to change in Phase 1**.
- Pharmacy is product/dispensing-billed (not service-billed) — leave as is.
- `finance` should be an operational/owner type, **not** a clinical billable
  service department.

---

## 13. Permissions & user-department impact

- `users.department_id` is **single** (`Department::users()` is `hasMany`; no
  `department_user` pivot). One department per user today.
- Dashboard/menu context derives from `$user->department?->type`.
- Permissions/roles are independent of department type (Spatie). The expansion
  does **not** touch the permission model.

**Suggested logic (matches prompt):** single dept → use its type for context;
admin/super-admin → all; **no dept → role fallback → generic**. Multi-department
support would need a new `department_user` pivot + a "current department" switcher
(defer to a later phase).

---

## 14. Reporting & statistics impact

- Existing: revenue/expense **by department** (`admin.accounting.reports.*-by-department`)
  — keyed on `department_id`, not type.
- **Gap:** no department-**type** rollups (e.g. all emergency revenue, all
  investigation volume). No per-type metric registry.
- **Future direction:** a `DepartmentMetricsRegistry` keyed by department type,
  generic metrics first; dedicated metrics where a module already exists
  (consultation, emergency, pharmacy, investigation, theatre, inpatient, blood_bank).

---

## 15. Localisation impact

- There is **no** `lang/en/departments.php` or `lang/fr/departments.php`.
- `DepartmentType::label()` is **hardcoded English** and incomplete (8 arms).
- `DepartmentType::translatedLabel()` resolves `__('statuses.default.{value}')`,
  and `statuses.default` is **missing** keys for: theatre, nursing, inpatient,
  maternity, blood_bank, mortuary, ambulance, records, finance, stores (and most
  new values) — so it would render raw keys, not labels.

**Files to add/change (Phase 1, not now):**
- create `lang/en/departments.php` + `lang/fr/departments.php` with all 19 type labels (+ later dashboard/menu labels);
- repoint `translatedLabel()` to `__('departments.types.{value}')`;
- make `label()` exhaustive (or delegate to `translatedLabel()`).

---

## 16. Backward-compatibility risks

1. **Non-exhaustive `match` fatals** (§1 breaks #1–#6) — highest risk; must be
   fixed *before* any new type is assigned.
2. **Retype-driven query drift** (§11) — reclassifying departments silently
   changes which workflow filters include them.
3. **`service_catalog.department_type` drift** — denormalised copies can fall out
   of sync with the parent department after a remap.
4. **Demo/seed data** encodes old generic mappings; re-seeding mid-stream could
   move departments between types unexpectedly.
5. **Historical rows** referencing departments are by `department_id` (stable), so
   history is preserved as long as we **don't delete old type values**.

---

## 17. Proposed safe migration / backfill plan

1. **Stop the bleeding first** — make every `DepartmentType` consumer exhaustive
   or add a safe `default`: `label()`, `color()`, `toVisitStatus()`, and
   `DepartmentDashboardResolver::resolveKey()`. (No data change; pure code.)
2. Keep `departments.type` as **string** (already true) — no schema migration.
3. Add `lang/{en,fr}/departments.php`; repoint `translatedLabel()`; complete `label()`.
4. (Optional) validate service `department_type` with `Rule::enum(DepartmentType::class)`.
5. Update `DepartmentSeeder` + `DepartmentFactory` to the precise types (§6).
6. Add a **dry-run backfill command** that suggests, but does not auto-apply,
   precise types for existing rows by name/code/module heuristics:
   ```bash
   php artisan departments:backfill-types --dry-run
   php artisan departments:backfill-types --apply
   ```
   Output columns: `id | name | code | old_type | suggested_type | confidence | reason | action`.
   - Only auto-apply **high-confidence** matches; list low-confidence rows for
     manual review; leave unknowns unchanged (or `support`/`administrative` only
     after review).
   - Re-sync `service_catalog.department_type` for remapped departments.
7. Add an **"unmapped / ambiguous departments" report** (rows still on a generic
   type, blank type, or matching multiple rules).
8. **Do not** delete old type values; **do not** run destructive migrations.

---

## 18. Suggested mapping rules (propose, do not apply)

```
Emergency / Casualty                         => emergency
OPD / Consultation / Consulting / *Clinic    => consultation
Laboratory / Lab                             => investigation
Radiology / X-Ray / Ultrasound / Imaging     => radiology
Theatre / Surgery / Operating Room           => theatre
Procedure Room / Minor Procedure             => procedure
Treatment Room / Dressing / Injection / Physio => treatment
Nursing Station / MAR / Ward Nursing         => nursing
Pharmacy / Dispensary                        => pharmacy
Ward / Admission / ICU / NICU / Inpatient    => inpatient
Maternity / Delivery / Antenatal / Postnatal => maternity
Blood Bank / Blood Storage                   => blood_bank
Mortuary                                     => mortuary
Ambulance / Transport                        => ambulance
Records / Folder / Archive                   => records
Billing / Cashier / Accounts / Claims / Finance => finance
Stores / Procurement / Inventory / Warehouse => stores
Maintenance / IT / Laundry / Security / CSSD / Housekeeping => support
HR / Admin / Management / Settings           => administrative
```

**Ambiguities to flag during backfill:**
- "Theatre / Procedures" (seed) — name spans **theatre** *and* **procedure**; needs a human call (or split into two departments).
- "Antenatal / Postnatal", "Family Planning" — **maternity** vs **consultation**.
- Wards typed `administrative` today — clearly **inpatient**, but confirm nursing-station vs ward distinction.
- "Physiotherapy" — **treatment** vs **nursing**.
- Any department with services spanning multiple types (e.g. a combined clinic).

---

## 19. Unmapped / ambiguous departments (from seed/demo data)

Discoverable from `DepartmentSeeder` (live data not inspected — no DB query run):
generic-typed rows that need reclassification: **Emergency/Casualty, Radiology,
Ultrasound, Theatre/Procedures, all Wards, ICU, NICU, Maternity Ward, Antenatal/
Postnatal, Family Planning, Records, Billing**. Tests additionally mistype an
"Emergency Department" as `support` and a "Theatre" as `procedure`.

A live `departments` query (`type` distinct counts) should be run at the start of
Phase 1 to produce the authoritative unmapped list.

---

## 20. Recommended implementation phases

- **Phase 1 — Canonicalisation & safety (do first):** exhaustive enum methods +
  resolver, translations (`lang/*/departments.php`), service-type validation,
  seeder/factory fixes, dry-run backfill command, unmapped-department report.
- **Phase 2 — Department-aware dashboard registry:** complete `type→key` map,
  generic fallback, admin override/preview (already supported via `?as=`),
  current-department context.
- **Phase 3 — Department-aware menu profiles:** type-based grouping/prioritisation
  on top of permissions/modules; optional department context switcher.
- **Phase 4 — Workflow routing cleanup:** broaden `where('type', X)` filters to
  type groups; split emergency/casualty, radiology vs investigation, theatre vs
  procedure; map wards→inpatient/nursing/maternity.
- **Phase 5 — Department metrics & reports:** `DepartmentMetricsRegistry`,
  per-type rollups and widgets.

---

## 21. Minimal verification run (this phase)

```bash
git diff --check          # clean (no whitespace/conflict markers)
php artisan route:list     # department & my-dashboard routes confirmed present
```

Grep/search inspection performed across `app/`, `database/`, `resources/`,
`routes/`, `lang/`, `tests/`, `config/`. **`php artisan test` was not run; no
migrations were run; no data was changed.**

---

## 22. Acceptance checklist

- [x] All current department-type definitions identified (single enum).
- [x] All direct `DepartmentType` usages listed (§4).
- [x] Source-of-truth assessment (one canonical source; half-migrated).
- [x] Database impact documented (§5) — string column, no schema change needed.
- [x] Dashboard impact (§9) and menu impact (§10).
- [x] Workflow routing (§11), billing (§12), permissions/user-department (§13),
      reporting (§14), localisation (§15) impacts.
- [x] Backward-compat risks (§16) + safe backfill plan (§17).
- [x] Suggested mapping rules (§18) + ambiguous/unmapped list (§19).
- [x] Recommended phases (§20). No production data changed; no full suite run.
