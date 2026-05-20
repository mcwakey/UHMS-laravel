# Emergency Unit / Emergency Department — Integration Audit

> **Audit only.** No code is changed by this document. It maps the current UHMS implementation so the Emergency module can be added without disturbing OPD, Admission, Triage, Consultation, Billing, Pharmacy, Investigation, Theatre/Procedure or Inventory workflows.

---

## 1. Executive Summary

UHMS already exposes most of the primitives an Emergency Department needs:

- `VisitType::EMERGENCY` and `VisitStatus::EMERGENCY` are defined and reachable from triage.
- `Priority::EMERGENCY` and `TriageScore::EMERGENCY` exist on the visit + triage records.
- Visit → MedicalRecord → (Prescription | LabRequest | ProcedureRequest | Diagnosis | Treatment) graph is **visit‑scoped, not OPD‑scoped**, so the same clinical primitives work for any `visit_type`.
- Billing uses one Invoice per visit with polymorphic `InvoiceItem.source_type` lines, which already accommodates emergency consumption.
- Inventory uses `Product` + `ProductStockMovement` + `StockLocation(department_id)`; adding an Emergency stock location is a config/seed change, not a schema change.
- Modules + Spatie permissions + `SidebarMenuBuilder` are designed for plug‑in modules. A new `emergency` module can be added without touching existing menus.

**Net conclusion:** Emergency should be implemented as an **overlay** on the existing Visit workflow (a specialized `visit_type` + dedicated queue/dashboard/triage UI), **not** as a parallel patient lifecycle. The biggest gaps are: (a) no emergency‑aware triage queue, (b) no auto‑stat behaviour for labs/pharmacy, (c) `DrugStock` (legacy) is not department‑scoped, (d) the EMERGENCY → ADMITTED transition needs to be exercised end‑to‑end, (e) no emergency `StockLocation` is seeded, (f) no Emergency role/permissions/menu.

---

## 2. Current OPD / Visit Workflow

### 2.1 Models, enums, services

- [app/Models/Visit.php](app/Models/Visit.php) — fillable includes `visit_number`, `patient_id`, `visit_type`, `status`, `priority`, `chief_complaint`, `visit_date`, `checked_in_at`, `checked_out_at`, `assigned_doctor_id`, `current_department_id`, `triage_score`, `visit_insurance_id`, `insurance_verification_id`, `consultation_mode`, `meeting_link`, plus reschedule/cancel fields.
- [app/Enums/VisitType.php](app/Enums/VisitType.php) — `OUTPATIENT`, `INPATIENT`, `EMERGENCY`.
- [app/Enums/VisitStatus.php](app/Enums/VisitStatus.php) — 27 statuses including `EMERGENCY`, `ADMITTED`, `DISCHARGING`, `DISCHARGED`, `COMPLETED`. Allowed transitions defined in the enum itself (e.g. `TRIAGE → {WAITING_CONSULTATION, CONSULTING, EMERGENCY, INPATIENT, CANCELLED}`).
- [app/Services/VisitService.php](app/Services/VisitService.php) — `list()`, `create()` (assigns visit number, decides SCHEDULED vs walk‑in TRIAGE, enforces one visit per patient per day).
- [app/Services/VisitWorkflowService.php](app/Services/VisitWorkflowService.php) — `initialize()`, `checkIn()`, `confirm()`, `cancel()`, `transition()`, `startConsultation()`. `transition()` is the canonical mutator and writes `VisitStatusLog` rows.

### 2.2 Routes

From [routes/web.php](routes/web.php):

- `GET/POST /visits` → `Admin/VisitController` (`visits.index`, `visits.create`, `visits.store`, `visits.patient-search`).
- Visit transitions are exposed via consultation routes (see §5).

### 2.3 How OPD is identified

`visit_type = OUTPATIENT` (default). Walk‑ins enter `status = TRIAGE`; scheduled appointments enter `SCHEDULED → CONFIRMED → REGISTERED → WAITING → TRIAGE`.

### 2.4 Status lifecycle (essential paths)

```
SCHEDULED → CONFIRMED → REGISTERED → WAITING → TRIAGE
TRIAGE → {WAITING_CONSULTATION | CONSULTING | EMERGENCY | INPATIENT | CANCELLED}
CONSULTING → {LAB | PHARMACY | REFERRED_CONSULTATION | ADMITTED | BILLING | COMPLETED}
ADMITTED → {CONSULTING | LAB | PHARMACY | DISCHARGING}
DISCHARGING → {BILLING | ADMITTED}
BILLING → {COMPLETED | DISCHARGED | CANCELLED}
```

### 2.5 `VisitWorkflowService` exists and is the single point of truth

Yes. All consultation/triage/admission controllers route status mutations through `VisitWorkflowService::transition()` (or its specialized helpers). **Any Emergency feature must use this same service** to preserve audit logging via `visit_status_logs`.

---

## 3. Current Admission / Inpatient Workflow

### 3.1 Models, enums, services

