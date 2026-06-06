# Emergency Case → Consultation Session Workflow Fix

Emergency cases must be treated as a **billable Emergency / Casualty consultation
session**, fully integrated with visits, consultation, billing, the patient
timeline and logging — not a disconnected case record.

## Root cause

Most of the clinical-session plumbing already existed and was correct:

- `EmergencyCaseService::create` already created the visit (type/status EMERGENCY),
  the `EmergencyCase`, and called `EmergencySessionService::getOrCreateForCase`,
  which **already** creates the consultation-interface session
  (`VisitConsultationRoute` with `session_type = EMERGENCY`), the `MedicalRecord`,
  and the `EmergencySession` — all idempotent, lock-guarded, and surfaced in the
  consultation UI and visit preview.

The actual gaps were:

1. **No billable service on the emergency consultation.** The emergency
   consultation route was created with **`service_id = null`**, and the Emergency
   Consultation service was never billed. So billing/consultation did not know the
   emergency consultation service, and the running invoice had no consultation line.
2. **Visit-type = Emergency did nothing special.** Creating a visit with
   `visit_type = emergency` produced a normal OPD/registered visit — no emergency
   case, no emergency session.
3. **Emergency Create patient search was inconsistent** with Visit Create (a
   full-page GET reload + a static server-rendered dropdown vs. the visit page's
   AJAX select2).

## Emergency consultation service mapping

`EmergencySessionService::defaultConsultationService(EmergencyCase)` resolves the
billable Emergency / Casualty consultation service:

1. The configured setting **`emergency.default_consultation_service_id`**
   (`Setting::getValue('emergency', 'default_consultation_service_id')`) — set it
   via the settings store to pin a specific service.
2. Fallback: a `CONSULTATION`-category active service in the **Emergency /
   Casualty** department (resolved by code/name/type — `EMR`, `emergency`,
   `casualty`). The seeders already ship **`EMR-CON` "Emergency Consultation"**
   (₵250, billable) in the Emergency / Casualty (consultation) department, so this
   works out of the box with no configuration.

No service name is hard-coded; the configurable setting wins, the department-based
resolver is the zero-config fallback.

## Emergency case creation behaviour

`EmergencySessionService::getOrCreateConsultationRoute` now stamps the resolved
service onto the route (`service_id`), which flows to the `MedicalRecord`. After
the session is ensured, `EmergencyCaseService::create` calls
`ensureEmergencyConsultationBilling()`, which:

- bills the emergency consultation via `BillingService::addItemToVisitInvoice`
  (`source_type = 'emergency_service'`), insurance-aware pricing, and
- links the route ↔ service ↔ invoice item via `VisitConsultationRouteService`.

This follows the **running-bill policy**: the item lands on the visit's running
invoice and is **never gated on payment** — emergency care starts immediately, the
balance is settled later. Idempotent throughout: `BillingService`'s duplicate guard
prevents a second invoice item, `getOrCreateForCase` prevents duplicate
routes/sessions, and `EmergencyCaseService` already blocks a second active case per
visit. If no billable service is configured/seeded, creation still succeeds (route
created, no invoice item).

## Visit Create behaviour (visit_type = Emergency)

`VisitController::store` now, after creating the visit + attaching any selected
services, routes an **Emergency** walk-in visit through
`EmergencyCaseService::create(['visit_id' => …])`:

- **Case A (no consultation service selected):** the emergency case + session are
  created and the Emergency consultation is billed.
- **Case B (a consultation service was selected):** the visit is **still** treated
  as Emergency — the emergency case + session are created and the emergency
  consultation is billed **in addition to** the selected service. The selected
  service is not lost; the visit does not fall back to OPD.

Emergency visits **skip the OPD triage queue** (status is EMERGENCY, the emergency
board owns them). Scheduled (future-dated) and non-emergency visits are unchanged.

## Patient search UI fix (parity with Visit Create)

Extracted a single reusable partial used by the Emergency Create page (and
available to any page) — **no parallel search system, no duplicated markup**:

- `resources/views/patients/partials/patient-search-select.blade.php` — select2
  search input + hidden `patient_id`.
