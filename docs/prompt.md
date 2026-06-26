You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Department Type Expansion — Phase 1: Canonicalisation, Safety Fixes & Safe Backfill

## Goal

Implement the first safe technical phase of the UHMS department type expansion.

The Phase 0 gap analysis has confirmed:

```text id="ld3fnk"
UHMS already has one canonical source of truth:
App\Enums\DepartmentType

The departments.type column is already a plain string.
No destructive schema change is needed.

The new 19 department types already exist in the enum.

However, several enum consumers are incomplete and can throw UnhandledMatchError / HTTP 500 when new department types are used.
```

This phase must make the department type system safe, exhaustive, translated, validated, seedable, and ready for controlled backfill.

Do not build dashboards yet.

Do not build department-aware menus yet.

Do not rework all workflow routing yet.

This phase is about **canonicalisation and safety first**.

---

# 1. Required Context

Read:

```text id="0qg1f5"
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Also inspect:

```text id="12e4tw"
app/Enums/DepartmentType.php
app/Models/Department.php
app/Services/Dashboard/DepartmentDashboardResolver.php
app/Services/Dashboard/DepartmentDashboardService.php
app/Services/SidebarMenuBuilder.php
database/seeders/DepartmentSeeder.php
database/factories/DepartmentFactory.php
resources/views/admin/services/index.blade.php
resources/views/vitals/record.blade.php
resources/views/components/department-services-card.blade.php
lang/en
lang/fr
tests
```

Important testing instruction:

```text id="msivpo"
Do not run the wide full application test suite after this phase.
Run only focused department/localisation safety checks and minimal verification.
The wide full-suite test remains deferred until the current implementation batch is complete.
```

---

# 2. New Canonical Department Type List

The final canonical list is:

```php id="o13hhj"
CONSULTATION = 'consultation';
EMERGENCY = 'emergency';
INVESTIGATION = 'investigation';
RADIOLOGY = 'radiology';
PROCEDURE = 'procedure';
THEATRE = 'theatre';
TREATMENT = 'treatment';
NURSING = 'nursing';
PHARMACY = 'pharmacy';
INPATIENT = 'inpatient';
MATERNITY = 'maternity';
BLOOD_BANK = 'blood_bank';
MORTUARY = 'mortuary';
AMBULANCE = 'ambulance';
RECORDS = 'records';
FINANCE = 'finance';
STORES = 'stores';
SUPPORT = 'support';
ADMINISTRATIVE = 'administrative';
```

Do not remove any of these.

Do not rename values.

Do not use database enum columns.

Store the values as strings.

---

# 3. Critical Breakages To Fix First

The Phase 0 gap analysis found these high-risk breakages:

```text id="qokx8m"
DepartmentType::label() is not exhaustive.
DepartmentType::color() is not exhaustive.
DepartmentType::toVisitStatus() is not exhaustive.
DepartmentDashboardResolver::resolveKey() has a non-exhaustive match.
resources/views/admin/services/index.blade.php can 500 because it loops over DepartmentType::cases() and calls label().
resources/views/vitals/record.blade.php can 500 when a department has one of the new types.
```

Fix these before any backfill or seeder changes.

No department type should be able to crash the UI.

---

# 4. DepartmentType Enum Requirements

Update:

```text id="8deadm"
app/Enums/DepartmentType.php
```

Make every method safe and exhaustive.

Required methods:

```php id="9rawz2"
public function label(): string
public function translatedLabel(): string
public function color(): string
public function toVisitStatus(): ?string
public static function values(): array
public static function options(): array
public static function clinicalTypes(): array
public static function diagnosticTypes(): array
public static function procedureTypes(): array
public static function inpatientTypes(): array
public static function administrativeTypes(): array
public static function operationalTypes(): array
public static function serviceRoutingTypes(): array
```

Rules:

```text id="hrj2u6"
label() must support all 19 types.
translatedLabel() must use departments.types.{value}.
color() must support all 19 types or use a safe default.
toVisitStatus() must not throw for any type. Return null or a safe default for types that do not map to a visit status.
Never leave a match() expression without either all 19 cases or a default arm.
```

Recommended grouping:

```php id="r0spry"
clinicalTypes():
- consultation
- emergency
- procedure
- theatre
- treatment
- nursing
- inpatient
- maternity