- [app/Models/Admission.php](app/Models/Admission.php) — `admission_number`, `visit_id`, `patient_id`, `bed_id`, `ward_id`, `status`, `admission_date`, `expected_discharge_date`, `actual_discharge_date`, `admitted_by`, `discharged_by`, `admitting_diagnosis`, `discharge_summary`, `admission_type`, `admission_fee_service_id`, `consumable_fee_service_id`.
- [app/Models/Bed.php](app/Models/Bed.php), [app/Models/Ward.php](app/Models/Ward.php).
- [app/Enums/AdmissionStatus.php](app/Enums/AdmissionStatus.php) — `ADMITTED | ON_LEAVE | DISCHARGED | TRANSFERRED | DECEASED`.
- [app/Enums/BedStatus.php](app/Enums/BedStatus.php) — `AVAILABLE | OCCUPIED | MAINTENANCE | RESERVED`.
- [app/Services/AdmissionService.php](app/Services/AdmissionService.php) — `admit()`, `discharge()`, `list()`, `createAdmissionInvoice()`.

### 3.2 Routes

- `GET /admissions`, `POST /admissions`, `GET /admissions/{admission}`, discharge form + `POST /admissions/{admission}/discharge`, `POST /admissions/{admission}/services`.

### 3.3 Linkage and billing

- `Admission.visit_id` ties admission to the originating visit. `AdmissionService::admit()` flips `Visit.visit_type` to `INPATIENT` and `Visit.status` to `ADMITTED`.
- One **auto‑generated invoice per admission** with three lines (admission/detention fee, bed × days, consumable × days). The invoice is keyed by `visit_id` (no `admission_id` FK on `invoices`).
- Insurance evaluated via `InsuranceService::evaluateCoverage()` on `visitInsurance`.

### 3.4 Admission origin

Admission is initiated from the consultation page (or admissions index) — `Visit.status = CONSULTING → ADMITTED`. There is currently **no direct EMERGENCY → ADMITTED route** wired in UI; the enum allows it via `EMERGENCY → {ADMITTED, CONSULTING, COMPLETED, CANCELLED}` but no controller calls it yet.

---

## 4. Current Triage Workflow

### 4.1 Models, enums, controllers

- [app/Models/Triage.php](app/Models/Triage.php) — `visit_id`, `patient_id`, `department_id` (target consult dept), vitals (`blood_pressure_systolic/diastolic`, `heart_rate`, `temperature`, `respiratory_rate`, `spo2`, `weight`, `height`, `bmi`), `triage_score`, `notes`, `triaged_by`, `triaged_at`.
- [app/Models/Vital.php](app/Models/Vital.php) — generic vitals timeline used during ward rounds / consultation.
- [app/Enums/TriageScore.php](app/Enums/TriageScore.php) — `ROUTINE | URGENT | EMERGENCY`.
- [app/Enums/Priority.php](app/Enums/Priority.php) — `NORMAL | URGENT | EMERGENCY` (stored on `visits.priority`).
- [app/Http/Controllers/Admin/TriageController.php](app/Http/Controllers/Admin/TriageController.php) — `index`, `show`, `create`, `store`.

### 4.2 Routes (require `vitals.create`)

- `GET /triage`, `GET /triage/{visit}`, `GET /triage/{visit}/assess`, `POST /triage/{visit}`.

### 4.3 Behaviour

- Triage is performed by nurses with `vitals.create` permission.
- `TriageController::store()` records vitals, computes BMI, picks consultation dept, and transitions `TRIAGE → WAITING_CONSULTATION` by default (or `EMERGENCY` / `INPATIENT` based on findings).
- Triage queue is sourced from `Visit.status = TRIAGE` via `QueueService` (entries added in `VisitWorkflowService::initialize()` for walk‑ins).

### 4.4 What is already emergency‑aware

- `TriageScore::EMERGENCY`, `Priority::EMERGENCY`, `VisitStatus::EMERGENCY` all exist.
- No automatic vitals‑threshold → triage_score logic; the score is set manually.
- No separate emergency triage queue/dashboard.

---

## 5. Current Consultation Workflow

### 5.1 Models / services

- [app/Models/MedicalRecord.php](app/Models/MedicalRecord.php) — `visit_id`, `patient_id`, `doctor_id`; hasMany `Complaint`, `Diagnosis`, `Treatment`, `Prescription`, `LabRequest`/`Investigation`, etc.
- [app/Models/Complaint.php](app/Models/Complaint.php), [app/Models/Diagnosis.php](app/Models/Diagnosis.php), [app/Models/Treatment.php](app/Models/Treatment.php), [app/Models/ConsultationTask.php](app/Models/ConsultationTask.php).
- [app/Services/ConsultationService.php](app/Services/ConsultationService.php) — `getOrCreateRecord()`, `getConsultationData()`, `getPatientHistory()`, complaint/diagnosis CRUD.
- [app/Services/ClinicalService.php](app/Services/ClinicalService.php) — ICD‑10 search, procedure catalog lookup.

