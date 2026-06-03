# UI Phase 4 — Friendly Error Handling, Disabled-Module Page & Raw-Error Shielding

Goal: users never see SQLSTATE, stack traces, table/column names, or server paths. Technical detail is always logged. Authentication, authorization and validation behaviour is unchanged.

## 1. Error pages created/updated
A new **self-contained error layout** drives all pages so they render even when the main app layout's data (sidebar, notifications, permissions) is itself failing.

| File | Purpose |
|---|---|
| `resources/views/layouts/error.blade.php` | Lightweight Bootstrap 5 + Tabler layout (icon, code, heading, message, actions, support) — no app-data dependency |
| `resources/views/errors/partials/actions.blade.php` | Reusable Back / Reload / Dashboard / Login buttons (route-guarded) |
| `resources/views/errors/403.blade.php` | "Access denied" — Back, Dashboard. Surfaces a custom abort reason **only** when short and free of technical markers |
| `resources/views/errors/404.blade.php` | "Page not found" — Back, Dashboard |
| `resources/views/errors/419.blade.php` | "Session expired" — Reload, Login |
| `resources/views/errors/500.blade.php` | "Something went wrong" — Back, Reload, Dashboard + "details have been logged" note |
| `resources/views/errors/503.blade.php` | "System temporarily unavailable" — Reload, Dashboard |
| `resources/views/errors/module-disabled.blade.php` | Friendly disabled-module page (module name + description) |

All pages: centred Bootstrap card, Tabler icon, readable message, action buttons, responsive, accessible (`aria-hidden` on decorative icons, text labels on buttons). No raw stack trace / SQL.

## 2. Disabled-module page
`errors/module-disabled.blade.php` shows the human-readable **module name** and optional **description**, with Back + Dashboard actions. Returned with **HTTP 403** (the module exists but access is forbidden while disabled) — never a generic 500 or raw middleware exception.

## 3. Exception handling changes — `bootstrap/app.php`
Added a render hook for **`Illuminate\Database\QueryException`** that:
- **Always logs** full technical context (exception class, message, SQLSTATE, user_id, route, URL, method, IP, truncated user-agent) — never passwords/tokens.
- In **debug** mode → returns `null` so developers keep Laravel's full debug page.
- In **production**:
  - JSON request → `{ "message": "<friendly>" }`, HTTP 500.
  - Web **form submit** (non-GET, has session) → `back()->withInput()->with('error', <friendly>)` so the user stays on the form.
  - Web **GET** → friendly `errors.500` page.
- Friendly message is **context-aware** by URL: stock/store/inventory, billing/invoice/payment, or a generic fallback.

Laravel's built-in mapping already routes the other exceptions to the new friendly views: `AuthorizationException`→403, `ModelNotFoundException`/`NotFoundHttpException`→404, `TokenMismatchException`→419, `AuthenticationException`→login redirect (existing Inertia-aware handler kept), generic→500. No `app/Exceptions/Handler.php` exists (Laravel 11/12 style) — all wiring is in `bootstrap/app.php`.

## 4. Module middleware changes — `EnsureModuleEnabled`
- Disabled module now renders the **friendly page** (web) / **clean JSON** (API) at **403**, instead of `abort(404)` / JSON 503.
- Uses the new `ModuleService::find($slug)` to show the real module name/description.
- Super-Admin override (`modules.override_disabled`) is **preserved**; direct URL access remains blocked; no stack trace is shown.

## 5. High-risk validation
Inspected the recurring offenders (stock receive/adjust/transfer/return, requisitions, billing). `StockController` already validates `product_id` / `stock_location_id` with `required|exists:` rules (and the other stock controllers similarly). **No glaring gap found** — DB constraints are not the first line of defence. The new QueryException shield is the production **safety net** for any integrity error that still slips through (e.g. a future code path), turning it into a friendly message + a log entry rather than raw SQL.

## 6. Raw-error shielding approach
Defence in depth:
1. Controllers validate required foreign keys up front (existing).
2. `QueryException` render hook logs + shields in production (new).
3. Friendly `errors.500` view never echoes the exception; `errors.403` only surfaces a custom reason after a technical-marker safety check (`SQLSTATE|Exception|::|\\|/var/|.php|vendor|column|table`).
4. Flash errors use the existing Bootstrap dismissible alert area (`session('error')` in `layouts/app.blade.php`) — the shield's friendly string, never `$e->getMessage()`.

## 7. Files modified
- `bootstrap/app.php` (QueryException shield)
- `app/Http/Middleware/EnsureModuleEnabled.php` (friendly disabled-module response)
- `app/Services/ModuleService.php` (`find()`)
- `resources/views/layouts/error.blade.php` (new)
- `resources/views/errors/{403,404,500}.blade.php` (rewritten), `{419,503,module-disabled}.blade.php` (new), `errors/partials/actions.blade.php` (new)
- `tests/Feature/ErrorHandlingTest.php` (new)
- `docs/UI_PHASE_4_ERROR_HANDLING_REPORT.md` (this), `docs/UI_UX_REMAINING_TODOS.md` (updated)

## 8. Tests run
`tests/Feature/ErrorHandlingTest.php` — **11 passing**:
1. 403 friendly message · 2. 404 friendly · 3. 419 friendly · 4. 500 hides technical detail · 5. error pages render without authenticated layout data · 6. missing route → friendly 404 (HTTP) · 7. unauthorized → friendly 403 (HTTP) · 8. disabled module → friendly page (HTTP 403) · 9. disabled module JSON → clean message · 10. QueryException shielded + logged + no SQL leak in production · 11. validation still returns field errors.

Regression: 66 prior tests (UI, Blood Bank, Reports, Ward/Admission, Billing, Statistics) still pass; all 534 views compile.

## 9. Remaining TODOs
- Optional: a dedicated maintenance page wired to `php artisan down` (503 view is ready; Laravel serves it automatically).
- Optional: route the shield's friendly flash through a shared toast for async (Inertia) form posts.
- Continue the Phase 2/3 header/badge rollout tracked in `docs/UI_UX_REMAINING_TODOS.md` (separate from Phase 4).

## 10. Manual verification checklist
- [ ] Hit a route without permission → friendly 403 (no raw exception).
- [ ] Open a non-existing patient/visit URL → friendly 404.
- [ ] Submit an expired-session form → friendly 419.
- [ ] Disable a non-core module, visit its route → friendly disabled-module page (403); API → clean JSON.
- [ ] With `APP_DEBUG=false`, trigger a DB integrity error on a stock/billing form → friendly flash/500, **no SQLSTATE**; confirm `storage/logs` has the full detail.
- [ ] Trigger a validation error → field-level messages still show.
