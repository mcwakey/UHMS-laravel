You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to implement two connected features:

1. **Department-aware Store / Stock Management**
2. **Procedure Catalogue based on Procedure-type department services**, similar to the Investigation Catalogue

Focus only on Store/Stock, product-department availability, stock locations, consumable usage, and Procedure Catalogue/templates.

Do not refactor unrelated modules.

---

# 1. Main Objective

Build a clean inventory and procedure catalogue architecture where:

* Store/Procurement is the only module allowed to create products.
* Products can be drugs, consumables, reagents, supplies, or equipment.
* Products can be linked to one or more departments.
* Stock locations are linked to departments.
* Departments consume stock only from their own linked stock location.
* Pharmacy cannot create drugs directly.
* Lab/Investigation cannot create investigation consumables directly.
* Procedure/Theatre cannot create consumables directly.
* Investigation Catalogue loads services from investigation-type departments.
* Procedure Catalogue loads services from procedure/theatre-type departments.
* Procedure services can have configurable report templates, sections, fields, and default consumables.

---

# 2. Core Store / Stock Rules

## Store Controls Products

Only Store/Procurement users can create and manage products.

Departments such as:

* Pharmacy
* Laboratory
* Investigation departments
* Theatre
* Ward
* X-ray
* Scan

must not create products directly.

They can only use products that Store has created and made available to them.

---

# 3. Product Types

Products should support types such as:

```text
DRUG
CONSUMABLE
REAGENT
SUPPLY
EQUIPMENT
GENERAL_ITEM
```

Examples:

```text
Paracetamol → DRUG
Syringe → CONSUMABLE
Malaria test strip → REAGENT
Gloves → CONSUMABLE
X-ray film → CONSUMABLE
Sutures → CONSUMABLE
Theatre instrument → EQUIPMENT
```

Use enum/constants if the project already uses enums.

---

# 4. Products Linked to Departments

A product can be linked to one or more departments.

Examples:

```text
Paracetamol → Pharmacy
Gloves → Pharmacy, Lab, Theatre, Ward
Malaria RDT Kit → Lab
X-ray Film → X-ray
Sutures → Theatre
```

Create or update pivot table:

```text
product_department
- id
- product_id
- department_id
- is_active
- created_at
- updated_at
```

Rules:

* A department can only see/use products linked to it.
* Product availability is controlled from Store/Procurement.
* Department users cannot link products to themselves unless they have Store/Admin permission.

---

# 5. Stock Locations Linked to Departments

Each stock location must belong to a department.

Examples:

```text
Main Store Location → Store Department
Pharmacy Stock Location → Pharmacy Department
Laboratory Stock Location → Laboratory Department
Theatre Stock Location → Theatre Department
Ward Stock Location → Ward Department
X-ray Stock Location → X-ray Department
```

Update or create `stock_locations`:

```text
id
name
department_id
is_main
is_active
created_at
updated_at
```

Rules:

* Main Store location must be linked to the Store department.
* Only one location should be marked as `is_main = true`, unless system design allows multiple main locations.
* Each department should have one default stock location, but the system may allow multiple later.
* Department stock usage must deduct from the stock location linked to that department.

---

# 6. Main Store Transfer Rules

Transfers should involve the Main Store at first.

Allowed:

```text
Main Store → Department Stock Location
Department Stock Location → Main Store
```

Not allowed initially:

```text
Pharmacy → Laboratory
Ward → Theatre
Department → Department
```

Unless a future system setting enables inter-department transfers.

Transfer rules:

* Source and destination must be different.
* Quantity must be greater than zero.
* Source location must have enough stock.
* Transfer must create two stock movements:

  * `TRANSFER_OUT` from source
  * `TRANSFER_IN` into destination
* Both movements must be linked to the same transfer record.

---

# 7. Stock Movement System

Stock movements remain the source of truth.

```text
stock_movements = source of truth
stock_balances = fast current stock cache
```

Current stock:

```text
Total IN movements - Total OUT movements
```