### 5.2 Queue source & start logic

- Queue rows for consultation come from `Visit.status ∈ {WAITING_CONSULTATION, CONSULTING}`.
- `VisitWorkflowService::startConsultation()` flips `WAITING_CONSULTATION → CONSULTING` and assigns a doctor if needed.

### 5.3 Routes (Doctor/ConsultationController)

- `GET /consultations`, `GET /consultations/{visit}` (main EHR), `GET /consultations/{visit}/history`.
- `POST /consultations/{visit}/start` → `startConsultation`.
- `PATCH /consultations/{visit}/transition` → generic `transitionVisit`.
- `POST /consultations/{visit}/refer` → `REFERRED_CONSULTATION`.
- `POST /consultations/{visit}/investigation` → `WAITING_INVESTIGATION`.
- `POST /consultations/{visit}/{complaints|diagnoses|treatments|prescriptions|procedures|lab-request}` → store* methods.

### 5.4 Implication for Emergency

The consultation primitives are **`Visit`‑scoped, not `visit_type`‑scoped**. An emergency doctor can use the same controller endpoints simply by working on a `Visit` whose `visit_type = EMERGENCY` and `status = EMERGENCY` (transition rules already allow `EMERGENCY → CONSULTING`).

---

## 6. Current Billing / Invoice Workflow

### 6.1 Models / enums / services

- [app/Models/Invoice.php](app/Models/Invoice.php) — `visit_id`, `patient_id`, `billing_type` (`CASH|INSURANCE`), `subtotal`, `tax_amount`, `discount_amount`, `nhis_amount`, `total_amount`, `amount_paid`, `balance`, `status`.
- [app/Enums/InvoiceStatus.php](app/Enums/InvoiceStatus.php) — `DRAFT | PENDING | PARTIALLY_PAID | PAID | CANCELLED | REFUNDED`.
- [app/Models/InvoiceItem.php](app/Models/InvoiceItem.php) — polymorphic line via `source_type` + `source_id`; also stores `service_catalog_id`, `product_id`, `cash_price`, `insurance_price`, `selected_price`, `insurance_covered`, `patient_payable`.
- [app/Models/Payment.php](app/Models/Payment.php), [app/Models/PaymentAllocation.php](app/Models/PaymentAllocation.php) — payment splits per item.
- [app/Models/VisitServiceItem.php](app/Models/VisitServiceItem.php) — staging table during consultation (pre‑invoice cost preview with insurance breakdown).
- [app/Services/BillingService.php](app/Services/BillingService.php) — `addItemToVisitInvoice()`, `addProductToVisitInvoice()`, `createInvoice()`, `recordPayment()`, `cancelInvoice()`, `generateItemsFromVisit()`.
- [app/Services/InvoiceService.php](app/Services/InvoiceService.php), [app/Services/PaymentService.php](app/Services/PaymentService.php), [app/Services/ServicePricingService.php](app/Services/ServicePricingService.php), [app/Services/ProductPricingService.php](app/Services/ProductPricingService.php), [app/Services/InsuranceService.php](app/Services/InsuranceService.php).

### 6.2 Canonical `source_type` values on `InvoiceItem`

- `consultation_service`
- `investigation_service` (from `LabRequestItem`)
- `procedure_service` (from `ProcedureRequest`)
- `pharmacy_product` (from `DispensingRecord`)
- `ward_consumable` (from `ConsumableUsage`)

### 6.3 One‑invoice‑per‑visit rule

`Invoice.visit_id` is the link. `Admission` shares the visit's invoice (it adds lines, it does not create a new invoice). Emergency must follow the same rule: **use the visit's invoice**; do not create parallel emergency invoices.

### 6.4 Billing safety for Emergency

`BillingService::addItemToVisitInvoice()` is duplicate‑guarded on `(source_type, source_id)` and resolves cash vs insurance pricing automatically — so Emergency consumption can safely reuse it with a new (or existing) `source_type`.

---

## 7. Current Investigation Workflow

### 7.1 Models / services

- [app/Models/LabRequest.php](app/Models/LabRequest.php) — `request_number`, `visit_id`, `patient_id`, `requested_by`, `department_id`, `target_department_id`, `clinical_info`, `urgency`, `status`.
- [app/Models/LabRequestItem.php](app/Models/LabRequestItem.php) — `lab_request_id`, `lab_test_id`, `service_id`, `status (pending|accepted|processing|completed|verified|cancelled|rejected)`, `accepted_at/by`, `billed_at`, `invoice_item_id`, `unit_price`.
- [app/Models/InvestigationHeader.php](app/Models/InvestigationHeader.php), [app/Models/InvestigationItem.php](app/Models/InvestigationItem.php) (catalog), [app/Models/InvestigationResultValue.php](app/Models/InvestigationResultValue.php), [app/Models/LabResult.php](app/Models/LabResult.php).
- [app/Services/InvestigationRequestService.php](app/Services/InvestigationRequestService.php) — `acceptSelectedItems()`, `rejectItems()`.

