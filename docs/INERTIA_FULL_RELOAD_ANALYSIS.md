# UHMS — Inertia Full-Reload Analysis & Permanent Fix Plan

> **Status:** Diagnosis complete · 2026-05-18
> **Companion docs:** [UNIFIED_INVENTORY_IMPLEMENTATION_PLAN.md](UNIFIED_INVENTORY_IMPLEMENTATION_PLAN.md)
> **Bridge notes:** `/memories/repo/inertia-legacy-bridge.md`

---

## 0. TL;DR

Today the app is **not** an Inertia SPA — it's a Blade app wrapped in an Inertia shell.
Every Blade view is converted into `Inertia::render('Legacy/BladePage', { html, styles, scripts })`
by `ConvertBladeViewsToInertia` middleware, and `resources/js/Pages/Legacy/BladePage.vue`
re-injects the HTML, styles, and scripts into the page.

**The shell is wired correctly. The visible "full reload" symptoms come from four leaks**:

| # | Leak | Where | Impact |
|---|------|-------|--------|
| L1 | **Every form is opt-in** to Inertia, and **zero** Blade form has opted in | `BladePage.vue` `handleSubmit` requires `data-inertia`; grep shows **0 matches for `data-inertia`** anywhere | Every save/update/delete in the app full-reloads |
| L2 | Hard-coded `window.location.href = ...` and `window.location.reload()` in Blade scripts | 3 confirmed call sites (billing, lab, consultations) + 1 `onclick="location.reload()"` (queue board) | Specific button clicks bypass Inertia |
| L3 | Silent fallback to `window.location.href` on **any** Inertia visit error | `BladePage.vue` `handleClick.onError`, `inertia.js` `router.on('invalid')` | Transient errors hard-reload instead of recovering |
| L4 | Anchors that should be buttons | `<a href="javascript:void(0)" data-bs-toggle="modal">`, print/download anchors without `data-no-inertia` | Wasted `router.visit` cycles; some modal anchors only work because of a defensive skip in the bridge |

Plus one architectural observation:

> **R0 — Even Blade→Blade navigation that "works" via Inertia today still costs a full Blade render server-side**, because every controller still returns `view(...)`. The middleware re-wraps that into an Inertia response. So *perceived* SPA navigation still pays the full HTML cost; only the browser-side re-parse is saved.

Permanent fix = close all four leaks (Phase A), then progressively migrate hot pages to true Inertia (Phase B). Phase A alone eliminates the user-visible "the page reloaded" symptom across the app.

---

## 1. Current architecture

### 1.1 Boot

- `resources/js/inertia.js` — `createInertiaApp` with `import.meta.glob('./Pages/**/*.vue')`, progress bar enabled.
- `resources/views/app.blade.php` — `@inertiaHead` in `<head>`, `@inertia` in `<body>`, single `@vite(['resources/js/inertia.js'])`.
- `bootstrap/app.php` registers two middleware on the `web` group, in this order:
  1. `HandleInertiaRequests` — shares `auth.user`, `flash.*`, `csrf_token`.
  2. `ConvertBladeViewsToInertia` — wraps Blade HTML into `Inertia::render('Legacy/BladePage', {...})`.

### 1.2 Conversion middleware

- Triggers when the response is `GET`, 2xx, content-type `text/html` (or empty), and the body contains the markers placed by `resources/views/layouts/app.blade.php`:
  - `<!--UHMS_LEGACY_LAYOUT-->` — opt-in flag
  - `<!--UHMS_LEGACY_BODY_START-->` / `..._END-->` — main content region
  - `<!--UHMS_LEGACY_STYLES_START-->` / `..._END-->` — `@stack('styles')`
  - `<!--UHMS_LEGACY_SCRIPTS_START-->` / `..._END-->` — `@stack('scripts')`
- Sends those four strings + `title` + `url` as props to `Legacy/BladePage`.

### 1.3 BladePage.vue (only Inertia page in the app today)

Responsibilities:
1. Render `props.html` into a `v-html` div.
2. Reinject `props.styles` into `<head>` (cleared on each visit via `data-uhms-legacy-style*` markers).
3. Reinject `props.scripts` into `<body>` as real `<script>` elements (so they actually execute).
4. **Intercept `<a>` clicks**: same-origin, plain left-click, no `target`, no `download`, no `data-bs-toggle`, no `data-no-inertia` → `router.visit(href)`.
5. **Intercept `<form>` submits**: only if the form has `data-inertia` (opt-in).

### 1.4 What the bridge deliberately does NOT do

