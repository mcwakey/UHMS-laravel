# UI Phase 7 — Responsive, Accessibility & Print Compliance

Full-system sweep. Much of the foundation already existed (Phase 2 status badges = text + dark-text contrast; Phase 4 friendly errors; Phase 5 `<x-data-table>`/`<x-print-layout>`). This pass closed the two largest concrete gaps — **icon-only button labels** and **unwrapped tables** — and audited the rest.

## 1. Files audited
534 Blade views + 18 Vue. Concrete metrics measured up front: 403 `<table>` (≈77 candidates without a responsive wrapper, mostly print/layout/already-wrapped); **~432 icon-only buttons without `aria-label`/`title`**; 9 print views.

## 2. Files changed
**226 Blade views** received `aria-label`/`title` on icon-only buttons; **9 files** had data tables wrapped in `.table-responsive`. No business logic, routes, or permissions changed.

## 3. Responsive issues fixed
- **16 data tables** in 9 files wrapped in `.table-responsive` (`admin/permissions/index`, `admin/product-stock/receive`, `admissions/create`, `admissions/discharge`, `appointments/show`, `lab/_consumables`, `lab/_result_modal`, `partials/consultation-clinical-sections`, `triage/create`) — they now scroll horizontally instead of breaking layout on small screens.
- The rest of the system already wraps data tables (`.table-responsive` or `<x-data-table>`). KPI cards already use responsive grids (`col-6 col-md-4 col-xl-*`); filter rows use `row g-2`/`g-3` (wrap on mobile).

## 4. Accessibility issues fixed
- **~385 icon-only buttons/links** (View, Edit, Delete, Print, Search, Filter, Actions dropdown, Close, etc.) gained `aria-label` + `title` via a careful transform that derives the label from the Tabler icon (mapped dictionary + name-derived fallback) and **skips** any element already having `aria-label`/`title`/`aria-hidden`. Remaining unlabeled icon-only buttons: **47** (down 89%) — edge cases with unusual markup, listed for follow-up.
- Status is already conveyed by **text** (not colour-only) via `<x-status-badge>` (Phase 2), which also adds `text-dark` on `bg-warning`/`bg-info` tints for contrast.

## 5. Icon-only buttons fixed
See §4 — 385 across 226 files, e.g. `<a aria-label="View" title="View" …><i class="ti ti-eye"></i></a>`, `<button aria-label="Actions" …><i class="ti ti-dots-vertical"></i></button>`.

## 6. Print pages
`<x-print-layout>` exists and is tested (hospital header, patient/visit context, generated timestamp, signatures, `d-print-none` controls, black-on-white CSS). Existing bespoke print views (`reports/*-pdf`, `print-consultation/-prescription/-lab-report`) already have print CSS and hospital headers — **left intact** (converting risks breaking working print routes). Recommended for gradual adoption (simplest first).

## 7. Tables wrapped/fixed
16 (see §3).

## 8. Clinical document readability
Consultation summary, visit preview, and clinical timelines already use document-style containers with patient headers, authorship, and wrapping text (Phase 3). The wrapped `consultation-clinical-sections` and `triage/create` tables improve their small-screen behaviour. No critical clinical data hidden.

## 9. Modal issues
Stuck-backdrop cleanup is centralised in `layouts/app.blade.php` (Phase 4). Modals retain titles, in-modal validation, and viewport fit. The Phase 6 global double-submit guard also applies inside modal forms.

## 10. Vue/Inertia
18 components already reuse `Can.vue` + `UhmsConfirmDialog.vue` and Bootstrap classes — no parallel design language. No change needed.

## 11. Pages intentionally skipped (and why)
- **Existing print views** — bespoke, already print-compliant; conversion risks breaking print routes. Documented for gradual `<x-print-layout>` adoption.
- **Specialised clinical grids** (MAR chart, theatre board) — intentionally designed; verified they live inside scroll containers and were not forced into generic wrappers.
- **47 remaining icon-only buttons** — unusual markup (dynamic icon classes, multi-icon buttons); flagged for the Phase 8 audit tool to track.

## 12. Remaining TODOs
Label the 47 remaining icon-only buttons; gradual `<x-print-layout>` adoption; periodic responsive spot-checks. Tracked in `docs/UI_UX_REMAINING_TODOS.md` and now enforceable via the Phase 8 `ui:audit` command.

## 13. Tests run
All 534 views compile after the transforms. **67 tests pass** (UI components, Phase 5 components, high-risk reason, error handling, statistics, ward/admission, reports) — no regression from the 226-file accessibility sweep.

## 14. Manual verification checklist
On desktop + a narrow viewport, for one list/detail/print page per module: tables scroll (not break); icon buttons announce a label (hover shows `title`); statuses show text; filters wrap; modals fit; error/disabled-module pages render; print pages stay black-on-white with header + timestamp.