### 7.2 Flow

1. Doctor calls `POST /consultations/{visit}/lab-request` → creates `LabRequest` + items (`status = pending`).
2. Lab accepts via `PATCH /lab/requests/{labRequest}/accept` or `accept-selected` → `BillingService::addItemToVisitInvoice('investigation_service', item.id)` and item moves to `accepted/processing`.
3. Results entered via `POST /lab/results/{item}` or batch endpoint; verified via `PATCH /lab/results/{result}/verify`.

### 7.3 Emergency readiness

- `LabRequest.urgency` field exists — Emergency just needs to set it to `STAT` (or equivalent) and (recommended) sort by it in the lab queue.
- `source_type/source_id` on `InvoiceItem` already exists; emergency does not need new types.

---

## 8. Current Pharmacy Workflow

### 8.1 Models / services / enums

- [app/Models/Prescription.php](app/Models/Prescription.php) — `medical_record_id`, `visit_id`, `patient_id`, `doctor_id`, `prescription_number`, `status`, `notes`.
- [app/Models/PrescriptionItem.php](app/Models/PrescriptionItem.php) — `drug_id`, `drug_name`, `dosage`, `frequency`, `duration`, `quantity`, `route`, `instructions`, `is_dispensed`.
- [app/Models/Drug.php](app/Models/Drug.php), [app/Models/DrugStock.php](app/Models/DrugStock.php) (`location` is a **string**, not an FK to `StockLocation`), [app/Models/DispensingRecord.php](app/Models/DispensingRecord.php).
- [app/Enums/PrescriptionStatus.php](app/Enums/PrescriptionStatus.php) — `PENDING | DISPENSED | PARTIALLY_DISPENSED | CANCELLED`.
- [app/Services/PrescriptionService.php](app/Services/PrescriptionService.php), [app/Services/PharmacyService.php](app/Services/PharmacyService.php).
- [app/Http/Controllers/Pharmacy/DispensingController.php](app/Http/Controllers/Pharmacy/DispensingController.php) — `index`, `show`, `dispenseItem`, `batchDispense`.

### 8.2 Behaviour

- Dispensing deducts from `DrugStock.quantity` (legacy pharmacy stock model **separate** from `ProductStockMovement`).
- Pharmacy is visit‑scoped via `Prescription.visit_id`. Emergency prescriptions therefore Just Work — but the dispensing path does not deduct from a department‑specific Emergency stock because `DrugStock.location` is a free‑text label.

### 8.3 Known gap

Dispensing does not automatically push lines to the visit invoice as `pharmacy_product` consistently — verify in `DispensingController` whether `BillingService::addProductToVisitInvoice` is called per dispense (it should be; this is the canonical place to enforce it for Emergency as well).

---

## 9. Current Theatre / Procedure Workflow

### 9.1 Models / services / enums

- [app/Models/ProcedureRequest.php](app/Models/ProcedureRequest.php) — `request_number`, `visit_id`, `patient_id`, `requested_by`, `department_id`, `service_catalog_id`, `procedure_id`, `priority`, `indication`, `preferred_datetime`, `status`, `accepted_by/at`, `rejected_by`, `billed_at`, `billing_item_id`.
- [app/Models/ProcedureSchedule.php](app/Models/ProcedureSchedule.php), [app/Models/Procedure.php](app/Models/Procedure.php) (catalog), [app/Models/OperativeNote.php](app/Models/OperativeNote.php), [app/Models/AnaesthesiaNote.php](app/Models/AnaesthesiaNote.php), [app/Models/PostOpNote.php](app/Models/PostOpNote.php), [app/Models/ProcedureChecklist.php](app/Models/ProcedureChecklist.php), [app/Models/ProcedureVital.php](app/Models/ProcedureVital.php), [app/Models/ProcedureStatusLog.php](app/Models/ProcedureStatusLog.php).
- [app/Enums/ProcedureStatus.php](app/Enums/ProcedureStatus.php) — `REQUESTED → ACCEPTED → BILLED → SCHEDULED → PRE_OP → ANAESTHESIA → IN_SURGERY → SURGERY_DONE → POST_OP → COMPLETED` (+ `REJECTED | CANCELLED | ON_HOLD | RESCHEDULED`).
- [app/Services/ProcedureRequestService.php](app/Services/ProcedureRequestService.php), [app/Services/ProcedureWorkflowService.php](app/Services/ProcedureWorkflowService.php), [app/Services/ProcedureScheduleService.php](app/Services/ProcedureScheduleService.php), [app/Services/ProcedureClinicalService.php](app/Services/ProcedureClinicalService.php), [app/Services/ProcedureReportService.php](app/Services/ProcedureReportService.php).

### 9.2 Behaviour relevant to Emergency

- `ProcedureRequest.priority` already supports `emergency`. Acceptance auto‑bills via `ProcedureWorkflowService::generateBilling()` → `InvoiceItem.source_type = procedure_service`.
- Consumables during a procedure are recorded by `ConsumableUsageService::recordUsageForSource('procedure_request', id, items)` which creates `ProductStockMovement(PROCEDURE_CONSUMED)`.
- Emergency can request a procedure with the same controller endpoints; no schema change required.

