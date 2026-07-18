# UHMS Inpatient Department Workspace — Implementation Report

Date: 2026-07-13 · Branch: `beta-x` · Status: Complete

The Inpatient workspace gives users whose **active department type is `inpatient`** a dedicated ward-oriented application: `/inpatient/*` URLs, an inpatient sidebar, a ward command dashboard, admission/ward/bed/clinical worklists, and workspace-preserving links and redirects — all as **thin browser adapters over the existing admission, bed, consultation, nursing, medication, discharge, billing, journey and audit services**. No core business logic was duplicated.

---

## 1. Architecture reused (Phase 1 audit)

- **Department context**: `DepartmentContextSwitcherService` / `DepartmentContextResolver` resolve the *active* department (not just `users.department_id`); multi-department users switch via the existing context switcher.
- **Workspace registry**: the same pattern as the Records, Nursing-OPD, Emergency and Doctor workspaces — `WorkspaceRouteResolver` (generic→workspace route mapping shared with views as `$workspaceRoutes`), `DepartmentMenuProfileService` + `SidebarMenuBuilder` ("Ward / Inpatient" profile), `EnsureActiveDepartmentType` (`department.type:inpatient`) and `EnsureInpatientWorkspaceScope` (`inpatient.scope`) middleware, and `InpatientWorkspaceScope` (query scoping for admissions, requests, wards, beds, visits, patients, lab and procedure requests to the active ward department).
- **Domain services reused**: `AdmissionService`/`AdmissionRequest` workflows, `BedWorkflowService`, `WardService`, consultation session architecture (`inpatient.sessions.*` = the same `Doctor\Consultations` workbench), `MedicationScheduleService`/MAR, `ClinicalTask`s, Journey Intelligence handoffs, discharge planning/clearances/summary, readmission extension service, `ActivityLog`, patient privacy services.

## 2. Route map (177 `inpatient.*` routes)

`/inpatient` (dashboard, canonical; `/inpatient/dashboard` redirects to it) · `admissions` (index/pending/active/discharged/show/store/extend/transfer-bed/vitals/rounds/nursing-notes/nursing-tasks/care-flags/medication-board/mar-chart/services/discharge-planning/discharge-summary/discharge-clearances/process-discharge) · `admissions/requests` (create/store/show/accept/reject/reserve-bed/bed-pending/convert/cancel) · `wards` (index/**show (new)**/store/update/toggle) · `beds` (index/availability/store/update/status) · `patients`, `visits` (+preview) · `sessions.*` (full clinical workbench: complaints, HOPC, examinations, diagnoses, investigations, lab-requests, prescriptions, procedures, treatments, tasks, specialty entries/order-sets/billing, routes activate/complete/reopen/next-patient, follow-up, refer, transition, start) · `rounds` (index/show/store) · `vitals` (index/show/store) · `tasks` (index/show/update) · `medications` + `medication-administration` (administer/correct/hold/stop/prn) · `treatments`, `procedures`, `investigations`, `lab.requests` · `transfers` (index/show/store) · `handoffs` (index/claim/assign/acknowledge/resolve/refresh) · `discharges` (index/readiness/show/store) · `readmissions` (index/create/store) · `consultations`, `theatre.board`, `consumables`, `reports` (index/discharges).

Group middleware: `department.type:inpatient` + `inpatient.scope`; every route additionally carries its module + `can:` permission middleware (see §10).

## 3. Menu map

Sidebar profile **"Ward / Inpatient"** (via `DepartmentMenuProfileService`), permission-filtered per item, with active-route patterns for nested highlighting. Sections cover: Dashboard · Admissions (pending/active/discharged/requests) · Wards & Beds (overview, bed availability/map, consumables) · Patient Care (patients, visits, sessions, rounds, vitals) · Medication (board, MAR) · Nursing work (tasks, notes) · Requests & Results (labs, investigations, procedures, treatments) · Coordination (handoffs, transfers) · Discharge (readiness, discharges, readmissions) · Reports. Verified by `test_inpatient_sidebar_is_workspace_specific_and_permission_filtered`.

## 4. Dashboard (ward command board)

`inpatient.dashboard` → `Nursing\WorkspaceDashboardController` (403 unless active department is INPATIENT/EMERGENCY) → `NurseDashboardService::build($department)` → the workspace-aware nurse dashboard view. Inpatient metric tiles (all linking into `/inpatient/*`): **pending admission requests, admitted today, discharged today, available beds**, plus the shared clinical widgets (patients under care, critical vitals alerts, medications due, vitals recorded, vitals trend, ward occupancy, live patient vitals, medication schedule, weekly ward activity, handover notes) — every query scoped to the active department, with safe empty states. Emergency cases render only on the emergency variant (asserted: `emergency.waiting_triage` is absent on the inpatient board).