Do not use product/drug quantity as current stock.

Required movement types:

```text
OPENING_STOCK
PURCHASE_RECEIVED
PHARMACY_DISPENSED
INVESTIGATION_CONSUMED
PROCEDURE_CONSUMED
WARD_CONSUMED
TRANSFER_IN
TRANSFER_OUT
RETURN_IN
RETURN_OUT
ADJUSTMENT_IN
ADJUSTMENT_OUT
DAMAGED
EXPIRED
REVERSAL_IN
REVERSAL_OUT
```

---

# 8. Stock Movement Tables

Use existing tables where possible. Add migrations if missing.

## products

```text
id
name
code
product_type
unit
description nullable
reorder_level nullable
is_active
created_by nullable
created_at
updated_at
```

Do not store current quantity as the main stock value.

If an old quantity field exists, stop using it as current stock.

---

## stock_locations

```text
id
name
department_id
is_main
is_active
created_at
updated_at
```

---

## stock_movements

```text
id
product_id
stock_location_id
movement_type
direction
quantity
unit_cost nullable
batch_no nullable
expiry_date nullable
source_type nullable
source_id nullable
performed_by nullable
movement_date
notes nullable
created_at
updated_at
```

Direction:

```text
IN
OUT
```

---

## stock_balances

```text
id
product_id
stock_location_id
quantity_on_hand
last_movement_at
created_at
updated_at
```

Add unique constraint:

```text
product_id + stock_location_id
```

---

# 9. Department Stock Consumption Rules

## Pharmacy

Pharmacy can only dispense products where:

```text
product.type = DRUG
product is linked to Pharmacy department
stock exists in Pharmacy stock location
```

When pharmacy dispenses:

```text
movement_type = PHARMACY_DISPENSED
direction = OUT
stock_location = Pharmacy stock location
source_type = dispensing_item / prescription_item
```

Pharmacy must not create drugs.

---

## Laboratory / Investigation

When lab/investigation users enter results, they should be able to specify consumables used.

Consumables must be deducted from the stock location linked to that investigation department.

Example:

```text
Malaria Test result entry:
- Malaria RDT Kit × 1
- Gloves × 1
- Lancet × 1
```

When saved:

```text
movement_type = INVESTIGATION_CONSUMED
direction = OUT
stock_location = Lab stock location
source_type = investigation_result
```

Investigation users must not create products.

---

## Theatre / Procedure

When theatre/procedure users record a procedure, they should be able to specify consumables used.

Consumables must be deducted from the Theatre stock location.

Example:

```text
Appendectomy:
- Surgical gloves × 4
- Sutures × 2
- Gauze × 10
```

When saved:

```text
movement_type = PROCEDURE_CONSUMED
direction = OUT
stock_location = Theatre stock location
source_type = procedure_request / procedure_record
```

Theatre users must not create products.

---

# 10. Service Default Consumables

Investigation services and procedure services should support default consumables.

Create or update table:

```text
service_consumables
- id
- service_id
- product_id
- default_quantity
- is_required
- notes nullable
- created_at
- updated_at
```

Rules:

* Service consumables are configured against a service.
* Product must be linked to the department that owns the service.
* Default consumables are preloaded during result/procedure entry.
* User can confirm, adjust, remove, or add actual consumables used if allowed.
* Actual stock deduction happens only when actual usage is saved.

---

# 11. Actual Consumable Usage

Create or update table:

```text
consumable_usages
- id
- visit_id
- patient_id
- service_id
- source_type
- source_id
- product_id
- stock_location_id
- quantity_used
- stock_movement_id nullable
- used_by
- used_at
- notes nullable
- created_at
- updated_at
```

Examples:

```text
source_type = investigation_result
source_id = investigation_results.id

source_type = procedure_request
source_id = procedure_requests.id

source_type = ward_care
source_id = ward_care_records.id
```

Rules:

* Every actual consumable usage should create a stock OUT movement.
* Every consumable usage should link to the created stock movement.
* If stock is insufficient, block saving unless authorized negative-stock override exists.
* Product must be available to the department.
* Stock must deduct from the department’s stock location.

---

# 12. Investigation Consumable Workflow

When entering investigation results:

1. Determine the investigation department.
2. Get the department’s stock location.
3. Load default consumables from `service_consumables`.
4. Show consumables in the result entry form.
5. Allow user to confirm/edit actual quantity used.
6. On result save:

   * save result values
   * save consumable usages
   * create stock OUT movements
   * update stock balances

Rules:

* Not every investigation must require consumables.
* If service has required consumables, they must be confirmed before finalizing result.
* Result entry should not deduct stock from Main Store unless the investigation department is actually linked to Main Store, which should not normally happen.

---

# 13. Procedure Consumable Workflow

When recording procedure/theatre notes:

1. Determine the procedure service.
2. Determine the procedure/theatre department.
3. Get the theatre/procedure stock location.
4. Load default consumables from `service_consumables`.
5. Allow theatre staff to confirm/edit actual consumables used.
6. On procedure stage save or finalization:

   * save clinical procedure data
   * save consumable usages
   * create stock OUT movements
   * update stock balances

Rules:

* Procedure consumables must not deduct from pharmacy stock.
* Procedure consumables must deduct from the stock location linked to the procedure/theatre department.
* If stock is insufficient, block unless authorized override exists.

---

# 14. Procedure Catalogue

Build the **Procedure Catalogue** using the same service-based approach as Investigation Catalogue.

Do not create separate procedure items detached from services.

## Procedure Catalogue Source

The Procedure Catalogue should list services where:

```text
service.department.type = procedure
```

or, if the system uses theatre terminology:

```text
service.department.type = theatre
```

Use the project’s existing department type naming convention.

Examples:

```text
Appendectomy
Caesarean Section
Wound Debridement
Suturing
Circumcision
Hernia Repair
```

These are services under procedure/theatre-type departments.

---

# 15. Procedure Catalogue Structure

Correct structure:

```text
Procedure Catalogue
    → Procedure Service
        → Report Templates
        → Sections / Headers
        → Fields / Criteria
        → Default Consumables
```

The service controls:

* billing
* procedure request
* procedure report template
* default consumables

Do not create a separate catalogue test/procedure entity that duplicates the service.

---

# 16. Procedure Report Templates

For each procedure service, allow configuration of templates.

Templates may include:

1. Pre-op template
2. Anaesthesia template
3. Surgeon operative template
4. Post-op template
5. Full procedure report template

Each template can have:

* sections/headers
* fields/criteria
* input types
* options
* required/optional flags
* sort order

---

# 17. Suggested Procedure Template Tables

Use existing generic template tables if available. Otherwise create clean tables.

## procedure_template_sections

```text
id
service_id
template_type
name
description nullable
sort_order
is_active
created_at
updated_at
```

`template_type` examples:

```text
PRE_OP
ANAESTHESIA
OPERATIVE_NOTE
POST_OP
FULL_REPORT
```

## procedure_template_fields

```text
id
service_id
section_id nullable
template_type
label
field_key
input_type
options nullable
default_value nullable
is_required
sort_order
is_active
created_at
updated_at
```

Input types:

```text
text
textarea
number
select
checkbox
date
time
datetime
file
```

Example:

```text
Field: Anaesthesia Type
input_type: select
options: Local, Regional, Spinal, General, Sedation
```

---

# 18. Procedure Template Values

When a procedure is performed, save values entered against the configured template fields.

Create or update:

```text
procedure_template_values
- id
- procedure_request_id
- service_id
- template_field_id
- template_type
- value nullable
- recorded_by
- recorded_at
- created_at
- updated_at
```

Rules:

* Store values against the field used at that time.
* Preserve labels/unit/options snapshot if the system already uses snapshot fields.
* Future template changes must not corrupt old procedure reports.
* If a field is required, it must be filled before completing that stage.

---

# 19. Procedure Template Workflow