---

## 10. Current Product Stock / Inventory Workflow

### 10.1 Unified inventory model

- [app/Models/Product.php](app/Models/Product.php) — the single physical‑item entity.
- [app/Models/ProductStockMovement.php](app/Models/ProductStockMovement.php) — `product_id`, `stock_location_id`, `movement_type`, `direction`, `quantity`, `unit_cost`, `batch_no`, `expiry_date`, `source_type`, `source_id`, `performed_by`, `movement_date`.
- [app/Models/ProductStockBalance.php](app/Models/ProductStockBalance.php) — `(product_id, stock_location_id)` cached balance.
- [app/Models/StockLocation.php](app/Models/StockLocation.php) — `name`, `type`, `department_id` (nullable FK), `is_active`, `is_main`. **Already supports department‑scoped locations.**
- [app/Models/ConsumableUsage.php](app/Models/ConsumableUsage.php) — `visit_id`, `patient_id`, `service_id`, `source_type` (`procedure_request|investigation_result|ward_care|…`), `source_id`, `product_id`, `stock_location_id`, `quantity_used`, `stock_movement_id`, `used_by`, `used_at`.
- Legacy: `StockMovement` / `StockBalance` (drug‑centric). Still present for pharmacy.

### 10.2 Enums

- [app/Enums/StockMovementType.php](app/Enums/StockMovementType.php) — `OPENING_STOCK | PURCHASE_RECEIVED | PHARMACY_DISPENSED | INVESTIGATION_CONSUMED | PROCEDURE_CONSUMED | WARD_CONSUMED | TRANSFER_IN/OUT | RETURN_IN/OUT | ADJUSTMENT_IN/OUT | DAMAGED | EXPIRED | REVERSAL_IN/OUT`. **No `EMERGENCY_CONSUMED` yet.**
- [app/Enums/ProductType.php](app/Enums/ProductType.php) — `DRUG | CONSUMABLE | REAGENT | SURGICAL_SUPPLY | MEDICAL_SUPPLY | EQUIPMENT | GENERAL_ITEM | SUPPLY (legacy)`.

### 10.3 Department consumption today

`StockLocationResolver::getDefaultLocationForDepartment()` maps a department to its stock location. Theatre, Lab, Pharmacy, Wards each have a resolved location. **Emergency is not yet mapped**, and no Emergency `StockLocation` row is seeded.

### 10.4 Single‑source rule

The repo already enforces *"every physical item = Product, every movement = ProductStockMovement"* for non‑pharmacy paths. Pharmacy still uses the legacy `DrugStock` table for dispense. Emergency should follow the unified path (`ProductStockMovement` + `ConsumableUsage`) for consumables and continue to go through `DispensingRecord` for drugs.

---

## 11. Current Roles, Menus, and Dashboards

### 11.1 Roles & permissions

- Spatie permission package, config in [config/permission.php](config/permission.php).
- 21 roles seeded in [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php) (Super Admin, Admin, Doctor, Consultant, Specialist, Physician Assistant, Nurse, Ward Nurse, Theatre Nurse, Anaesthetist, Radiologist, Receptionist, Lab Technician, Lab Manager, Pharmacist, Cashier, Accountant, Claims Officer, Store Keeper, HR Manager).
- Permission convention: dotted groups (`patients.*`, `visits.*`, `consultations.*`, `vitals.*`, `prescriptions.*`, `lab.requests.*`, `pharmacy.*`, `invoices.*`, `payments.*`, `procedure.*`, `ward.*`, `beds.*`, `stock.*`, `reports.*`, `modules.manage`, …).
- **No emergency roles or permissions exist yet.**

### 11.2 Modules

- [app/Models/Module.php](app/Models/Module.php) + [app/Services/ModuleService.php](app/Services/ModuleService.php). Modules have `slug`, `is_core`, `is_enabled`, `depends_on`, `icon`, `sort_order`. `ModuleService::enabled($slug)` is cache‑backed and fails‑open.
- Core: `auth, users, patients, visits, triage, consultation, departments, services, billing, settings`.
- Optional: `insurance, claims, pharmacy, inventory, investigations, analyzer, hr, payroll, reports, medical-patterns, notifications`.
- Emergency belongs in the optional set with `depends_on = visits, triage, consultation, billing`.

### 11.3 Sidebar menu

- [app/Services/SidebarMenuBuilder.php](app/Services/SidebarMenuBuilder.php) — `build(?User, $currentRouteName, $unread)`. Each item is gated by `permission` and/or `module` (slug). Active highlighting uses `active_patterns` (glob match). Clinical users get a focused menu via `consultationSections()`. Emergency entries can be added behind `module = 'emergency'` and `permission = 'emergency.*'` without touching existing sections.

### 11.4 Dashboards