- `resources/views/patients/partials/patient-search-select-scripts.blade.php` —
  select2 init mirroring the Visit Create behaviour exactly.

It reuses the **existing** `admin.visits.patient-search` endpoint (no new endpoint).
`Patient::search` already matches by **name, folder/patient number, phone, email,
Ghana Card number, and insurance membership number** (plus contacts/aliases). The
Emergency Create page now uses this in place of the GET-reload + static dropdown,
keeping the "Create temporary emergency patient" unknown-patient path. The selected
patient submits as `patient_id`.

## Billing behaviour summary

- Emergency consultation billed once, insurance-aware, on the running invoice.
- No pay-before-service gate (distinct from OPD).
- Linked to visit + emergency consultation route + invoice item.
- No duplicate invoice items / cases / sessions / routes on retry.

## Logs / audit trail

Reused the existing funnel — **no new/duplicate logs**:
`EMERGENCY / CASE_CREATED` (ActivityLogService) and the `EMERGENCY_SESSION_CREATED`
pathway event already fire on creation; the emergency timeline records `ARRIVAL`.
The billed consultation item rides the standard invoice/visit billing trail. No
change was needed to `docs/LOGGING_REMAINING_TODOS.md` — the documented
emergency/admission follow-ups (bay/bed assignment, vitals, contributors, bed
transfer/release, discharge summary, print) are untouched and remain open.

## Visit preview / patient timeline

Because the emergency session is a `VisitConsultationRoute` + `MedicalRecord` on the
visit, and the consultation is now a billed line item, Visit Preview and the
consultation interface show the emergency visit, the Emergency / Casualty session,
the billed emergency consultation service, and the emergency clinical records — all
on the patient's chronological history.

## Idempotency / race safety

- `getOrCreateForCase` uses `lockForUpdate` and get-or-create for the route,
  medical record and emergency session.
- `EmergencyCaseService::create` runs in a transaction and rejects a second active
  emergency case for the same visit.
- Billing dedupes via `BillingService` source guard; the route↔service link is an
  `updateOrCreate`.

## Tests

- `tests/Feature/EmergencyConsultationBillingTest.php` (5) — route carries the
  service; consultation billed under running-bill (unpaid, care not blocked);
  idempotent on re-ensure; setting override; graceful when no service configured.
- `tests/Feature/VisitEmergencyTypeTest.php` (3) — emergency visit (no service)
  creates case/session/bill; emergency visit with a selected service is still
  emergency; normal OPD visit unaffected.
- `tests/Feature/EmergencyCreatePatientSearchTest.php` (2) — Emergency Create uses
  the shared search partial + endpoint; the endpoint matches every criterion
  (name, phone, Ghana Card, insurance membership, folder number).

All **10 new tests pass**; the broader emergency/visit/consultation suite passes
(57/58 — the 1 failure, `ConsultationRouteSessionWorkflowTest::
test_visit_page_transition_section_shows_routes_and_queue_form`, is **pre-existing**:
it fails identically on the baseline without these changes).

## Files changed

- `app/Services/EmergencySessionService.php` — service resolver +
  `service_id` on the emergency route.
- `app/Services/EmergencyCaseService.php` — inject `BillingService`;
  `ensureEmergencyConsultationBilling()` after session creation.
- `app/Http/Controllers/Admin/VisitController.php` — emergency visit-type branch.
- `app/Http/Controllers/Admin/EmergencyCaseController.php` — pass `selectedPatient`,
  drop the server-rendered patient list.
- `resources/views/emergency/create.blade.php` — use the shared search partial.
- `resources/views/patients/partials/patient-search-select.blade.php` (+ `-scripts`)
  — new reusable partials.

## Remaining TODOs / follow-ups

- Add a Settings UI control to pick `emergency.default_consultation_service_id`
  (the value is already honoured; today it is set via the settings store, with the
  department-based fallback covering the unconfigured case).
- Optionally migrate the Visit Create page to the shared patient-search partial
  (it currently keeps its own equivalent inline implementation; behaviour matches).
- Pre-existing: fix `ConsultationRouteSessionWorkflowTest::
  test_visit_page_transition_section_shows_routes_and_queue_form` (unrelated to this
  change).
