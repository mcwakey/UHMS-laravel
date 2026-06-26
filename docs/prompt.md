# UHMS Department Type Expansion — Phase 4: Workflow Routing Cleanup & Department Type Groups

## Goal

Clean up UHMS workflow routing so the new department types do not accidentally disappear from queues, selectors, sessions, requests, service routing, billing screens, or operational workflows.

Phase 0 identified the gap.

Phase 1 made `DepartmentType` safe and canonical.

Phase 2 made dashboards department-type aware.

Phase 3 made sidebar menu profiles department-aware.

Phase 4 must now address the dangerous part:

```text
hardcoded workflow filters like where('type', DepartmentType::CONSULTATION->value)
where('type', DepartmentType::INVESTIGATION->value)
where('type', DepartmentType::PROCEDURE->value)
where('type', DepartmentType::TREATMENT->value)
```

These filters were safe when UHMS had only 8 broad department types, but they can become wrong after precise retyping.

Example:

```text
Emergency / Casualty used to be consultation.
If it becomes emergency, old consultation-only filters may no longer show it.

Radiology used to be investigation.
If it becomes radiology, lab-only filters may behave correctly, but shared diagnostic selectors may need both.

Theatre used to be procedure.
If it becomes theatre, minor procedure screens and theatre screens must be separated intentionally.
```

The goal is not to blindly include all new types everywhere.