- `routes/web.php` `dashboard` route redirects by role:
  - Super Admin / Admin → `admin.dashboard`
  - Doctor → `doctor.dashboard` ([Doctor/DashboardController](app/Http/Controllers/Doctor/DashboardController.php))
  - Nurse / Receptionist / Pharmacist / Lab / Cashier / Accountant / HR / Store → `staff.dashboard` ([StaffDashboardController](app/Http/Controllers/StaffDashboardController.php), branches by role name).
- There is **no Emergency dashboard yet**.

---

## 12. Integration Risks

| # | Risk | Affected files / modules | Why it matters | Recommended safe approach |
|---|------|--------------------------|----------------|---------------------------|
| 1 | **Visit status pollution** — emergency cases added directly via `Visit.status = EMERGENCY` may appear in OPD consultation queue if filters are loose | `ConsultationController@index`, `QueueService`, `SidebarMenuBuilder` (counters) | Doctors see ER patients in their OPD queue, missed prioritization | Filter the consultation queue on `visit_type = OUTPATIENT` (or expose explicit `visit_types` filter). Emergency queue reads only `visit_type = EMERGENCY`. |
| 2 | **Triage queue mixing** — triage list currently shows all `status = TRIAGE` visits | `Admin/TriageController@index`, `QueueService::triageEntries()` | ER patients buried among OPD walk‑ins | Add `visit_type` filter to triage list; show two tabs (OPD vs Emergency) or a separate emergency triage list. |
| 3 | **Billing double‑posting** if Emergency adds its own invoice instead of using the visit invoice | `BillingService::addItemToVisitInvoice`, any new emergency controller | Duplicate invoices for one episode of care; reconciliation pain | Mandatory rule: every emergency item goes through `BillingService::addItemToVisitInvoice/addProductToVisitInvoice` on the visit's existing invoice (same as Admission). |
| 4 | **Admission flow conflict** — `EMERGENCY → ADMITTED` is allowed by the enum but no UI exercises it | `AdmissionService::admit()`, `VisitStatus`, ConsultationController | Direct ER admit may bypass diagnosis prerequisites | `AdmissionService::admit()` should accept a visit in `EMERGENCY` (not only `CONSULTING`). Add a guard test before enabling the UI button. |
| 5 | **Insurance verification stalls** — current flow expects verification before billing | `InsuranceService`, `VisitWorkflowService` | Genuine emergencies cannot wait | Allow Emergency visits to bill provisionally (`payer_type = cash` fallback) and reconcile insurance post‑stabilization. Config flag + audit log. |
| 6 | **Stock location conflict** — no Emergency `StockLocation`; consumption falls back to main store or theatre | `StockLocationResolver`, `ConsumableUsageService`, `ProductStockMovementService` | Wrong dept balance; transfer history misleading | Seed an Emergency stock location, map `DepartmentType::EMERGENCY` (after adding it) in resolver, add `StockMovementType::EMERGENCY_CONSUMED`. |
| 7 | **Pharmacy stock not department‑scoped** — `DrugStock.location` is a string | `DispensingController`, `PharmacyService`, `DrugStock` | Emergency drug usage is not deducted from an ER drug cabinet | Either (a) introduce an ER bay row in `DrugStock.location` and document it, or (b) migrate dispensing to `ProductStockMovement` (longer‑term, separate phase). |
| 8 | **Menu / permission leakage** — copying existing menu blocks may grant ER users unintended permissions | `SidebarMenuBuilder`, `RoleSeeder` | Privilege escalation / wrong dashboards | Use dedicated `emergency.*` permissions and `module = 'emergency'` gate. Do not reuse `consultations.start` etc. blindly — assign explicitly. |
| 9 | **Lab `urgency` not enforced** — emergency labs may queue normally | `Lab/LabRequestController`, `InvestigationRequestService` | Slower TAT for stat tests | Sort lab queues by `urgency` descending; show STAT badge; optionally auto‑skip acceptance step for STAT items. |
| 10 | **Triage duplication** — vitals can be entered both in Triage and on consultation page | `TriageController`, `VitalController` | Two sources of truth for ER observations | Keep one Triage row per visit; for repeated ER vitals use the `Vital` timeline (already used in ward rounds). |
| 11 | **Consultation queue pollution by EMERGENCY status** — `EMERGENCY → CONSULTING` is a valid transition; if not filtered, ER patients sent to OPD doctor | `ConsultationController@index`, queue source | Wrong specialist sees ER patient | Add `visit_type` filter to consultation queue and dedicated ER "in‑consult" view. |
| 12 | **VisitWorkflowService bypass** — direct `Visit::status = …` writes outside the service skip the audit log | New emergency code | Loss of audit trail | All ER status changes must call `VisitWorkflowService::transition()` (or its named helpers). |

---

## 13. Recommended Emergency Architecture

### 13.1 Visit model — overlay, not replacement

