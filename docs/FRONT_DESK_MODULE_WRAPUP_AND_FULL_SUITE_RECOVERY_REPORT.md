# Front Desk Module Wrap-up and Full-Suite Recovery Report

## Summary

The Front Desk Operations module is feature-complete across Phases 18A–18E
(visitor/call/courier logs, patient visitor management + pass, call follow-up
queue, courier dispatch/handover workflow, reports/exports, and shift handover /
lost & found / incident desk). Phase 18F **closes the module**: it fixed the
three consultation full-suite failures that were deliberately deferred throughout
18A–18E, reconfirmed the Front Desk suite and all safety gates, ran the full
suite, and produced this closure documentation. No new Front Desk features were
added — this was a cleanup/closure phase only.

## Initial State

Focused Front Desk status: **green** (109 focused tests). Safety gates: green
(LanguageParity, ActiveRuntimeLocalizationAudit, RouteLoadMemory,
PermissionsAuditStrict).

Three deferred consultation failures (present on committed `main`, unrelated to
Front Desk, carried through every Front Desk phase):

1. `ConsultationJavascriptLifecyclePhase2Test::workflow controls no longer use inline event handlers`
2. `ConsultationStructurePhase3Test::no inline workflow handlers exist in show or partials`
3. `ConsultationClinicalSectionsTest::consultation page shows required clinical order`

Source: `resources/views/consultations/show.blade.php` — committed inline
`onsubmit=` handlers on the three "send investigations/prescriptions/procedures
to department" forms (≈ lines 938 / 1299 / 1491), and the required clinical-order
assertion for the **Treatments** section.

## Consultation Cleanup

### Inline handler fixes (failures 1 & 2)

The three department-handoff forms used a large inline
`onsubmit="if (!confirm(…)) return false; …disable button + spinner…; return true;"`.
Each was migrated to the project's existing **delegated** patterns, preserving
all behaviour:

- The `confirm()` gate → `data-confirm="{{ __('…investigation_handoff_confirm…') }}"`
  on the `<form>`, consumed by the established delegated handler in
  `resources/js/inertia.js` (`installConfirmDelegates`) which intercepts the
  submit, awaits the dialog, then `requestSubmit()`s on confirm (HTML5 validation
  preserved).
- The button disable + "queueing" spinner → `data-loading-text="{{ __('…investigation_handoff_queueing…') }}"`
  on the submit button, consumed by the global double-submit handler in
  `layouts/app.blade.php` (which already disables the button and shows a spinner
  on real submit).

Preserved for every form: POST route, `@csrf`, the `consultation_route_id` hidden
input (route context), validation display, and normal-submit fallback. No
behaviour was deleted; the confirm + disable/spinner UX is intact via the shared
conventions. No inline handlers remain in `show.blade.php` or its partials.

### Clinical section order fix (failure 3)

The Treatments section header is now specialty-aware and renders the label
**"Treatment Plan"** (`consultation_specialties.sections.treatment_plan`) instead
of the literal "Treatments", so the required-order assertion could no longer find
`Treatments` in sequence. Per the phase rule ("if the label intentionally
changed, add a backward-compatible stable marker instead of weakening the test"),
a hidden, canonical marker was added at the treatments section header:

```html
<span class="visually-hidden" data-clinical-section="treatments">Treatments</span>
```

The visible "Treatment Plan" label and all treatment/procedure/task workflow are
unchanged; the marker simply restores the stable clinical-order contract. The
test was not weakened.

## Files Modified

- `resources/views/consultations/show.blade.php`
  - 3× `onsubmit=` inline handlers → `data-confirm` (form) + `data-loading-text` (button)
  - added the backward-compatible `Treatments` clinical-section marker
- `docs/FRONT_DESK_MODULE_WRAPUP_AND_FULL_SUITE_RECOVERY_REPORT.md` (this file)

No Front Desk files were changed in Phase 18F (no Front Desk test exposed a
defect).

## Front Desk Closure Audit

- **Routes permission-gated** — every `admin.front-desk.*` route sits inside
  `can:front_desk.view` and carries an action-specific `can:` middleware
  (verified by `route:list` and enforced by feature tests asserting 403 for
  unauthorised users on each area).
- **Write actions audit-logged** — all create/update/workflow actions funnel
  through their services into `ActivityLogService` under the `FRONT_DESK` module
  (visitor, call, courier, callback, handover, lost-found, incident, pass-print,
  report-export events); tests assert the events exist.
- **EN/FR localisation** — `LanguageParityTest` green (front_desk **559/559**
  keys) and `ActiveRuntimeLocalizationAuditTest` reports **0** active runtime
  candidates (no hardcoded visible strings).
- **Privacy-safe exports** — CSV exports mask phone numbers, exclude
  clinical/billing fields, and export incident summary/status (not the full
  narrative). Verified by `FrontDeskReportsTest`.
- **No clinical/billing exposure** — the module never reads or renders diagnosis,
  notes, medications, lab results or billing; patient-linked screens show only
  safe identifiers. A test asserts an admission's `admitting_diagnosis` never
  appears on visitor screens.
- **Phones masked where required** — visitor/call export phones, and lost-found /
  incident phones in lists, detail pages and exports.
- **Workflows pass tests** — visitor, call, courier, callback queue, courier
  dispatch/handover, shift handover, lost & found and incident desk all covered
  (109 focused tests).
- **Report/export permission separation** — `front_desk.reports.view` vs
  `front_desk.reports.export` enforced and tested.
- **Sidebar entries permission-gated** — each Front Desk menu child declares its
  `permission` and is filtered by `SidebarMenuBuilder`.

## Tests Run

```text
Focused consultation (post-fix):
  ConsultationJavascriptLifecyclePhase2Test   → PASS
  ConsultationStructurePhase3Test             → PASS
  ConsultationClinicalSectionsTest            → PASS
  tests/Feature/Consultations (whole dir) + ConsultationWorkspaceStabilisationTest
    + System/RouteLoadMemoryTest              → 257 passed

Front Desk + gates:
  tests/Feature/FrontDesk + LanguageParity + ActiveRuntimeLocalizationAudit
    + RouteLoadMemory + PermissionsAuditStrict → 115 passed (382 assertions)

Build / route / view:
  php artisan route:list  → 1190 routes
  php artisan view:cache  → OK
  npm run build           → OK
```

## Full Suite Result

```text
php artisan test
Tests:    1644 passed (8823 assertions)
```

**The full suite is green — 0 failures.** All three previously-deferred
consultation failures are resolved and no other test regressed (the prior
baseline was 1583 passed / 3 failed; it is now 1644 passed / 0 failed — the +58
delta over 1583+3 is the Front Desk 18C–18E tests added since that baseline was
recorded). No test was skipped or weakened to achieve this.

## Risk / Safety Notes

- Consultation workflow behaviour is preserved: the department-handoff forms
  still confirm, disable the button with a spinner, POST with CSRF + route
  context, and run validation — only the delivery mechanism moved from an inline
  handler to the established delegated conventions.
- Front Desk behaviour is unchanged (no Front Desk source edited this phase).
- No clinical or billing data exposure was introduced.
- No tests were weakened, skipped, or had guards removed; the section-order fix
  adds a stable marker rather than relaxing the assertion.

## Known Issues / Follow-up

Genuine deferred enhancements (not defects):
- Visitor pass QR / PDF
- SMS / email callback reminders and incident escalation alerts
- Incident attachments / photos
- Dedicated Security role (not present in this install)
- Scheduled / emailed front desk reports
