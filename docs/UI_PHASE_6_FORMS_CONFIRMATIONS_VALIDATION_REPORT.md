# UI Phase 6 — Forms, Confirmations, Validation, Feedback & Safe-Action UX

Full-system UX safety sweep. Several Phase 6 goals were **already satisfied** by earlier phases (Phase 4 raw-error shield; Phase 5 confirm-form + reasons), so this pass closed the genuine remaining gaps and added a global safety net, with the highest-value items verified by tests.

## 1. Files audited
- **534 Blade views** (full tree) + 18 Vue components.
- Backend validation points: high-risk controllers (payment reverse/refund, stock adjustment/return, patient merge, triage override, blood issue/crossmatch).
- Existing coverage measured: **23 views** already use field-level `@error`, **22** use `is-invalid` (the key create/edit forms — e.g. `patients/create` has 49 field-level validation hooks already).

## 2. Files changed
- `resources/views/layouts/app.blade.php` — global double-submit guard.
- `app/Http/Controllers/Admin/PatientMergeController.php` — merge `reason` now **required** (friendly message).
- `app/Http/Controllers/Admin/EmergencyTriageController.php` — **triage override now requires a reason** (conditional guard).
- `resources/views/patients/merge/compare.blade.php` — merge reason: required indicator + `@error`/`is-invalid` + old-input.
- `resources/views/patients/show.blade.php` (remove insurance, remove emergency contact), `resources/views/lab/process.blade.php` (cancel request) — native `confirm()` → `<x-confirm-form>`.
- `tests/Feature/HighRiskActionReasonTest.php` (new).

## 3. Forms updated with field-level validation
- `patients/merge/compare` reason field (required `*`, `is-invalid`, `invalid-feedback`, `old()`).
- **Already compliant (verified, not re-touched):** the major create/edit forms — `patients/create`, etc. — already use `@error('field') is-invalid @enderror` + `invalid-feedback` + `old()` throughout. Field-level validation is the established pattern for the vendor-template create forms.

## 4. High-risk actions on `<x-confirm-form>`
Across Phases 5 + 6, **17 destructive/high-risk actions** (18 view files) now use the confirmed form: billing (cancel invoice ×2), claims (remove item), accounts (delete entry), **admin/modules (disable/enable)**, icd-codes (delete), **procedures (cancel — reason)**, store (purchase-returns cancel ×2 + post, requisitions cancel/issue/acknowledge, transfers cancel), roles (delete), departments, designations, **MAR (stop order — reason)**, **patients (remove insurance, remove emergency contact)**, **lab (cancel request)**, insurance (toggle). Native `confirm()` forms reduced from 26 → **10** (the remainder documented in §10).

## 5. Actions now requiring a reason
| Action | Where enforced |
|---|---|
| **Patient merge** | `PatientMergeController` `reason` required + UI field validation (NEW) |
| **Triage override** | `EmergencyTriageController` conditional guard when final ≠ computed category (NEW) |
| Procedure cancel | `<x-confirm-form require-reason>` (Phase 5) |
| MAR stop medication order | `<x-confirm-form require-reason>` (Phase 5; replaces a hard-coded reason) |
| Payment reversal | already enforced — backend `reason` required + dedicated modal with required reason |
| Stock adjustment / return | already enforced — controller `reason` required |
| Incompatible / emergency blood release | already enforced — `BloodIssueService` requires `emergency_release_reason` |

## 6. Feedback improvements
- All converted destructive actions now show a **SweetAlert2 confirmation** (native `confirm()` fallback) before submit.
- Existing controllers already return `->with('success'|'error', …)` flash messages rendered in the layout's Bootstrap alert area; the Phase 4 shield converts any DB error into a friendly flash. No silent failures on the converted paths.

## 7. Loading / double-submit protection
**Global guard** in `layouts/app.blade.php`: on any real (non-prevented) form submit, submit buttons are disabled and show a spinner, preventing a quick double-click from creating **duplicate payments, dispenses, stock movements, blood issues, or medication administrations**. Opt-out via `data-no-loading`; `data-loading-text` customises the label; a 12s safety net + `pageshow` handler re-enable after navigation/back. Skips already-cancelled submits (`event.defaultPrevented`) so it never fights the confirm dialogs.

## 8. Disabled / locked action explanations
`<x-confirm-form>` supports `disabled` + `disabledReason` (shown via `title`). Existing pages already gate actions on workflow state (`@if(!in_array($status,[…]))`, `can_reverse`, `is_editable`) and permissions (`@can`). No always-403 buttons were introduced.

## 9. Backend validation guards added
- `PatientMergeController::store` — `reason` required (+ friendly messages for `reason`/`confirmed`).
- `EmergencyTriageController::store` — override-reason guard before the service write.
- Verified the recurring offenders already validate up front: `StockController` (adjustment/return: `product_id`, `stock_location_id`, `reason` all `required|exists`), `PaymentController` (reverse: `reason` required). The Phase 4 `QueryException` shield remains the production net so DB constraints are never the first user-facing error.

## 10. Pages/actions skipped (and why)
- **10 remaining native `confirm()` forms** (`store/purchase-orders` cancel/remove-item, `theatre/rooms` block, `admin/analyzers` ×2, `admin/investigation-catalogue`, `admin/procedure-catalogue` ×3, `lab/tests`): pattern established — mechanical follow-up. **`consultations/show` is excluded** (its `onsubmit` chains extra JS `saveTabBeforeSubmit` and must not be blindly converted).
- **Field-level validation on simple modal/inline forms** that currently rely on HTML5 `required` + the summary alert: low risk, deferred; the major create/edit forms already have full field-level validation.
- **Payment reversal** kept on its existing dedicated modal (robust, required reason) rather than `<x-confirm-form>` — already compliant.
- **Vue/Inertia (18 files):** already use `Can.vue` + `UhmsConfirmDialog.vue` and data-driven validation; no parallel design introduced. No change needed.

## 11. Tests run
- **`HighRiskActionReasonTest`** (4, NEW, all pass): patient-merge requires reason / succeeds with reason; triage override requires reason / no reason needed without override.
- Existing UI/error suites still pass (`UiComponentsTest`, `UiPhase5ComponentsTest`, `ErrorHandlingTest`). All **534 views compile**.

## 12. Manual verification checklist
- [ ] Patient merge without a reason → blocked with a field error; with a reason → request created.
- [ ] Emergency triage: change final category vs computed → reason required; same category → no reason needed.
- [ ] Reverse a payment → modal asks for a required reason; reversal logged.
- [ ] Stock adjustment / return → reason required.
- [ ] Cancel invoice / procedure / lab request / transfer / requisition → SweetAlert2 confirmation.
- [ ] Disable a module → confirmation.
- [ ] Double-click a Save/Pay/Dispense button → second click is ignored (spinner shown).
- [ ] Trigger a DB error on a form (debug off) → friendly message, no SQLSTATE.

## 13. Remaining TODOs
Tracked in `docs/UI_UX_REMAINING_TODOS.md`: finish the 10 remaining native-`confirm()` forms; field-level validation on the remaining modal/inline forms; optionally persist the discarded `reason` on `modules.toggle`/`roles.destroy` controllers if reasons are wanted there.