- **Use** `Visit.visit_type = EMERGENCY` for the canonical episode.
- **Do not** create a parallel `emergency_visits` table.
- An optional `emergency_cases` table can be added later to hold ER‑only fields (`triage_category`, `arrival_mode`, `accompanied_by`, `mass_casualty_id`, `disposition`) **without** duplicating any visit field. It links 1‑1 to `visits.id`.

### 13.2 Triage

- **Reuse** the `triage` table and `Triage` model.
- Optionally add a `triage_category` column (e.g. ESI 1–5) or store the category in the optional `emergency_cases` row.
- Add automated vitals‑threshold → `TriageScore::EMERGENCY` promotion in `TriageController::store()` behind a config switch.

### 13.3 Consultation

- Reuse `MedicalRecord` + existing consultation controllers.
- Render through a dedicated ER UI (Inertia page) that filters by `visit_type = EMERGENCY` and exposes Triage + Vitals timeline + ABCDE prompts.

### 13.4 Billing

- Reuse `BillingService::addItemToVisitInvoice/addProductToVisitInvoice` against the visit's single invoice.
- Add a new `InvoiceItem.source_type` value only if a brand new source is needed (e.g. `emergency_observation_fee`).

### 13.5 Investigations

- Reuse `LabRequest`/`LabRequestItem`. Set `urgency = STAT` and add a STAT lane to lab dashboards. **No schema change needed.**

### 13.6 Pharmacy / drugs

- Reuse `Prescription` + `DispensingRecord`. Optionally introduce an ER drug cabinet row in `DrugStock` (string location), and ensure dispensing posts a `pharmacy_product` invoice line.
- Optional later phase: migrate `DrugStock` to `ProductStockMovement` + `StockLocation` to make dispensing department‑scoped natively.

### 13.7 Consumables / inventory

- Add `StockLocation` row for Emergency (`department_id = <Emergency dept>`, `type = 'emergency'`).
- Add `StockMovementType::EMERGENCY_CONSUMED` (new enum value + migration enum update if MySQL ENUM is used).
- Add `DepartmentType::EMERGENCY` (if not present) and map it in `StockLocationResolver`.
- `ConsumableUsageService::recordUsageForSource('emergency_case', $caseId, $items)` (or reuse `visit_id` with `source_type='emergency'`).

### 13.8 Admission conversion

- Wire a button on the ER case page that calls `AdmissionService::admit()`. Confirm/relax the precondition that allows the source visit to be in `EMERGENCY` (currently expects `CONSULTING`). Use `VisitWorkflowService::transition(EMERGENCY → ADMITTED)`.

### 13.9 Disposition

- Outcomes: `ADMITTED` (via Admission service), `DISCHARGED` (`EMERGENCY → COMPLETED/DISCHARGED`), `REFERRED` (`EMERGENCY → REFERRED_CONSULTATION` or a new external‑referral record), `DECEASED` (Admission/Visit supports it via Admission status + a future `Visit.outcome` field).

### 13.10 Roles, permissions, module, menu

- **Module**: seed `emergency` (depends on `visits, triage, consultation, billing`).
- **Permissions**: `emergency.access`, `emergency.dashboard.view`, `emergency.queue.manage`, `emergency.triage.create`, `emergency.case.create`, `emergency.case.update`, `emergency.case.discharge`, `emergency.case.escalate`, `emergency.admit`, `emergency.refer`, `emergency.stock.consume`, `emergency.report.view`.
- **Roles**: assign to Doctor, Nurse, Receptionist (limited to registration), Pharmacist (read), Cashier (read). Consider a new `Emergency Doctor` and `Emergency Nurse` role for stricter scoping.
- **Sidebar**: add Emergency section gated by `module = 'emergency'` + per‑item permissions.
- **Dashboard**: new `Emergency/DashboardController` with metrics (active cases, triage backlog, mean stabilization time, beds awaiting admission, stat labs pending).

---

## 14. Suggested Files to Modify (no edits in this audit)

> The list below is a planning aid only — actual diffs to be produced in implementation phases.

1. **Enums**
   - [app/Enums/StockMovementType.php](app/Enums/StockMovementType.php) — add `EMERGENCY_CONSUMED`.
   - [app/Enums/DepartmentType.php](app/Enums/DepartmentType.php) — add `EMERGENCY` if missing.
   - (Optional) [app/Enums/VisitStatus.php](app/Enums/VisitStatus.php) — review transitions `EMERGENCY ↔ {TRIAGE, REFERRED_CONSULTATION}`.
2. **Services**
   - [app/Services/AdmissionService.php](app/Services/AdmissionService.php) — allow `admit()` from `EMERGENCY`.
   - [app/Services/StockLocationResolver.php](app/Services/StockLocationResolver.php) — map `EMERGENCY` department.
   - [app/Services/ConsumableUsageService.php](app/Services/ConsumableUsageService.php) — accept `source_type = 'emergency_case'`.
   - [app/Services/BillingService.php](app/Services/BillingService.php) — no API change; verify `source_type` casing.
   - [app/Services/SidebarMenuBuilder.php](app/Services/SidebarMenuBuilder.php) — add Emergency section.
   - [app/Services/InvestigationRequestService.php](app/Services/InvestigationRequestService.php) — honor `urgency = STAT` ordering (read‑only filter change in controllers).