diagnosticTypes():
- investigation
- radiology
- blood_bank

procedureTypes():
- procedure
- theatre

inpatientTypes():
- inpatient
- nursing
- maternity

administrativeTypes():
- records
- finance
- administrative

operationalTypes():
- pharmacy
- stores
- support
- ambulance
- mortuary

serviceRoutingTypes():
- consultation
- emergency
- investigation
- radiology
- procedure
- theatre
- treatment
- nursing
- pharmacy
- inpatient
- maternity
- blood_bank
- mortuary
- ambulance
```

Do not use these groups yet to rewrite every workflow. They are for safe future phases.

---

# 5. Translations

Create or update:

```text id="3xpzkc"
lang/en/departments.php
lang/fr/departments.php
```

Add:

```php id="hfzrr2"
return [
    'types' => [
        'consultation' => 'Consultation',
        'emergency' => 'Emergency',
        'investigation' => 'Investigation',
        'radiology' => 'Radiology',
        'procedure' => 'Procedure',
        'theatre' => 'Theatre',
        'treatment' => 'Treatment',
        'nursing' => 'Nursing',
        'pharmacy' => 'Pharmacy',
        'inpatient' => 'Inpatient',
        'maternity' => 'Maternity',
        'blood_bank' => 'Blood Bank',
        'mortuary' => 'Mortuary',
        'ambulance' => 'Ambulance',
        'records' => 'Records',
        'finance' => 'Finance',
        'stores' => 'Stores',
        'support' => 'Support',
        'administrative' => 'Administrative',
    ],
];
```

French labels should be natural and clear:

```php id="jt5blb"
'consultation' => 'Consultation',
'emergency' => 'Urgences',
'investigation' => 'Examens / Analyses',
'radiology' => 'Radiologie',
'procedure' => 'Actes / Procédures',
'theatre' => 'Bloc opératoire',
'treatment' => 'Soins / Traitement',
'nursing' => 'Soins infirmiers',
'pharmacy' => 'Pharmacie',
'inpatient' => 'Hospitalisation',
'maternity' => 'Maternité',
'blood_bank' => 'Banque de sang',
'mortuary' => 'Morgue',
'ambulance' => 'Ambulance',
'records' => 'Archives médicales',
'finance' => 'Finance',
'stores' => 'Magasin / Stocks',
'support' => 'Support',
'administrative' => 'Administration',
```

Run EN/FR parity checks.

Active runtime localisation candidates must remain:

```text id="5fdwuy"
0
```

---

# 6. Dashboard Resolver Safety

Update:

```text id="w7bkmj"
app/Services/Dashboard/DepartmentDashboardResolver.php
```

Make the department type to dashboard key mapping exhaustive and safe.

Suggested mapping:

```php id="6gctrq"
consultation => consultation
emergency => emergency
investigation => investigation
radiology => investigation
procedure => theatre or generic
theatre => theatre
treatment => generic
nursing => admission
pharmacy => pharmacy
inpatient => admission
maternity => admission
blood_bank => blood_bank
mortuary => generic
ambulance => generic
records => reception
finance => accounting
stores => stock
support => generic
administrative => management
```

Rules:

```text id="uh7i3l"
No department type should crash /admin/my-dashboard.
Unknown or null department type must fall back to role-based dashboard or generic dashboard.
Admin/Super Admin must retain broad access.
Do not build new dashboards in this phase.
Only map the new types safely to existing dashboard keys or generic fallback.
```

If `DepartmentDashboardService::build()` already has default fallback, keep it.

---

# 7. Views Safety

Fix all views that call:

```php id="544xro"
$type->label()
$department->type->label()
```

Known files:

```text id="7ryp9s"
resources/views/admin/services/index.blade.php
resources/views/vitals/record.blade.php
resources/views/components/department-services-card.blade.php
```

Rules:

```text id="98v7ax"
Use translatedLabel() where possible.
Guard null department type.
No view should crash if department type is null or unknown.
No hardcoded English-only labels in Blade.
```

Example safe display:

```php id="avb3zv"
$department->type?->translatedLabel() ?? __('common.not_specified')
```

---

# 8. Validation

Current department validation uses:

```php id="nvx8wj"
Rule::enum(DepartmentType::class)
```

Keep it.

Also inspect service forms that accept:

```text id="24hlic"
department_type
```

If any service form accepts raw strings, validate against:

```php id="q8em1s"
Rule::enum(DepartmentType::class)
```

or against `DepartmentType::values()` depending on existing Laravel version compatibility.

Do not introduce database enum validation.

---

# 9. Seeder Updates

Update:

```text id="wdq0fn"
database/seeders/DepartmentSeeder.php
database/factories/DepartmentFactory.php
```

Use precise department types.

Required mappings:

```text id="p8gfna"
Emergency / Casualty => emergency
OPD / Consultation / Consulting Room => consultation
Laboratory / Lab => investigation
Radiology / X-Ray / Ultrasound / Imaging => radiology
Theatre / Surgery / Operating Room => theatre
Procedure Room / Minor Procedure => procedure
Treatment Room / Dressing / Injection / Physiotherapy => treatment
Nursing Station / Ward Nursing => nursing
Pharmacy / Dispensary => pharmacy
Ward / Admission / ICU / NICU / Inpatient => inpatient
Maternity / Delivery / Antenatal / Postnatal / Family Planning => maternity
Blood Bank / Blood Storage => blood_bank
Mortuary => mortuary
Ambulance / Transport => ambulance
Records / Folder / Archive => records
Billing / Cashier / Accounts / Claims / Finance => finance
Stores / Procurement / Inventory / Warehouse => stores
Maintenance / IT / Laundry / Security / CSSD / Housekeeping => support
HR / Admin / Management / Settings => administrative
```

Important:

```text id="2tqzsr"
If a seeded department name combines two meanings, do not guess silently.
Example: Theatre / Procedures may need to become theatre, or the seeder may need separate Theatre and Minor Procedures departments.
Document the decision.
```

Do not reseed production data automatically.

---

# 10. Backfill Command

Create a safe command:

```bash id="bx898w"
php artisan departments:backfill-types --dry-run
php artisan departments:backfill-types --apply
```

Command class suggestion:

```text id="edj5mt"
App\Console\Commands\BackfillDepartmentTypesCommand
```

Behavior:

```text id="56lpqo"
Inspect existing departments.
Suggest a precise new department type from department name/code/current type.
Assign confidence: high, medium, low.
Explain the reason.
Dry-run by default.
Apply only when --apply is provided.
Only auto-apply high-confidence mappings.
Medium/low confidence must be reported for manual review unless --force is explicitly provided.
Re-sync service_catalog.department_type for updated departments.
Do not delete departments.
Do not alter historical records.
Do not change department_id references.
```

Output columns:

```text id="4l8u72"
id
name
code
old_type
suggested_type
confidence
reason
action
```

Rules:

```text id="1o9zwo"
Unknown departments stay unchanged.
Blank types can be suggested but not force-applied unless high confidence.
Generic old values can be suggested for precision.
Log summary counts.
```

Suggested matching rules:

```text id="6azgdn"
emergency|casualty => emergency
opd|consultation|clinic|consulting => consultation
laboratory|lab => investigation
radiology|x-ray|xray|ultrasound|imaging|ct|mri => radiology
theatre|surgery|operating => theatre
procedure|minor procedure => procedure
treatment|dressing|injection|physio|physiotherapy => treatment
nursing|nurse station|mar => nursing
pharmacy|dispensary => pharmacy
ward|admission|inpatient|icu|nicu => inpatient
maternity|delivery|antenatal|postnatal|family planning => maternity
blood bank|blood storage|blood => blood_bank
mortuary|morgue => mortuary
ambulance|transport => ambulance
records|folder|archive => records
billing|cashier|accounts|claims|finance => finance
stores|store|procurement|inventory|warehouse => stores
maintenance|it|laundry|security|cssd|housekeeping|biomedical => support
hr|admin|management|settings => administrative
```

---

# 11. Unmapped / Ambiguous Report Command

Add either a second command or an option:

```bash id="i9p7kv"
php artisan departments:types-report
```

or:

```bash id="9xha05"
php artisan departments:backfill-types --report
```

Report:

```text id="c15fx0"
departments with null type
departments with old generic type that could be made more precise
departments with ambiguous name
departments whose service_catalog.department_type differs from departments.type
departments with no services
departments with services across multiple department types
```

This report will help before Phase 4 workflow routing cleanup.

---

# 12. Service Catalog Sync

Because `service_catalog.department_type` is a denormalised copy, add safe sync logic.

When department type is updated through:

```text id="4xgbxw"
DepartmentController
departments:backfill-types command
```

then related services should be synced:

```text id="u7bjj5"
service_catalog.department_type = departments.type
```

Only if:

```text id="4e0991"
service_catalog.department_id = departments.id
```

Do not overwrite service category/type.

Do not replace billable service type.

Remember:

```text id="jeqdgt"
department type is operational owner/routing
service type is billable/service category
```

---

# 13. Tests To Add

Add focused tests only.

Do not run the full suite.

Required tests:

```text id="ls7hpt"
DepartmentType label supports all 19 cases.
DepartmentType translatedLabel supports all 19 cases.
DepartmentType color supports all 19 cases.
DepartmentType toVisitStatus does not throw for any case.
Department dashboard resolver does not throw for any department type.
Services index page renders with all department types.
Vitals record page renders with a new department type.
Department form accepts all 19 enum values.
Invalid department type is rejected.
Backfill dry-run does not change data.
Backfill high-confidence emergency mapping works with --apply.
Backfill low-confidence mapping is not auto-applied.
Service catalog department_type is synced after department retype.
Unmapped report lists null/ambiguous department types.
EN/FR department labels exist.
```

Allowed focused commands:

```bash id="g1lndv"
php artisan test tests/Feature/Departments/DepartmentTypeExpansionPhase1Test.php
```

Do not run:

```bash id="xzcqsy"
php artisan test
```

unless explicitly instructed.

---

# 14. Minimal Verification Commands

Run only:

```bash id="6gr44m"
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed PHP files if practical:

```bash id="z94kfn"
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not run migrations unless you add a table/column, which should not be necessary in this phase.

Do not run the wide full suite.

---

# 15. Documentation

Create:

```text id="cd7ihr"
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
```

Include:

```text id="w301w5"
summary
enum changes
translation files added
dashboard resolver changes
view safety changes
validation changes
seeder/factory changes
backfill command behavior
unmapped report behavior
service_catalog sync behavior
tests added
minimal verification commands run
known limitations
next recommended phase
```

---

# 16. Acceptance Criteria

Phase 1 is complete only when:

```text id="oqb18l"
DepartmentType supports all 19 types safely.
No DepartmentType match expression can throw for the 19 canonical values.
Department labels are localised EN/FR.
DepartmentDashboardResolver handles all 19 types safely.
Services settings page no longer crashes.
Vitals record page no longer crashes for new department types.
Department forms accept all 19 values and reject invalid values.
Seeder/factory mappings use the precise new department types.
Backfill command exists and defaults to dry-run.
Backfill command can apply high-confidence mappings.
Low-confidence mappings require manual review or explicit force.
Service catalog department_type can be synced safely after department retype.
Unmapped/ambiguous department report exists.
Active runtime localisation candidates remain 0.
EN/FR localisation parity is maintained.
Route list works.
View cache compiles.
Permissions audit is clean.
Documentation report is created.
No production data is changed unless the explicit --apply command is run.
Full test suite is intentionally deferred.
```

Proceed with Department Type Expansion Phase 1 now.