When theatre opens a scheduled procedure:

1. System identifies procedure service.
2. System loads templates configured for that service.
3. User enters values for the current stage:

   * Pre-op
   * Anaesthesia
   * Operative note
   * Post-op
4. System saves values.
5. System validates required fields.
6. System updates procedure status according to workflow.
7. System makes values available in procedure timeline and report.

---

# 20. Procedure Catalogue UI

Create or update Procedure Catalogue page.

First screen:

```text
List of procedure services
```

Only show services under procedure/theatre-type departments.

When service is selected, allow configuration of:

* template sections
* template fields
* default consumables

UI should be similar to Investigation Catalogue.

Sections:

```text
Service Details
Templates
Default Consumables
Preview
```

Template management should allow:

* create section
* edit section
* delete/deactivate section
* create field
* edit field
* delete/deactivate field
* reorder sections/fields

Default consumables management should allow:

* add product
* set default quantity
* mark required/not required
* remove/deactivate consumable

---

# 21. Investigation Catalogue Consistency

Ensure Investigation Catalogue and Procedure Catalogue follow the same design philosophy:

## Investigation Catalogue

```text
Investigation Service
    → Headers / Categories
    → Criteria
    → Default Consumables
```

## Procedure Catalogue

```text
Procedure Service
    → Template Sections
    → Template Fields
    → Default Consumables
```

Both are based on services.

Do not create separate detached “test” or “procedure item” records.

---

# 22. Product Availability in Catalogues

When configuring default consumables for a service:

* Only show products linked to the service’s department.
* For Investigation services, show products linked to the investigation department.
* For Procedure services, show products linked to the procedure/theatre department.
* Do not show products not available to that department.
* Do not allow department users to create new products from the catalogue page.

If a needed product is missing, user must request Store/Procurement to create/link it.

---

# 23. Services

Create or update these services:

```text
ProductService
StockLocationService
StockMovementService
StockBalanceService
StockTransferService
ConsumableUsageService
ServiceConsumableService
ProcedureCatalogueService
ProcedureTemplateService
InvestigationCatalogueService
```

## ProductService

Handles:

* product creation by Store/Admin
* product-department linking
* product type management
* product availability

## StockLocationService

Handles:

* department stock locations
* main store location
* default stock location resolution

Required method:

```php
getDefaultLocationForDepartment(Department $department): StockLocation
```

## ConsumableUsageService

Handles:

* loading default consumables
* validating actual consumables
* creating consumable usage rows
* creating stock OUT movements
* linking movements to usage records

Required method:

```php
recordUsageForSource(
    Visit $visit,
    Service $service,
    string $sourceType,
    int $sourceId,
    array $items,
    User $user
): void
```

## ProcedureCatalogueService

Handles:

* loading procedure services
* managing template sections
* managing template fields
* managing default consumables

## ProcedureTemplateService

Handles:

* loading templates for a procedure service
* validating required fields
* saving template values
* building printable reports

---

# 24. Validation Rules

## Product

* name required
* code unique if used
* product_type required
* unit required
* only Store/Admin can create product
* department links must reference valid departments

## Stock Location

* name required
* department_id required
* main location must belong to Store department
* department location must be active before use

## Transfer

* source and destination required
* transfer must involve Main Store unless inter-department transfer is enabled
* source and destination must be different
* quantity > 0
* source must have enough stock

## Consumable Usage

* product required
* product must be linked to service department
* stock location must match service department
* quantity_used > 0
* sufficient stock required unless override allowed
* required consumables must be confirmed

## Procedure Template

* service must belong to procedure/theatre department
* section name required
* field label required
* input_type required
* required fields must have values before completing stage

---

# 25. Permissions

Add or verify permissions:

```text
product.create
product.edit
product.link_departments
stock_location.manage
stock.transfer
stock.adjust
stock.return
stock.view
stock.override_negative
service_consumable.manage
consumable_usage.record
procedure_catalogue.view
procedure_catalogue.manage
procedure_template.manage
```