3. **New code (no existing file edited)**
   - `app/Http/Controllers/Emergency/{DashboardController,CaseController,TriageController,QueueController,DispositionController}.php`.
   - `app/Models/EmergencyCase.php` (optional 1‑1 with Visit).
   - `app/Services/EmergencyService.php` (thin orchestrator over Visit/Triage/Billing services — must not duplicate their logic).
   - `database/migrations/*_create_emergency_cases_table.php` (optional table).
   - `resources/js/Pages/Emergency/{Dashboard,Queue,Triage,Case}.vue`.
4. **Seeders**
   - [database/seeders/ModuleSeeder.php](database/seeders/ModuleSeeder.php) — register `emergency` module.
   - [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php) — add `emergency.*` permissions and (optionally) `Emergency Doctor`, `Emergency Nurse` roles.
   - StockLocation seeder — add Emergency location.
5. **Routes**
   - [routes/web.php](routes/web.php) — `Route::prefix('admin/emergency')->name('admin.emergency.')->middleware('can:emergency.access')->group(...)`.

---

## 15. Safe Implementation Phases

**Phase 0 — Audit & docs (this document).** No code.

**Phase 1 — Foundations (no UI surface).**
- Add `emergency` module row.
- Add permissions and (optionally) `Emergency Doctor`/`Emergency Nurse` roles.
- Add Emergency `StockLocation` + `DepartmentType::EMERGENCY` + resolver mapping + `StockMovementType::EMERGENCY_CONSUMED`.
- Verify enum transitions and write feature tests for `TRIAGE → EMERGENCY`, `EMERGENCY → CONSULTING`, `EMERGENCY → ADMITTED`, `EMERGENCY → COMPLETED`.

**Phase 2 — Emergency triage + queue.**
- Filter existing triage/consultation queues by `visit_type`.
- New `Emergency/QueueController` + `Emergency/TriageController` reusing `TriageController::store` logic via the existing service.
- Dashboard read‑only.

**Phase 3 — ER consultation, prescription, labs, procedures (reuse).**
- New ER consultation page wired to existing consultation endpoints filtered by `visit_type = EMERGENCY`.
- STAT lane in lab dashboard (sort by `urgency`).
- ER stock consumption via `ConsumableUsageService`.

**Phase 4 — Disposition.**
- Wire `AdmissionService::admit()` from ER (relax precondition).
- Discharge / refer / deceased outcome capture.
- Insurance deferral flow.

**Phase 5 — Reporting + alerts.**
- Emergency KPIs report, dashboard widgets, optional notifications (mass‑casualty mode, critical alerts).

**Phase 6 — Drug‑stock unification (optional, separate roadmap item).**
- Migrate `DrugStock` to `ProductStockMovement` + `StockLocation` so ER drug cabinet can be department‑scoped natively.

Each phase ends with: targeted feature tests, regression tests on OPD/Admission/Triage/Consultation/Billing/Pharmacy/Lab/Theatre/Inventory, and a manual UAT script.

---

## 16. Open Questions / Missing Information

1. **Triage category model.** Does the hospital want ESI 1–5, MTS, CTAS, or a local scale? Affects `triage_category` enum + UI labels.
2. **Mass‑casualty mode.** Is a dedicated MCI flag/dashboard required (multiple ER cases sharing one incident id)?
3. **Arrival mode tracking.** Walk‑in / ambulance / police / referral — confirm whether to persist on Visit, on `emergency_cases`, or as free text.
4. **External referral records.** Do we need a structured `Referral` entity, or is `REFERRED_CONSULTATION` status + a note enough?
5. **Death documentation.** Where should the mortality form live (Admission `DECEASED` already exists; do we need ER‑only death certificate before any admission)?
6. **Insurance behaviour for emergencies.** Auto‑bill cash and reconcile later, or block billing until verification? Need policy decision.
7. **Pharmacy ER cabinet.** Use string `DrugStock.location = 'EMERGENCY'`, or block until pharmacy stock is unified into `ProductStockMovement`?
8. **STAT lab acceptance.** Should accept‑step be skipped for `urgency = STAT` items, or only re‑ordered?
9. **Bed/holding area in ER.** Is the ER a "department" only, or also a `Ward` with `Bed` rows (for ER observation beds)? Affects whether ER occupancy uses `Bed` or a lighter "ER bay" concept.
10. **Role granularity.** Reuse generic Doctor/Nurse with `emergency.*` perms, or create dedicated `Emergency Doctor` / `Emergency Nurse` roles? (Recommendation: dedicated roles for cleaner audit.)
11. **SLA / response time tracking.** Targets per triage category, alerts on breach — required?
12. **Handover from Emergency to Ward.** Should `Admission` row carry an `emergency_case_id` for traceability, or is `visit_id` enough?

---

*End of audit. No source files were modified.*
