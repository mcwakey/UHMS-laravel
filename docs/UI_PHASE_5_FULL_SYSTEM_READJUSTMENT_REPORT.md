# UI Phase 5 — Full-System Readjustment Report

## 1. Why this readjustment was needed
Phase 5 created the six reusable components and adopted them only in **representative** pages. This pass audits the **entire** frontend and rolls them out further, prioritising the highest-value, lowest-risk conversions, while documenting where conversion would change a page's visual and is therefore deferred.

## 2. Components already created (before this pass)
`<x-stat-card>`, `<x-filter-bar>`, `<x-data-table>`, `<x-action-menu>`, `<x-confirm-form>`, `<x-print-layout>` — all in `resources/views/components/`, tested in `tests/Feature/UiPhase5ComponentsTest.php`, documented in `docs/UI_COMPONENT_STANDARDS.md`. **No component was modified or duplicated** this pass.

## 3. Scope audited
- **Blade views audited:** 534 (full tree).
- **Vue/Inertia components audited:** 18 (status display is data-driven; they reuse `Can.vue`/`UhmsConfirmDialog.vue` — no Blade-component swap applicable; see §13).
- Searched for: KPI cards (`border-start border-* border-3`, 24 files), GET filter forms (96), standard tables, action dropdowns (16), native `onsubmit="return confirm()"` forms (26), print views.

## 4. Component adoption (files, before → after this pass)
| Component | Before | After |
|---|---|---|
| `<x-confirm-form>` | 2 | **16** |
| `<x-stat-card>` | 2 | 2 |
| `<x-filter-bar>` | 1 | 1 |
| `<x-data-table>` | 1 | 1 |
| `<x-action-menu>` | 1 | 1 |
| `<x-print-layout>` | 0 (tested) | 0 (tested) |

This pass concentrated on **`<x-confirm-form>`** because it is the safety-critical, prompt-emphasised component and converts without visual regression.

## 5. Files already compliant before this pass (not touched)
`statistics/dashboard`, `statistics/show` (stat-card, filter-bar, empty-state, data-table empty), `blood-bank/units` (data-table), `insurance/index` (action-menu + confirm-form). Detected and left intact.

## 6. Files changed in this pass
**`<x-confirm-form>` adopted (14 destructive/high-risk actions across the system):**
- `billing/invoices/index` (cancel invoice), `billing/invoices/show` (cancel invoice)
- `claims/show` (remove item)
- `accounts/entries/index` (delete entry)
- `admin/icd-codes/index` (delete code)
- `admin/modules/index` (**disable/enable module** — disable now properly confirmed)
- `admin/procedures/schedule` (cancel procedure — **require-reason**)
- `store/purchase-returns/show` (cancel ×2, post-to-ledger)
- `store/stock-requisitions/show` (cancel, issue, acknowledge)
- `store/transfers/index` (cancel transfer)
- `roles/index` (delete role)
- `departments/index` (delete), `designations/index` (delete)
- `medication-administration/admission-show` (**stop medication order — require-reason**; replaces a hard-coded reason so clinicians enter the real one)

Each conversion preserves the action URL, HTTP method (CSRF + spoofing), `@can` guards, and adds a SweetAlert2 confirmation (native fallback) — **destructive actions are now harder to trigger, not easier**.

## 7–12. Pages updated per component
- **`<x-confirm-form>`:** the 14 actions in §6 (now 16 files total incl. Phase 5).
- **`<x-stat-card>`:** statistics dashboard/show (Phase 5) — see §13 skips.
- **`<x-filter-bar>`:** statistics/show (Phase 5) — see §13 skips.
- **`<x-data-table>`:** blood-bank/units (Phase 5) — see §13 skips.
- **`<x-action-menu>`:** insurance/index (Phase 5) — see §13 skips.
- **`<x-print-layout>`:** none adopted yet (component ready + tested) — see §13.

## 13. Pages intentionally skipped (and why)
- **KPI cards → stat-card (≈22 files: dashboards + `reports/*`):** these carry per-card nuances that `<x-stat-card>` does not reproduce 1:1 — the **₵ currency symbol** (vs the component's `GHS`), **coloured value text** (`text-success` etc.), `shadow-sm`, and **inline icon+subtitle** styling (`<small class="text-info"><i…>Scheduled</small>`). Converting would alter the visuals → a redesign. Left as-is to preserve appearance; `<x-stat-card>` remains canonical for **new** KPI cards (and is live in `statistics/*`).
- **Filter forms → filter-bar (≈96):** most are bespoke (varying Apply/Reset placement, advanced multi-field layouts, some with no Reset). `<x-filter-bar>` standardises button placement, which is a minor layout change per page; deferred to avoid mass visual churn — convert as pages are touched.
- **Standard tables → data-table (many):** safe but each needs head/body/pagination preserved exactly; converted the cleanest representative (`blood-bank/units`). Complex clinical grids (MAR chart, theatre board, consultation/visit-preview timelines, charts) are explicitly **not** converted.
- **Action dropdowns → action-menu (15):** trigger markup matches, but items are wrapped in `<li>` (the component uses a flat `<div class="dropdown-menu">`); stripping `<li>` per item is per-file work, deferred.
- **`<x-print-layout>`:** existing print views (`reports/*-pdf`, `print-consultation/-prescription/-lab-report`) are bespoke full documents; converting risks breaking working print routes. Component is ready+tested — adopt simplest documents first.
- **Remaining native-confirm forms (12):** `lab/process`, `lab/tests`, `patients/show` (insurance/contact removal), `store/purchase-orders` (cancel/remove-item), `theatre/rooms` (block), `admin/analyzers` (×2), `admin/investigation-catalogue`, `admin/procedure-catalogue` (×3). Pattern established — mechanical follow-up. `consultations/show` is **excluded** because its `onsubmit` chains extra JS (`saveTabBeforeSubmit`) and must not be blindly converted.

## 14. Broken / inconsistent previous usage found
One self-inflicted issue during this pass — a duplicate `@endcan` introduced in `billing/invoices/show` while converting the cancel form — caught and fixed immediately (views compile). No pre-existing broken component usage found.

## 15. New component props added
None. The existing component APIs covered every conversion (incl. `require-reason`, `button-class="dropdown-item …"`, icon-only buttons via empty `button-label`).

## 16. Remaining TODOs
Tracked in `docs/UI_UX_REMAINING_TODOS.md`. Priority: (1) finish the 12 remaining native-confirm forms; (2) `<x-stat-card>` for **new** KPI cards (don't retro-fit ₵/coloured ones); (3) `<x-filter-bar>`/`<x-data-table>`/`<x-action-menu>` as pages are touched; (4) capture the discarded `reason` on `modules.toggle`/`roles.destroy` controllers if reasons are wanted there (a backend change, out of UI scope).

## 17. Tests run
- `UiComponentsTest` (12) + `UiPhase5ComponentsTest` (9) — **all pass**.
- Regression: **57 tests pass** (UI, Billing, Blood Bank, Statistics, Ward/Admission). **All 534 views compile** after the conversions.

## 18. Manual verification checklist
For one list/detail page per module, confirm: cards render, filters work, tables paginate, **destructive actions show the SweetAlert2 confirmation (and ask for a reason where required)**, permissions preserved, print pages still print, no layout break, no business-logic change. Modules to spot-check: Dashboard, Patients, Visits, Consultation, Emergency, Admission, MAR, Pharmacy, Billing, Investigations, Procedures/Theatre, Blood Bank, Stock, Procurement, Supplier Ledger, Reports, Statistics, Notifications, Logs, Roles/Permissions/Modules, Settings.