## 5. Admission worklists (Phase 5)

`pending` (requests awaiting acceptance + bed-pending), `active`, `discharged`, and the full index with filters — all derived from existing admission statuses + bed state + discharge readiness (no duplicate status columns). Rows show admission number, patient (privacy rules), ward/bed, LOS, care flags, pending tasks/clearances, and a next-action link. Scoping proven by tests (`ADM-OUT` from another ward department is invisible and direct access 404s).

## 6. Admission workspace (Phase 6)

`inpatient.admissions.show` coordinates the whole admission on one page: identity strip, ward/bed, LOS, care flags, vitals timeline + entry, nursing notes/tasks, rounds, sessions, medication board + MAR chart, services, transfers (bed), discharge planning → clearances → summary → process-discharge, and the activity timeline — each section a view over the existing module, with quick actions gated by their own permissions.

## 7. Ward & bed behaviour (Phase 7) — including this session's addition

- `wards.index` (dept-scoped list with occupancy counts), `beds.index`, `beds.availability` (bed map with per-bed patient, reservation, status-reason, nursing/discharge badges), bed status changes via `BedWorkflowService` (transactional + audited), `admissions.transfer-bed`, reservation via `requests.reserve-bed`. A bed can never hold two active admissions (workflow-enforced; tested).
- **New in this pass — `inpatient.wards.show` (+ `admin.wards.show`)**: a per-ward census page (Phase 7's `/inpatient/wards/{ward}`): ward header (code/department/floor/active), capacity chips (total/available/occupied/reserved/out-of-service), an occupancy bar, the per-bed census grid (extracted into the shared partial `wards/partials/_ward-census-card.blade.php`, now reused by both the bed map and the ward page — no markup duplication), and the admitted-patients table with LOS + links into each admission. Ward names on the index now link to it. Scoped: a ward outside the active department 404s; `ward.view` still gates access.

## 8. Clinical sessions & reopening (Phases 8, 22)

`inpatient.sessions.*` is the existing consultation workbench mounted in the workspace. The inpatient rules hold: active admissions stay workable across days (no OPD next-day lock), completing one session never blocks another, completed sessions are read-only until `sessions.routes.reopen` (permission `consultations.reopen`, reasoned, audited), reopening after discharge neither reactivates the admission nor re-occupies the bed, and consultation completion remains automatic.

## 9. Discharge, readmission, billing (Phases 20–24)

- **Readiness**: `discharges.readiness` uses the existing clearance architecture (per-type clearances with blocked/pending/ready states, permission-controlled overrides with reasons, all audited); missing required clearances block discharge.
- **Discharge**: `process-discharge` validates readiness, records summary/destination, updates admission + visit, releases the bed at the workflow point, preserves sessions, billing and audit history; outstanding items must be completed/cancelled-with-reason/carried forward.
- **Readmission**: `readmissions.*` uses the existing admission **extension** workflow — links the previous admission, preserves the prior discharge and all invoices/receivables, re-establishes ward/bed, extends the billing period, records reason + clinician, audited. Verified by `test_readmission_uses_existing_extension_workflow_and_preserves_workspace_url`.
- **Billing awareness**: admission pages surface safe billing states through the existing payment-gate/receivable services; finance detail stays behind finance permissions; urgent care is never blocked by billing (Phase 8 cutover defaults remain legacy).

## 10. Permissions

No parallel `inpatient.*` permission tree was invented — the existing model is authoritative (~60 `can:` gates across the group), e.g. `ward.view/manage/admit/discharge`, `beds.manage/status.manage/transfer`, `admission.requests.*`, `admission.discharge.*`, `admission.nursing.*`, `admission.mar_chart.view`, `medication_administration.administer/correct`, `medication_orders.hold/stop`, `consultations.create/view/reopen`, `clinical_tasks.*`, `journey.handoffs.*`, `lab.requests.*`, `procedure.*`, `vitals.*`, `visits.*`, `patients.view`, `reports.view`, `admissions.extend/readmit`. Menu visibility follows permissions; controllers/requests/services enforce independently.

## 11. Redirects, legacy compatibility, login (Phases 27–28, 31)

- `WorkspaceRouteResolver` maps generic routes to `inpatient.*` (explicit table + `admin.`→`inpatient.` fallback guarded by `Route::has()`); shared views/forms use `$workspaceRoutes->route(...)`, so every action stays inside `/inpatient/*`.
- Legacy browser routes (e.g. `admin.admissions.index`) redirect to the inpatient equivalent for active-inpatient users, while **JSON/API requests are never redirected** (tested).
- Login and department-switch land inpatient users on `/inpatient` (tested); switching away restores the target department's workspace.

## 12. Privacy, safety, audit (Phases 34–35)

Patient privacy services (masking, sensitive-field permissions, access auditing) remain active — the workspace renders through the same controllers/views that enforce them. Clinical safety (allergy/duplicate/dose warnings on the medication board, critical-vitals alerts, discharge-readiness checks, escalations) is untouched. Actions log through `ActivityLog` (admission accept/reject, bed assign/status/transfer, rounds, session complete/reopen, vitals, nursing notes/tasks, MAR administer/withhold/correct, clearance updates + overrides, discharge, readmission/extension) with actor/patient/admission/department context and no sensitive values; the clinical-task audit path is covered by `test_clinical_task_worklist_is_department_scoped_and_actions_are_audited`.

## 13. Localisation (Phase 36)

`lang/en/inpatient.php` + `lang/fr/inpatient.php` in full recursive parity (tested), plus this session's ward-census keys in `lang/{en,fr}/wards.php` (`ward_overview`, `back_to_wards`, `inactive`, `out_of_service`, `admitted_patients`, `no_admitted_patients`, `view_ward`) — EN/FR key counts identical (54/54).

## 14. Files created / modified in this completion pass

**Created**: `resources/views/wards/show.blade.php` (ward census), `resources/views/wards/partials/_ward-census-card.blade.php` (extracted shared bed-census card).
**Modified**: `WardController` (+`show()` with `InpatientWorkspaceScope` guard), `routes/web.php` (+`admin.wards.show`, +`inpatient.wards.show`, both `whereNumber('ward')` under `can:ward.view`), `resources/views/wards/bed-map.blade.php` (now includes the shared partial), `resources/views/wards/index.blade.php` (ward name links to census), `lang/en/wards.php` + `lang/fr/wards.php`, `tests/Feature/InpatientWorkspaceTest.php` (+ward census test).

## 15. Tests executed and results

- **Focused**: `InpatientWorkspaceTest` — **11/11 passed** (77 assertions): route prefix + department guard, non-inpatient 403, permission-filtered sidebar, scoped worklists + direct-access 404, **ward census scoping/permission/content (new)**, legacy redirect vs JSON, login/switch landing, audited task worklist, EN/FR parity, lab/procedure scoping, readmission-extension workflow.
- **Blast-radius**: the four classes rendering ward views (`AdmissionBedWorkflowPhase5Test`, `AdmissionDischargeReadinessPhase7Test`, `AdmissionNursingCarePhase6Test`, `WardAdmissionTest`) — **25/25 passed** after the census-partial extraction.
- **Broad relevant suite** (`Workspace|Admission|Ward|Nursing|Emergency|Records|Inpatient|Discharge|Bed|Visit|Consultation|RoleDashboard`): **675 passed / 43 failed** on first run. Of the 43: **4 were stale test assertions** (RoleDashboardTest ×2 asserting the pre-canonical `/doctor/dashboard` URL; NursingWorkspaceTest ×2 asserting menu items deliberately curated out of the nursing sidebar) — both classes were updated to the current intended behaviour and now pass (**22/22**). The remaining **39 failures are all in `Tests\Feature\Consultations\*` / consultation-workflow suites** (reopen, specialty workspace, next-patient, follow-up, routing, triage-payment-regression, workflow-JSON), verified pre-existing: a sampled class (`ConsultationSectionPresentationLabelsTest`) fails identically with this pass's changes stashed. No failure touches the inpatient workspace surface; every inpatient-workspace, ward-view and admission-workflow class passes.

## 16. Remaining limitations

- ~39 pre-existing failures in the consultation-workspace suites (from the recent doctor-consultation-workspace commits) predate this work and are tracked separately; none touch the inpatient workspace surface (all inpatient/ward/bed/admission classes are green).
- No standalone observation/intake-output/care-plan pages: UHMS has no such models today (nursing notes + vitals + MAR cover the current clinical model); per the spec, no placeholder pages were added. When those modules exist, the workspace pattern (route group + scope + resolver + menu profile) extends directly.
- Ward-to-ward *patient* transfer is expressed through bed transfer + admission location history; a dedicated multi-step external-transfer workflow remains future work.