The goal is to replace old single-type assumptions with explicit, named, testable workflow groups.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text
app/Enums/DepartmentType.php
app/Models/Department.php
app/Services/VisitService.php
app/Services/ConsultationRouteService.php
app/Services/ConsultationFollowUpService.php
app/Http/Controllers/Doctor/ConsultationController.php
app/Http/Controllers/Admin/Visits/VisitController.php
app/Http/Controllers/Admin/Patients/TriageController.php
app/Http/Controllers/Admin/AdmissionsWard/VitalController.php
app/Http/Controllers/Admin/Lab
app/Http/Controllers/Admin/Radiology
app/Http/Controllers/Admin/Procedures
app/Http/Controllers/Admin/Theatre
app/Http/Controllers/Admin/Emergency
app/Http/Controllers/Admin/Store
app/Http/Requests/Concerns/ValidatesVisitServiceRoutes.php
app/Console/Commands
database/seeders
resources/views
tests
```

Search the full codebase for:

```text
DepartmentType::CONSULTATION
DepartmentType::INVESTIGATION
DepartmentType::RADIOLOGY
DepartmentType::PROCEDURE
DepartmentType::THEATRE
DepartmentType::TREATMENT
DepartmentType::PHARMACY
DepartmentType::SUPPORT
DepartmentType::ADMINISTRATIVE
where('type'
whereIn('type'
department_type
service_catalog.department_type
```

Testing instruction:

```text
Do not run the wide full application test suite after this phase.
Run focused workflow-routing tests only.
The wide full-suite test remains deferred until the current implementation batch is complete.
```

---

## 2. Core Rule

Do not confuse department type with service type.

```text
Service type = what is being billed/rendered.
Department type = operational owner / workflow destination.
```

A consultation service can be routed to a consultation department.

An emergency consultation may be routed to an emergency department.

A radiology service may still be an investigation-like clinical request, but the destination is a radiology department.

A theatre case may include procedures, but theatre is not the same workflow as minor procedure.

Do not replace service category logic with department type logic.

---

## 3. Create Explicit Workflow Type Groups

Extend `DepartmentType` or create a dedicated helper such as:

```text
DepartmentTypeGroup
DepartmentWorkflowTypes
DepartmentRoutingProfile
```

Use one clean place.

Recommended if keeping inside enum:

```php
public static function consultationWorkflowTypes(): array
public static function emergencyWorkflowTypes(): array
public static function diagnosticRequestTypes(): array
public static function labWorkflowTypes(): array
public static function radiologyWorkflowTypes(): array
public static function procedureWorkflowTypes(): array
public static function theatreWorkflowTypes(): array
public static function treatmentWorkflowTypes(): array
public static function nursingWorkflowTypes(): array
public static function inpatientWorkflowTypes(): array
public static function pharmacyWorkflowTypes(): array
public static function stockIssueDestinationTypes(): array
public static function billableClinicalDestinationTypes(): array
public static function operationalDestinationTypes(): array
```

Each method must return enum values or string values consistently.

Pick one convention and use it everywhere.

Recommended:

```php
public static function consultationWorkflowValues(): array
{
    return [
        self::CONSULTATION->value,
    ];
}
```

Then use:

```php
DepartmentType::consultationWorkflowValues()
```

in Eloquent queries.

---

## 4. Initial Type Group Definitions

Start conservative.

### consultationWorkflowTypes

```text
consultation
```

Do not include emergency by default unless the current OPD consultation workflow truly handles emergency sessions safely.

Emergency should have its own explicit flow.

### emergencyWorkflowTypes

```text
emergency
```

Use this for emergency cases, emergency sessions, emergency/casualty selectors, emergency queues, and emergency billing mapping.

### diagnosticRequestTypes

```text
investigation
radiology
blood_bank
```

Use this only where the workflow is a general diagnostic request selector.

### labWorkflowTypes

```text
investigation
```

Use for lab-only sample/result workflows.

### radiologyWorkflowTypes

```text
radiology
```

Use for imaging-only request/result workflows.

### procedureWorkflowTypes

```text
procedure
```

Use for minor procedure workflows outside full operating theatre.

### theatreWorkflowTypes

```text
theatre
```

Use for surgery, anaesthesia, pre-op, intra-op, post-op, theatre scheduling.

### treatmentWorkflowTypes

```text
treatment
nursing
```

Use carefully for dressing, injection, treatment room, nursing treatment tasks only where existing workflow supports nursing.

### inpatientWorkflowTypes

```text
inpatient
nursing
maternity
```

Use for admissions, ward, bed, inpatient observations, ward vitals where appropriate.

### pharmacyWorkflowTypes

```text
pharmacy
```

Use for dispensing/prescription pharmacy workflows.

### stockIssueDestinationTypes

```text
pharmacy
stores
procedure
theatre
treatment
nursing
inpatient
maternity
blood_bank
emergency
support
```

Use for stock/consumable issue destinations, not for clinical request routing.

### financeWorkflowTypes

```text
finance
```

Use for cashier, billing, claims, receivables, accounting-owner departments.

### storesWorkflowTypes

```text
stores
```

Use for procurement, general stores, stock warehouse.

### recordsWorkflowTypes

```text
records
```

Use for patient records/folders/archive workflows.

---

## 5. Replace Hardcoded Single-Type Filters

Replace old filters only when the workflow intent is clear.

Examples:

### Before

```php
Department::where('type', DepartmentType::INVESTIGATION->value)
```

### After for lab-only screen

```php
Department::whereIn('type', DepartmentType::labWorkflowValues())
```

### After for diagnostic request destination selector

```php
Department::whereIn('type', DepartmentType::diagnosticRequestValues())
```

### Before

```php
Department::where('type', DepartmentType::PROCEDURE->value)
```

### After for minor procedure

```php
Department::whereIn('type', DepartmentType::procedureWorkflowValues())
```

### After for operating theatre

```php
Department::whereIn('type', DepartmentType::theatreWorkflowValues())
```

Do not use broad groups just to make tests pass.

Each replacement must match the workflow purpose.

---

## 6. Consultation / Emergency Routing Cleanup

Inspect:

```text
VisitService
ConsultationRouteService
ConsultationFollowUpService
Doctor\ConsultationController
Admin\Visits\VisitController
Admin\Patients\TriageController
ValidatesVisitServiceRoutes
```

Rules:

```text
General OPD consultation routing should use consultationWorkflowTypes().
Emergency/casualty routing should use emergencyWorkflowTypes().
Emergency visit type must create or route to Emergency/Casualty department/service when configured.
Emergency must not depend on a department still being typed consultation.
```

If existing emergency workflow still depends on consultation route tables, add a controlled bridge:

```text
emergency consultation session may reuse consultation session model,
but department selection must come from emergencyWorkflowTypes().
```

Do not hardcode a department name.

Do not hardcode an Emergency/Casualty service ID.

Use existing service/dept configuration or document missing configuration.

---

## 7. Lab / Radiology Cleanup

Inspect:

```text
Admin\Lab\LabTestController
Admin\Lab\InvestigationItemController
Radiology controllers if present
LinkInvestigationItemsToProductsCommand
service/request destination selectors
```

Rules:

```text
Lab-only screens use labWorkflowTypes().
Radiology-only screens use radiologyWorkflowTypes().
Generic diagnostic selectors use diagnosticRequestTypes().
Investigation item/product links may include investigation + radiology if they truly represent diagnostic items.
```

Expected behavior:

```text
Retyping Radiology from investigation to radiology should not remove it from diagnostic selectors.
Retyping Radiology to radiology should remove it from lab-only sample screens where appropriate.
```

---

## 8. Procedure / Theatre Cleanup

Inspect:

```text
Admin\Procedures
Admin\Theatre
Emergency procedure selectors
ProcedureConsumablesController
Theatre procedure scheduling
```

Rules:

```text
Minor procedure workflows use procedureWorkflowTypes().
Operating theatre workflows use theatreWorkflowTypes().
Generic procedure/service selectors may include procedure + theatre only if the screen supports both.
Emergency procedure picker should be intentionally reviewed:
    if it is minor emergency procedure, use procedureWorkflowTypes()
    if it can refer to theatre surgery, include theatreWorkflowTypes()
```

Expected behavior:

```text
Retyping Theatre from procedure to theatre should not break theatre screens.
Minor procedure screens should not accidentally show theatre departments unless intended.
```

---

## 9. Treatment / Nursing / Inpatient Cleanup

Inspect:

```text
VitalController
Admissions/Ward controllers
treatment controllers
nursing task controllers if present
ward vitals
MAR / observations if present
DepartmentConsumablesController
```

Rules:

```text
Ward/admission screens should use inpatientWorkflowTypes().
Treatment room screens should use treatmentWorkflowTypes().
Nursing station workflows should use nursingWorkflowTypes() or inpatientWorkflowTypes() depending on purpose.
Vitals recorded in ward context should not require consultation department type.
```

Expected behavior:

```text
Retyping wards from administrative to inpatient must not break vitals/admission screens.
Maternity may share inpatient/admission routing until a dedicated maternity workflow exists.
```

---

## 10. Pharmacy Cleanup

Inspect:

```text
pharmacy controllers
prescription controllers
dispensing controllers
LinkDrugsToProductsCommand
```

Rules:

```text
Pharmacy workflow remains pharmacyWorkflowTypes().
Do not mix stores/general inventory with pharmacy dispensing unless the screen is truly general stock.
```

---

## 11. Stores / Stock / Consumables Cleanup

Inspect:

```text
Admin\Store
inventory controllers
stock issue destinations
department consumables
procedure consumables
purchase/procurement destination selectors
```

Rules:

```text
Stores/procurement owner departments use storesWorkflowTypes().
Stock issue destinations can use stockIssueDestinationTypes().
Consumable request destination should include treatment, procedure, theatre, nursing, inpatient, maternity, emergency where appropriate.
```

Do not expose stock-cost data to users without permission.

---

## 12. Finance / Records / Administrative Cleanup

Inspect:

```text
billing
claims
accounting
cashier
patient records
folder management
merge services
admin settings
HR
```

Rules:

```text
Finance/cashier/billing/claims owner departments use financeWorkflowTypes().
Patient folder/records workflows use recordsWorkflowTypes().
System administration workflows use administrativeWorkflowTypes().
```

Do not make financial access depend only on department type.

Permissions remain the security layer.

---

## 13. Department Model Scopes

Update or add model scopes in:

```text
App\Models\Department
```

Existing scopes may include:

```php
scopeConsultation()
scopeAcceptsRequests()
```

Add safe scopes:

```php
scopeOfTypes($query, array $types)
scopeConsultationWorkflow($query)
scopeEmergencyWorkflow($query)
scopeDiagnosticRequestWorkflow($query)
scopeLabWorkflow($query)
scopeRadiologyWorkflow($query)
scopeProcedureWorkflow($query)
scopeTheatreWorkflow($query)
scopeTreatmentWorkflow($query)
scopeInpatientWorkflow($query)
scopePharmacyWorkflow($query)
scopeStockIssueDestination($query)
scopeFinanceWorkflow($query)
scopeStoresWorkflow($query)
scopeRecordsWorkflow($query)
```

Make scopes use the canonical type group methods.

Do not remove existing scopes if other code depends on them.

Instead, update internals safely or add new scopes and migrate callers gradually.

---

## 14. Service Catalog Sync and Routing

Inspect `service_catalog`.

Rules:

```text
service_catalog.department_type must remain synced with department.type when department is retyped.
Service category/type remains independent.
Department type helps route the service to workflow destination.
```

Add or confirm helper methods:

```text
service belongs to department type
service is routable to department type group
service destination department matches expected workflow group
```

Do not create duplicate service records.

Do not change prices.

Do not alter invoice totals.

---

## 15. Backfill Safety After Routing Cleanup

Do not automatically apply the backfill in this phase unless explicitly instructed.

But add a readiness command or option:

```bash
php artisan departments:backfill-types --dry-run --with-routing-check
```

or a new command:

```bash
php artisan departments:routing-readiness
```

Report:

```text
department id
department name
current type
suggested new type
affected workflows
risk level
recommended action
```

Risk examples:

```text
Emergency retype affects consultation filters.
Radiology retype affects lab/investigation filters.
Theatre retype affects procedure filters.
Ward retype affects admissions/vitals filters.
```

This helps decide when to run:

```bash
php artisan departments:backfill-types --apply
```

after the routing cleanup is safe.

---

## 16. Localisation

Only add labels if needed for new diagnostics/readiness screens or command output.

Extend:

```text
lang/en/departments.php
lang/fr/departments.php
```

Potential keys:

```text
workflow_groups
routing_readiness
affected_workflows
risk_level
low_risk
medium_risk
high_risk
safe_to_retype
manual_review_required
```

Maintain EN/FR parity.

Run:

```bash
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Active runtime candidates must remain:

```text
0
```

---

## 17. Tests To Add

Add focused tests only.

Required tests:

```text
DepartmentType workflow group methods return expected values.
Department model workflow scopes return correct departments.
Consultation selectors still show consultation departments.
Emergency selectors show emergency departments after retype.
Lab-only selectors do not include radiology unless intended.
Diagnostic selectors include investigation and radiology.
Radiology selectors show radiology departments.
Minor procedure selectors show procedure departments.
Theatre selectors show theatre departments after retype.
Inpatient/admission selectors show inpatient and maternity/ward-safe departments where intended.
Pharmacy selectors still show pharmacy only.
Stock issue destinations include stores plus operational destinations where intended.
Finance selectors show finance departments.
Records selectors show records departments.
Service catalog sync remains intact after department retype.
Routing readiness command flags emergency/radiology/theatre/ward retype risks.
No workflow selector crashes when department type is null.
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentWorkflowRoutingPhase4Test.php
```

Also run any existing focused tests that directly cover changed workflows, such as:

```bash
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/Emergency
php artisan test tests/Feature/Investigations
php artisan test tests/Feature/Pharmacy
php artisan test tests/Feature/Inventory
```

Run only the focused subset needed for changed files.

Do not run:

```bash
php artisan test
```

unless explicitly instructed.

---

## 18. Minimal Verification Commands

Run only:

```bash
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed PHP files if practical:

```bash
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not apply department backfill unless explicitly instructed.

Do not run the wide full suite.

---

## 19. Documentation

Create:

```text
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_4_WORKFLOW_ROUTING_REPORT.md
```

Include:

```text
summary
workflow type group design
DepartmentType group methods added/updated
Department model scopes added/updated
consultation/emergency routing changes
lab/radiology routing changes
procedure/theatre routing changes
treatment/nursing/inpatient routing changes
pharmacy routing changes
stores/stock routing changes
finance/records/admin routing changes
service catalog sync behavior
routing readiness command output
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
department metrics/reporting registry is Phase 5
multi-department user context switcher is deferred
department backfill apply was not run unless explicitly instructed
some workflows may still share generic routes until dedicated modules exist
```

---

## 20. Acceptance Criteria

Phase 4 is complete only when:

```text
workflow type groups exist and are named clearly
old hardcoded single-type filters are replaced where workflow intent is clear
consultation and emergency routing are separated safely
lab and radiology routing are separated safely
procedure and theatre routing are separated safely
inpatient/nursing/maternity routing no longer depends on administrative/consultation hacks
pharmacy routing remains stable
stores/stock routing remains stable
finance/records/admin routing remains permission-safe
department model scopes support workflow groups
service_catalog.department_type sync remains safe
routing readiness command/report exists
no selector crashes on null/new department types
focused workflow tests pass
active runtime localisation candidates remain 0
EN/FR localisation parity is maintained
route list works
view cache compiles
permissions audit is clean
documentation report is created
full test suite is intentionally deferred
department backfill is not applied unless explicitly instructed
```

Proceed with Department Type Expansion Phase 4 now.