- Does not intercept forms by default (per the bridge memory note — preventing double-submit with jQuery/Bootstrap handlers).
- Does not rewrite the DOM beyond style/script re-injection.
- Does not intercept anchors with `data-bs-toggle`, `target=_blank`, downloads, or anchors with inline `onclick` handlers that call `preventDefault`.

---

## 2. Audit findings

### 2.1 Routes & controllers

- **Inertia pages (`Inertia::render(...)`): 0.**
- **Blade pages (`return view(...)`): 100+** across `app/Http/Controllers/**`.
- All hit `layouts/app.blade.php`, all are wrapped by the middleware.

### 2.2 Explicit reload calls in Blade

| File | Line | Snippet | Why it reloads |
|---|---|---|---|
| `resources/views/billing/invoices/create.blade.php` | 275 | `window.location.href = '{{ route(...) }}?visit_id=' + ...` | onchange of a visit selector |
| `resources/views/lab/process.blade.php` | 117 | `window.location.href = data.redirect || window.location.href;` | Post-AJAX redirect |
| `resources/views/consultations/show.blade.php` | 2344 | `window.location.reload();` | After saving a "medical pattern" |
| `resources/views/queue/board.blade.php` | 39 | `onclick="location.reload()"` | Manual refresh button |

(`window.location.hash` reads at consultations/show:1542, admissions/show:742, admin/products/show:280 are **safe** — read-only.)

### 2.3 Forms

Grep for `data-inertia` across the entire `resources/` tree returns **0 matches**.

Forms across the app use the classic POST/redirect-GET cycle:

```blade
<form method="POST" action="{{ route('...') }}">
    @csrf
    <button type="submit">Save</button>
</form>
```

Result: every save in the app triggers `<form>.submit()` → native POST → 302 → browser GET → full HTML re-download → Inertia shell re-boots. **This is by far the loudest "full reload" symptom.**

### 2.4 Modal-trigger anchors

- `<a href="javascript:void(0)" data-bs-toggle="modal" ...>` — correctly ignored by `shouldIgnoreAnchor` (handles `javascript:` and `data-bs-toggle`).
- `<a href="..." target="_blank">` print/download — correctly ignored (handles `target=_blank`).

These are **not** leaks today, but they're fragile (depend on `data-bs-toggle` being literally present). Recommend tagging them with `data-no-inertia` for explicit intent.

### 2.5 Anchor-disguised-as-button

```blade
<a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#x">…</a>
```

These are ignored correctly, but semantically they should be `<button type="button">`. Not a reload leak, but a quality issue.

### 2.6 Service worker

`public/sw.js` is **navigation-network-first** (HTML never cached) and **build asset network-first**. Not a reload contributor — good.

### 2.7 Visit-error fallback

`BladePage.vue::handleClick` and `handleSubmit` both fall back to `window.location.href = url` on **any** `onError` callback or thrown exception. `inertia.js` also has a `router.on('invalid')` → `window.location.href` and an `unhandledrejection` swallow specifically for the `toString` crash.

This means: a transient 419 (CSRF), 500, or network glitch silently turns into a full reload. The user perceives this as the app "randomly" full-reloading.

---

## 3. Root causes (ranked by impact)

1. **R1 — Default-off form interception.** With `data-inertia` opt-in and **zero adoption**, 100% of saves in the app full-reload. *This is the dominant cause of the user's observation.*
2. **R2 — Hard-coded `window.location.*` calls in Blade scripts.** 4 known call sites; each is a guaranteed full reload on a specific user action.
3. **R3 — Error fallback to native navigation.** Any visit error → silent hard reload, no recovery, no user feedback.
4. **R4 — Every controller returns `view()`.** Even SPA-style nav re-renders the full Blade tree server-side. SPA cost savings are 100% client-side, ~0% server-side.
5. **R5 — Anchor/form metadata is implicit.** The bridge relies on heuristics (Bootstrap data attrs, `target`, scheme). Explicit `data-no-inertia` is rarely used, so changes to the heuristics carry risk.

---

## 4. Permanent fix plan

The plan is structured so **Phase A alone fixes the user-visible symptom across the entire app.** Phase B is the real architectural payoff but is per-page and incremental.

### Phase A — Stop the leaks (target: 1 working week)

#### A1. Flip forms to **default-on**, opt-out via `data-no-inertia`

In `resources/js/Pages/Legacy/BladePage.vue::handleSubmit`:

```diff
- // FORM SUBMISSIONS ARE OPT-IN: …
- if (!form.hasAttribute('data-inertia')) return;
+ // FORM SUBMISSIONS ARE DEFAULT-ON.
+ // - Forms that have already called event.preventDefault() (handled above) are skipped.
+ // - Forms with `data-no-inertia` (downloads, jQuery handlers, etc.) are skipped.
+ // - Cross-origin / target!=_self forms are skipped.
```