Department restrictions:

* Pharmacy users cannot create products.
* Lab users cannot create products.
* Theatre users cannot create products.
* Only Store/Admin can create products and link them to departments.
* Department users can consume products assigned to their department.

---

# 26. Frontend Requirements

## Store / Procurement

Create or update pages for:

* Product list
* Create/edit product
* Link product to departments
* Stock locations
* Stock balances
* Stock transfer
* Stock ledger
* Stock adjustment
* Returns
* Damaged/expired stock

## Department Usage

Update these screens:

* Pharmacy dispensing
* Investigation result entry
* Theatre/procedure recording

They must consume products from their department stock location.

## Procedure Catalogue

Create/update:

```text
ProcedureCatalogue/Index.vue
ProcedureCatalogue/Show.vue
ProcedureCatalogue/Templates.vue
ProcedureCatalogue/Consumables.vue
```

## Investigation Catalogue

Update to support default consumables if not already implemented.

---

# 27. Data Integrity Rules

* Store is the only product creator.
* Department users cannot create products directly.
* Stock locations must belong to departments.
* Department usage must deduct from that department’s stock location.
* Main Store belongs to Store department.
* Transfers should involve Main Store by default.
* Stock movements remain the source of truth.
* Stock balances are cache only.
* Service default consumables do not deduct stock until actual usage is saved.
* Actual consumable usage must create stock movements.
* Procedure Catalogue must be service-based.
* Investigation Catalogue must be service-based.
* Do not duplicate services into separate catalogue item records.

---

# 28. Performance Rules

* Use stock_balances for current stock display.
* Do not calculate stock from all movements on every page.
* Eager-load product departments where needed.
* Load default consumables only for selected service.
* Paginate product and stock movement lists.
* Avoid N+1 queries in stock reports and catalogue pages.
* Cache product availability per department where safe.

---

# 29. Testing / Verification

Add or update tests for:

1. Store can create product.
2. Pharmacy cannot create product.
3. Lab cannot create product.
4. Product can be linked to multiple departments.
5. Department sees only linked products.
6. Stock location belongs to department.
7. Pharmacy dispensing deducts from Pharmacy stock location.
8. Lab result consumables deduct from Lab stock location.
9. Theatre procedure consumables deduct from Theatre stock location.
10. Main Store can transfer to department location.
11. Department can return stock to Main Store.
12. Department-to-department transfer is blocked unless enabled.
13. Procedure Catalogue loads only procedure/theatre department services.
14. Procedure template fields save and load correctly.
15. Procedure template values save against procedure request.
16. Default consumables preload for procedure service.
17. Actual consumable usage creates stock movements.
18. Stock balance updates correctly after usage.

---

# 30. Deliverables

Provide:

1. New/updated migrations.
2. New/updated models and relationships.
3. Product-department linking implementation.
4. Stock location department linking.
5. Main Store rules.
6. Stock transfer rules.
7. Department consumption rules.
8. Consumable usage implementation.
9. Service default consumables implementation.
10. Procedure Catalogue implementation.
11. Procedure template sections/fields/values.
12. Procedure default consumables.
13. Investigation Catalogue default consumable update if needed.
14. Updated Vue/Inertia pages.
15. Updated validation requests.
16. Updated permissions.
17. Tests or verification notes.
18. List of modified files.
19. Remaining TODOs if any.

---

# 31. Important Rules

Do not let Pharmacy create drugs.

Do not let Lab/Investigation create products.

Do not let Theatre create products.

Do not use product quantity as current stock.

Do not deduct stock from Main Store when department stock should be used.

Do not allow direct department-to-department transfer unless explicitly enabled.

Do not create Procedure Catalogue items detached from services.

Do not create Investigation Catalogue tests detached from services.

Do not deduct default consumables until actual usage is saved.

Do not bypass StockMovementService.

Do not bypass StockBalanceService.

Do not break existing pharmacy, investigation, or procedure workflows.

Now inspect the current UHMS implementation and apply these changes carefully.
