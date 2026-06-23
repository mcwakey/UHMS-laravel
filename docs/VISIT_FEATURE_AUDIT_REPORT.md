# Visit Feature Audit & Fix Report
**Date:** April 13, 2026  
**Scope:** Visit insurance display, department→service selection, billing line creation

---

## 1. Requirements Checked

| Requirement | Status Before Audit | Status After Fix |
|---|---|---|
| **Visit show page**: Display insurance used for visit | ❌ Had bugs — would crash | ✅ Fixed |
| **Visit show page**: Show insurance limitations (annual limit, per-visit limit) | ⚠️ Template existed but referenced wrong keys | ✅ Fixed |
| **Visit show page**: Show current usage state from billing | ⚠️ Referenced `billed` instead of `total_billed` | ✅ Fixed |
| **Visit show page**: Show when NO insurance selected | ❌ Section hidden entirely | ✅ Shows "Cash & Carry" fallback |
| **Visit create**: Department → loads services | ❌ 0 services had `department_id` | ✅ 65 services seeded across 16 departments |
| **Visit create**: Selecting service creates billing line | ✅ Already working (frontend JS + backend `attachServices()`) | ✅ No change needed |
| **Visit create**: Insurance display with coverage/limits | ✅ Already working | ✅ No change needed |

---

## 2. Bugs Found & Fixed

### Bug 1: `insurance_type` property crash on visit show (CRITICAL)
- **File:** `resources/views/visits/show.blade.php` line 157
- **Problem:** Template referenced `$insuranceInfo['insurance']->insurance_type` but `PatientInsurance` model has no `insurance_type` column. The insurance type lives on the `InsuranceProvider` model.
- **Fix:** Changed to `$insuranceInfo['provider']->type->color()` and `$insuranceInfo['provider']->type->label()`

### Bug 2: `coverage_percentage` on wrong model (CRITICAL)
- **File:** `resources/views/visits/show.blade.php` line 164
- **Problem:** Referenced `$insuranceInfo['insurance']->coverage_percentage` — this field is on `InsuranceProvider`, not `PatientInsurance`.
- **Fix:** Changed to `$insuranceInfo['coverage_percentage']` (from `getUsageSummary()` return array)

### Bug 3: Wrong array key `billed` → `total_billed`
- **File:** `resources/views/visits/show.blade.php` lines 180, 183
- **Problem:** `InsuranceService::getUsageSummary()` returns `total_billed`, but the template accessed `$insuranceInfo['billed']`.
- **Fix:** Changed all `['billed']` references to `['total_billed']`

### Bug 4: Null `insurance_price` crashes `number_format()`
- **File:** `resources/views/visits/show.blade.php` line 220
- **Problem:** `visit_services.insurance_price` column is nullable. When null, `number_format(null, 2)` in some PHP configs can warn.
- **Fix:** Added null coalescing: `$vs->insurance_price ?? 0`

### Bug 5: Insurance section hidden when no insurance
- **File:** `resources/views/visits/show.blade.php` line 143
- **Problem:** `@if($insuranceInfo)` wrapped the entire card — if no `visit_insurance_id` was set, the section completely disappeared.
- **Fix:** Card is now always visible. Shows insurance details when present, shows "Cash & Carry (Self-Sponsored)" fallback when absent.

### Bug 6: Cash & Carry had 100% coverage instead of 0%
- **Table:** `insurance_providers`
- **Problem:** The "Cash & Carry" provider had `coverage_percentage = 100.00` — misleading since Cash & Carry means patient pays everything.
- **Fix:** Updated to `coverage_percentage = 0.00`. The `InsuranceService::calculateCoverage()` already returned 0 for `is_default` providers, but the frontend display was showing wrong info.

---

## 3. Data Gap Fixed

### No services linked to departments (CRITICAL)
- **Problem:** Only 1 service existed in `service_catalog` ("general consultation") with `department_id = NULL`. Selecting any department in the create form returned 0 services via the AJAX endpoint — the entire department→services→billing flow was broken.
- **Fix:** Created `ServiceCatalogSeeder` with 65 services across all 16 departments:

| Department | Services | Examples |
|---|---|---|
| General Medicine / OPD | 7 | General Consultation, Follow-up, Blood Pressure Check, Wound Dressing |
| Pediatrics | 4 | Pediatric Consultation, Child Immunization, Growth Monitoring |
| Obstetrics & Gynecology | 5 | Obstetric Consultation, Antenatal Check-up, Ultrasound Scan |
| Surgery | 4 | Surgical Consultation, Minor Surgery, Suturing |
| Orthopedics | 3 | Orthopedic Consultation, POP Application, Fracture Management |
| Eye Clinic | 4 | Eye Consultation, Visual Acuity Test, Foreign Body Removal |
| ENT | 3 | ENT Consultation, Ear Syringing, Throat Swab |
| Dental | 4 | Dental Consultation, Tooth Extraction, Filling |
| Psychiatry | 2 | Psychiatric Consultation, Counseling Session |
| Emergency / Casualty | 4 | Emergency Consultation, Triage, Resuscitation, Oxygen Therapy |
| Laboratory | 10 | FBC, Malaria RDT, Urinalysis, LFT, RFT, HIV, Hepatitis B |
| Pharmacy | 1 | Dispensing Fee |
| Radiology / X-Ray | 4 | Chest X-Ray, Abdominal X-Ray, Ultrasound |
| Physiotherapy | 2 | Physiotherapy Consultation, Session |
| Antenatal / Postnatal | 3 | Antenatal Registration, Visit, Postnatal Check-up |
| Family Planning | 4 | Counseling, Implant Insertion, IUD Insertion, Injectable |

All services have NHIS prices configured. Existing "GC" service updated with OPD department_id.

---

## 4. Architecture Verification (Already Correct)

These components were audited and found to be working correctly:

| Component | Status | Notes |
|---|---|---|
| `InsuranceService::resolveForVisit()` | ✅ | Correctly resolves selected → primary → Cash & Carry fallback |
| `InsuranceService::getPatientInsurances()` | ✅ | Returns full validation/coverage info for frontend |
| `InsuranceService::calculateCoverage()` | ✅ | Handles NHIS-only, coverage %, per-visit limits, Cash & Carry exclusion |
| `InsuranceService::getUsageSummary()` | ✅ | Queries invoices by visit_insurance_id for YTD billing |
| `VisitService::attachServices()` | ✅ | Creates `visit_services` records with insurance price calculation |
| `VisitService::getServicesForDepartment()` | ✅ | Queries by `department_id` (now works with seeded data) |
| `VisitController::patientInsurances()` | ✅ | AJAX endpoint returns insurances + auto-resolved default |
| `VisitController::departmentServices()` | ✅ | AJAX endpoint returns services for department |
| `StoreVisitRequest` | ✅ | Validates `visit_insurance_id`, `services[]` array |
| `BillingService::generateItemsFromVisit()` | ✅ | Uses visit_services as primary source, falls back to consultation fee |
| `Visit::visitInsurance` relationship | ✅ | `belongsTo(PatientInsurance::class, 'visit_insurance_id')` |
| `Visit::visitServices` relationship | ✅ | `hasMany(VisitServiceItem::class)` |
| Frontend: Patient search → Insurance load | ✅ | Radio selection with coverage/limits/remaining balance |
| Frontend: Dept → Services → Add to billing | ✅ | Dynamic AJAX loading, add service creates billing line row |
| Frontend: Real-time billing calculation | ✅ | Insurance amount, total, patient-pays all recalculated live |

---

## 5. Files Modified

| File | Change |
|---|---|
| `resources/views/visits/show.blade.php` | Fixed 5 bugs (property references, null safety, always-visible insurance) |
| `database/seeders/ServiceCatalogSeeder.php` | **New** — 65 services across 16 departments |
| `insurance_providers` table data | Cash & Carry coverage: 100% → 0% |
| `service_catalog` table data | 1 → 65 services, all with `department_id` |

---

## 6. Current Flow Summary

### Visit Creation
1. **Select Patient** → AJAX search
2. **Insurance auto-loads** → Radio list with validity, coverage %, remaining balance
3. **Select Department** → AJAX loads department services (now 65 services available)
4. **Click "+" on service** → Adds to billing table with quantity, unit price, insurance coverage, total
5. **Submit** → Creates visit + `visit_services` records + insurance prices calculated

### Visit Show Page
1. **Insurance card** always visible — shows provider, type badge, coverage %, remaining balance
2. **Usage progress bar** shown when annual limit exists (% used, color-coded)
3. **"Cash & Carry" fallback** shown when no insurance selected
4. **Visit Services table** — service name, qty, unit price, insurance amount, total, subtotals, patient-pays