Then ship a one-time codemod pass tagging known opt-out cases:

- Forms with `enctype="multipart/form-data"` that expect a binary response (PDF, CSV) → add `data-no-inertia`.
- Forms with a `<button onclick="...form.submit()...">` shortcut → add `data-no-inertia`.
- Forms whose action route returns a non-JSON-able response (file download, external redirect) → add `data-no-inertia`.

**Risk control**: this is the riskiest change, so do it in two steps —
1. Land the codemod that tags known opt-outs **first** (zero behaviour change).
2. Then flip the default in `BladePage.vue`.

#### A2. Replace the 4 hard-coded `window.location.*` calls

| File:Line | Replacement |
|---|---|
| `billing/invoices/create.blade.php:275` | `window.UhmsInertia.visit(url, { preserveScroll: true })` (helper added in A4) |
| `lab/process.blade.php:117` | `window.UhmsInertia.visit(data.redirect, { preserveScroll: true })` |
| `consultations/show.blade.php:2344` | `window.UhmsInertia.reload({ preserveScroll: true, preserveState: true })` |
| `queue/board.blade.php:39` | `<button … onclick="window.UhmsInertia.reload({ preserveScroll: true })">` |

#### A3. Replace silent fallbacks with a structured retry

In `BladePage.vue`:

- `onError` callbacks should **not** call `window.location.href`. Instead:
  - 419 (CSRF mismatch) → refresh CSRF token via shared prop, then retry once.
  - 5xx → surface a Bootstrap toast "Action failed. [Retry]" with retry calling the same `router.visit`.
  - Network error → toast "Connection lost", do **not** reload.
- Only on **two consecutive failures** for the same visit do we fall back to native navigation, and even then with a visible "We had to reload the page" toast on the next render.

In `resources/js/inertia.js`:

- Keep the `unhandledrejection` swallow for the known `toString` crash (until upstream Inertia ships a fix) but log it loudly.
- Remove the `router.on('invalid') → fallbackToNative` chain; replace with a toast.

#### A4. Add a tiny global helper `window.UhmsInertia`

In `resources/js/inertia.js`, expose:

```js
window.UhmsInertia = {
    visit: (url, opts = {}) => router.visit(url, { preserveScroll: false, preserveState: false, ...opts }),
    reload: (opts = {}) => router.reload(opts),
    post:   (url, data, opts) => router.post(url, data, opts),
};
```

So Blade scripts have a one-line replacement for `window.location.*` calls and don't need to import from `@inertiajs/vue3` (which they can't, they're inline).

#### A5. Make anchor opt-out explicit

Codemod across `resources/views/**`:

- `<a href="..." target="_blank">` → also add `data-no-inertia`.
- `<a href="..." download>` → already ignored, but add `data-no-inertia` for clarity.
- `<a href="javascript:void(0)" data-bs-toggle="modal">` → convert to `<button type="button" class="btn …" data-bs-toggle="modal" data-bs-target="#x">` (semantic + future-proof).

#### A6. Tighten the heuristic with telemetry

Add a one-line `console.warn('[bridge] fell back to native nav', { url, reason })` at every fallback path. Run the app for a day and collect leaks. Each warning becomes a fix.

#### A7. Tests

- Add Dusk/Pest browser tests for one form per controller group (auth, visits, billing, pharmacy, lab, theatre, admissions, reports) that asserts:
  - After submit, `performance.getEntriesByType('navigation')` has only the initial entry (no extra full nav).
  - The Inertia version header `X-Inertia` was sent on the submission.

### Phase A acceptance

- 0 occurrences of `window.location.href`, `window.location.reload`, `location.reload()`, `location.assign`, `location.replace` in `resources/views/**` and `resources/js/Pages/**`. (`window.location.hash` reads exempt.)
- `grep "data-inertia\b"` returns hits only on legacy compatibility helpers, not new forms.
- All forms either submit through Inertia or are explicitly tagged `data-no-inertia`.
- Browser DevTools "Network" tab shows only one document load on app boot; subsequent navigation/submission shows `fetch` requests with `X-Inertia: true`.

---

### Phase B — Move to true Inertia per surface (target: incremental, per hot page)

Now that the bridge is leak-free, migrate per-route from `view(...)` to `Inertia::render('SomePage', $props)`. Order by hit rate:

1. **`consultations/show`** — the highest-traffic clinical screen, 2000+ lines of Blade. Split into a Vue page + small components (vitals, complaints, prescriptions, lab requests). Each sub-tab becomes its own component with its own Inertia partial reload.
2. **`billing/invoices/{index,create,show}`** — hot during the workday, lots of inline JS that would benefit from real reactivity.
3. **`pharmacy/dispensing`** — high frequency, hard real-time stock validation.
4. **`visits/{index,show}`** — entry point for most clinical workflows.
5. **`admissions/{index,show}`**.
6. **`lab/{requests,process}`**.
7. **Reports** — keep on the bridge (rare access, complex tables).
8. **Settings / admin CRUDs** — keep on the bridge unless a usability win is obvious.

Each migration follows the same recipe:

1. Build the Vue page under `resources/js/Pages/<Domain>/<Page>.vue`. Use `<Link>` for nav and `useForm()` for submits.
2. Change the controller from `view(...)` to `Inertia::render(...)`.
3. Add per-route data sharing only as needed (don't bloat `HandleInertiaRequests::share`).
4. Delete the old Blade view and its `@push('scripts')` blocks.
5. Move per-page CSS into a scoped Vue `<style>` block.

### Phase B acceptance per page

- `Inertia::render` used, not `view()`.
- Zero inline `<script>` in the rendered HTML.
- `router.visit`/`<Link>` everywhere; no `window.location.*`.
- Browser back/forward preserves scroll and form state where appropriate.

---

## 5. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Flipping forms to default-on triggers double-submit where a jQuery handler already POSTs | The existing `if (event.defaultPrevented) return;` guard in `handleSubmit` already covers handlers that call `preventDefault`. Forms whose handlers do **not** call `preventDefault` but instead trigger their own `$.ajax` need `data-no-inertia` — handled by the A1 codemod pre-pass. |
| Inertia visit on a multipart upload misbehaves | `BladePage.vue` already detects `enctype=multipart/form-data` and sets `forceFormData: true`. A1 codemod will additionally tag big-binary endpoints with `data-no-inertia`. |
| 419 CSRF after a long-idle session | A3's structured retry refreshes the CSRF from the Inertia shared prop and replays the request once. |
| Tests can't easily assert "no full reload" | Use `performance.getEntriesByType('navigation').length` in the test runner; or assert presence of `X-Inertia` header on POSTs via a small request log mocked in tests. |
| `consultations/show` migration is huge | Break into 6 sub-pages by tab (Complaints, Vitals, Diagnosis, Prescriptions, Lab, Procedures). Bridge the rest until each tab is migrated. |

---

## 6. Tracking checklist

### Phase A

- [ ] A1.1 Codemod: tag known opt-out forms with `data-no-inertia`
- [ ] A1.2 Flip `BladePage.vue::handleSubmit` default to opt-out
- [ ] A2.1 Replace `billing/invoices/create.blade.php:275`
- [ ] A2.2 Replace `lab/process.blade.php:117`
- [ ] A2.3 Replace `consultations/show.blade.php:2344`
- [ ] A2.4 Replace `queue/board.blade.php:39`
- [ ] A3 Structured retry + toast on `onError` (BladePage + inertia.js)
- [ ] A4 `window.UhmsInertia` helper
- [ ] A5 Codemod: anchor opt-outs + `<a javascript:void(0)>` → `<button>`
- [ ] A6 Fallback telemetry warning
- [ ] A7 Browser tests for one form per controller group

### Phase B (per surface, mark as migrated)

- [ ] B1 `consultations/show` → Vue tabs
- [ ] B2 `billing/invoices/*`
- [ ] B3 `pharmacy/dispensing`
- [ ] B4 `visits/*`
- [ ] B5 `admissions/*`
- [ ] B6 `lab/*`
- [ ] B7 Reports — *defer (bridge stays)*
- [ ] B8 Admin CRUDs — *defer (bridge stays)*

---

## 7. Files referenced

- [resources/js/inertia.js](../resources/js/inertia.js)
- [resources/js/Pages/Legacy/BladePage.vue](../resources/js/Pages/Legacy/BladePage.vue)
- [app/Http/Middleware/ConvertBladeViewsToInertia.php](../app/Http/Middleware/ConvertBladeViewsToInertia.php)
- [app/Http/Middleware/HandleInertiaRequests.php](../app/Http/Middleware/HandleInertiaRequests.php)
- [resources/views/app.blade.php](../resources/views/app.blade.php)
- [resources/views/layouts/app.blade.php](../resources/views/layouts/app.blade.php)
- [bootstrap/app.php](../bootstrap/app.php)
- Reload leaks: `billing/invoices/create.blade.php:275`, `lab/process.blade.php:117`, `consultations/show.blade.php:2344`, `queue/board.blade.php:39`
