# UHMS Stores Department Workspace — Inventory Operations and `/stores/*` Route Architecture

Implement a dedicated **Stores Department Workspace** for UHMS.

This workspace is for Stores and inventory staff managing hospital supplies from receipt and storage through departmental requisitions, approvals, reservations, issuing, transfers, returns, adjustments, stock counts, expiry monitoring, quarantine, disposal, and inventory reporting.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency
* Inpatient
* Investigations
* Pharmacy

The configured department type is:

```php
DepartmentType::STORES
```

The browser workspace must use:

```text
/stores/*
```

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::STORES
```

the user should experience UHMS as a dedicated Stores application with:

* A Stores-specific sidebar menu
* A Stores operations dashboard
* Stores-specific breadcrumbs
* Stores-specific route names
* Consistent `/stores/*` URLs
* Department requisition worklists
* Goods receipt and stock-entry workflows
* Stock issuing and transfer workflows
* Batch, lot, serial, and expiry tracking
* Stock-count and reconciliation workflows
* Stock adjustment and approval controls
* Quarantine, recall, damage, and disposal management
* Permission-controlled menu visibility
* Active-department and stock-location scoping
* Transactional stock-ledger integrity
* Reuse of existing products, inventory, procurement, billing, pharmacy, department, reporting, and audit logic

Do not duplicate core product, stock, inventory, stock-ledger, requisition, purchase, receiving, transfer, adjustment, or reporting logic merely to create the Stores workspace.

---

# 1. Core Functional Requirement

When the active department type is `stores`, all supported browser pages used by Stores staff must appear under the `/stores` URL prefix.

Examples:

```text
/stores
/stores/dashboard

/stores/requisitions
/stores/requisitions/pending
/stores/requisitions/approved
/stores/requisitions/partially-issued
/stores/requisitions/completed
/stores/requisitions/rejected
/stores/requisitions/{requisition}

/stores/issues
/stores/issues/pending
/stores/issues/in-progress
/stores/issues/completed
/stores/issues/{issue}

/stores/receipts
/stores/receipts/pending
/stores/receipts/completed
/stores/receipts/{receipt}

/stores/transfers
/stores/transfers/pending
/stores/transfers/in-transit
/stores/transfers/received
/stores/transfers/{transfer}

/stores/returns
/stores/adjustments
/stores/stock-counts
/stores/reconciliations

/stores/products
/stores/products/{product}

/stores/stock
/stores/stock/{stockItem}
/stores/batches
/stores/batches/{batch}
/stores/expiries
/stores/low-stock
/stores/stock-outs

/stores/quarantine
/stores/recalls
/stores/damages
/stores/disposals

/stores/suppliers
/stores/purchase-requests
/stores/purchase-orders
/stores/goods-received

/stores/handoffs
/stores/reports
```

A Stores user should not enter through:

```text
/stores/requisitions/{requisition}
```

and later be redirected to generic URLs such as:

```text
/requisitions/{requisition}
/stock/{stockItem}
/products/{product}
/inventory/transfers/{transfer}
/goods-receipts/{receipt}
```

All browser navigation, forms, redirects, breadcrumbs, worklists, stock links, product links, dashboard cards, notifications, approvals, and report drilldowns must preserve the Stores workspace context.

---

# 2. Stores Workspace Scope

The Stores workspace is responsible for hospital inventory operations, including:

1. Product and supply visibility
2. Active stock-location visibility
3. Departmental requisitions
4. Requisition approval
5. Stock reservation
6. Full and partial issuing
7. Departmental receipt acknowledgement
8. Goods receipt
9. Purchase-order receipt where supported
10. Direct stock receipt where authorized
11. Batch and lot creation
12. Serial-number capture where supported
13. Expiry-date capture
14. Stock transfers
15. Transfer dispatch
16. Transfer receipt
17. Departmental returns
18. Supplier returns where supported
19. Stock adjustments
20. Adjustment approval
21. Physical stock counts
22. Cycle counts
23. Stock reconciliation
24. Variance investigation
25. Low-stock monitoring
26. Stockout monitoring
27. Expiry monitoring
28. Quarantine
29. Product recall
30. Damage recording
31. Disposal and destruction
32. Stock valuation
33. Inventory movement history
34. Stock-ledger reporting
35. Supplier and procurement awareness where supported
36. Stores handoffs and escalations
37. Inventory reports and analytics

The Stores workspace must remain distinct from:

* Pharmacy prescription dispensing
* Nursing medication administration
* Departmental service consumption
* Finance payments
* Procurement approval where procurement is independently controlled
* Clinical ordering
* Patient billing
* Pharmacy retail or patient collection

Stores may manage medical supplies, medications, consumables, equipment items, and general hospital products according to configuration, but it must not perform Pharmacy dispensing or clinical administration actions.

---

# Phase 1 — Inspect the Existing Stores and Inventory Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How existing department workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. How `DepartmentType::STORES` is currently mapped by:

   * dashboard resolver
   * menu profile service
   * capability resolver
   * stock-location resolver
6. Existing routes, controllers, models, services, policies, commands, jobs, and views for:

   * products
   * product categories
   * product units
   * stock items
   * stock locations
   * batches
   * lots
   * serial numbers
   * expiry dates
   * stock movements
   * stock ledger
   * departmental requisitions
   * requisition approvals
   * stock reservations
   * stock issues
   * goods receipts
   * purchase requests
   * purchase orders
   * suppliers
   * stock transfers
   * departmental returns
   * supplier returns
   * stock adjustments
   * stock counts
   * reconciliation
   * quarantine
   * recall
   * damage
   * disposal
   * reporting
7. Existing requisition statuses.
8. Existing issue statuses.
9. Existing transfer statuses.
10. Existing goods-receipt and purchase-order statuses.
11. Existing stock-count and reconciliation processes.
12. Existing batch-selection policies.
13. Existing stock valuation method.
14. Existing approval thresholds.
15. Existing negative-stock protections.
16. Existing department and stock-location scoping.
17. Existing stock integration with:

* Pharmacy
* Investigations
* Radiology
* Theatre
* Inpatient wards
* Nursing

18. Existing audit-event infrastructure.
19. Existing views that hardcode generic routes such as:

```php
route('requisitions.show', $requisition)
route('stock.show', $stockItem)
route('products.show', $product)
route('transfers.show', $transfer)
route('goods-receipts.show', $receipt)
```

Do not create a competing stock, inventory, requisition, transfer, receipt, menu, or routing framework where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Department capability resolver
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Stock location resolver
* Product services
* Stock-ledger services
* Requisition services
* Reservation services
* Issue services
* Receipt services
* Transfer services
* Stock-count services
* Adjustment services
* Authorization policies
* Activity logging

---

# Phase 2 — Stores Workspace Route Group

Create a dedicated Stores route group.

Use a structure equivalent to:

```php
Route::prefix('stores')
    ->name('stores.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:stores',
    ])
    ->group(function () {
        // Stores workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where the corresponding functionality already exists:

```text
stores.dashboard

stores.requisitions.index
stores.requisitions.pending
stores.requisitions.approved
stores.requisitions.partially_issued
stores.requisitions.completed
stores.requisitions.rejected
stores.requisitions.show
stores.requisitions.approve
stores.requisitions.reject
stores.requisitions.reserve
stores.requisitions.issue

stores.issues.index
stores.issues.pending
stores.issues.in_progress
stores.issues.completed
stores.issues.show
stores.issues.create
stores.issues.store
stores.issues.complete

stores.receipts.index
stores.receipts.pending
stores.receipts.completed
stores.receipts.show
stores.receipts.create
stores.receipts.store
stores.receipts.complete

stores.transfers.index
stores.transfers.pending
stores.transfers.in_transit
stores.transfers.received
stores.transfers.show
stores.transfers.create
stores.transfers.store
stores.transfers.dispatch
stores.transfers.receive
stores.transfers.cancel

stores.returns.index
stores.returns.create
stores.returns.store
stores.returns.show
stores.returns.accept
stores.returns.reject

stores.adjustments.index
stores.adjustments.create
stores.adjustments.store
stores.adjustments.show
stores.adjustments.approve
stores.adjustments.reject

stores.stock_counts.index
stores.stock_counts.create
stores.stock_counts.store
stores.stock_counts.show
stores.stock_counts.start
stores.stock_counts.submit
stores.stock_counts.approve

stores.reconciliations.index
stores.reconciliations.show
stores.reconciliations.complete

stores.products.index
stores.products.show

stores.stock.index
stores.stock.show
stores.stock.low
stores.stock.out
stores.stock.expiring
stores.stock.expired

stores.batches.index
stores.batches.show

stores.quarantine.index
stores.quarantine.show
stores.quarantine.release

stores.recalls.index
stores.recalls.show

stores.damages.index
stores.damages.create
stores.damages.store
stores.damages.show

stores.disposals.index
stores.disposals.create
stores.disposals.store
stores.disposals.show
stores.disposals.approve
stores.disposals.complete

stores.purchase_requests.index
stores.purchase_requests.show
stores.purchase_requests.create
stores.purchase_requests.store

stores.purchase_orders.index
stores.purchase_orders.show

stores.suppliers.index
stores.suppliers.show

stores.handoffs.index
stores.reports.index
```

Only register routes for functionality that exists or is implemented in this phase.

Do not create empty placeholder pages merely to populate the Stores menu.

---

# Phase 3 — Stores Operations Dashboard

Create or complete a dedicated Stores dashboard.

The canonical route should be:

```text
/stores
```

or:

```text
/stores/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::STORES => 'stores.dashboard'
```

The dashboard should function as an inventory operations command board.

Recommended metrics and widgets include, where reliable data exists:

* Pending requisitions
* Urgent requisitions
* Approved requisitions awaiting issue
* Partially issued requisitions
* Issues in progress
* Transfers awaiting dispatch
* Transfers in transit
* Transfers awaiting receipt
* Goods receipts pending completion
* Purchase orders awaiting receipt
* Departmental returns pending review
* Adjustments awaiting approval
* Open stock counts
* Stock-count variances
* Low-stock products
* Out-of-stock products
* Near-expiry batches
* Expired batches
* Quarantined stock
* Recalled products
* Damaged stock
* Disposal requests awaiting approval
* Negative-stock anomalies
* Reservation anomalies
* Stock without batches where batches are required
* Products without reorder levels
* Total stock value where authorized
* Stock issued today
* Stock received today
* Stock transferred today

Each dashboard metric must:

* Respect permissions
* Respect the active department
* Respect the active Stores stock location
* Avoid exposing unauthorized financial valuation
* Link to valid `/stores/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse existing inventory and department metrics where applicable

Do not introduce metrics that cannot be calculated reliably.

---

# Phase 4 — Stores-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::STORES
```

receives a dedicated Stores menu.

Recommended menu structure:

## Stores Command

* Dashboard
* Pending Requisitions
* Urgent Requisitions
* Pending Issues
* Transfers in Transit
* Open Stock Counts

## Requisitions

* All Requisitions
* Pending Approval
* Approved for Issue
* Partially Issued
* Completed Requisitions
* Rejected Requisitions

## Stock Issues

* Pending Issues
* Issues in Progress
* Completed Issues
* Department Collection
* Issue History

## Goods Receipt

* Pending Receipts
* New Goods Receipt
* Purchase Orders Awaiting Receipt
* Completed Receipts
* Supplier Delivery History

## Transfers

* New Transfer
* Pending Transfers
* Awaiting Dispatch
* In Transit
* Awaiting Receipt
* Completed Transfers
* Cancelled Transfers

## Returns

* Departmental Returns
* Pending Return Review
* Accepted Returns
* Rejected Returns
* Supplier Returns

## Stock Control

* Current Stock
* Product Catalogue
* Batches and Lots
* Low Stock
* Stock Outs
* Near Expiry
* Expired Stock
* Stock Movement History

## Inventory Control

* Stock Counts
* Cycle Counts
* Variance Review
* Reconciliation
* Stock Adjustments
* Adjustment Approvals

## Stock Safety

* Quarantined Stock
* Product Recalls
* Damaged Stock
* Disposal Requests
* Completed Disposals

## Procurement Awareness

* Purchase Requests
* Purchase Orders
* Suppliers
* Awaiting Deliveries

Only show Procurement items when those features exist and the user possesses the required permissions.

## Coordination

* Stores Handoffs
* Department Clarifications
* Escalations
* Reservation Conflicts
* Stockout Alerts

## Stores Reports

* Stock Balance Report
* Stock Movement Report
* Requisition Report
* Issue Report
* Receipt Report
* Transfer Report
* Return Report
* Adjustment Report
* Stock Count Report
* Variance Report
* Expiry Report
* Stockout Report
* Low-Stock Report
* Consumption Report
* Stock Valuation Report
* Supplier Delivery Report
* Staff Activity Report

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The required module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The active stock location permits the operation.

Permissions remain authoritative.

Do not expose menu items solely because the active department type is `stores`.

---

# Phase 5 — Stock Location and Department Scoping

All Stores worklists, balances, movements, receipts, issues, counts, and alerts must respect:

* Active department
* Active Stores stock location
* User stock-location assignment
* Authorized cross-location access
* Product-location configuration
* Department-to-stock-location mapping

Where multiple Stores departments or locations exist, such as:

* Central Medical Store
* General Store
* Pharmacy Bulk Store
* Laboratory Store
* Theatre Store
* Ward Supply Store
* Satellite Store

a user should only see stock and operations relevant to their active department unless explicitly granted cross-location access.

Extend or reuse the existing:

```php
StockLocationResolver
```

Ensure:

```php
DepartmentType::STORES
```

resolves to the appropriate configured Stores stock location.

Do not assume that every Stores department maps to one global stock location.

Support explicit department-to-stock-location mapping where already available.

---

# Phase 6 — Stores Requisition Worklists

Create or adapt requisition worklists under:

```text
/stores/requisitions
```

Recommended requisition states include:

```text
draft
submitted
pending_approval
approved
partially_approved
rejected
reserved
ready_for_issue
partially_issued
fully_issued
completed
cancelled
expired
```

Use existing requisition, approval, reservation, and issue statuses.

Do not introduce duplicate statuses where the worklist state can be derived through a centralized resolver.

Each requisition row should display:

* Requisition number
* Requesting department
* Requesting stock location where applicable
* Requesting user
* Request date and time
* Priority
* Number of items
* Approval state
* Reservation state
* Issue state
* Assigned Stores officer
* Waiting duration
* Next required action

Support filters such as:

* Date
* Priority
* Requesting department
* Requesting user
* Product category
* Requisition status
* Approval state
* Reservation state
* Issue state
* Assigned Stores officer
* Urgent or routine
* Waiting duration

Use pagination and efficient queries.

---

# Phase 7 — Requisition Workspace

Create or adapt a dedicated requisition workspace.

Recommended route:

```text
/stores/requisitions/{requisition}
```

The requisition workspace should coordinate the complete requisition-to-issue lifecycle.

Recommended sections:

1. Requisition details
2. Requesting department
3. Requesting user
4. Request date and priority
5. Requested items
6. Requested quantities
7. Approved quantities
8. Reserved quantities
9. Previously issued quantities
10. Outstanding quantities
11. Available stock
12. Batch availability
13. Alternative products where authorized
14. Approval history
15. Reservation history
16. Issue history
17. Department receipt acknowledgement
18. Clarification history
19. Cancellation history
20. Activity timeline
21. Authorized quick actions

Do not duplicate the underlying requisition, stock, reservation, or issue logic.

The page should coordinate existing inventory services through a Stores-specific interface.

---

# Phase 8 — Requisition Approval

Where requisitions require approval, preserve the existing approval architecture.

Approval should support:

* Full approval
* Partial approval
* Rejection
* Request for clarification
* Approval by threshold
* Multi-level approval where configured
* Emergency approval where supported

An approval record should include:

* Requisition
* Approving user
* Approval level
* Approved items
* Approved quantities
* Rejected items
* Reason
* Date and time

Do not allow approval of quantities greater than the requested quantity unless the existing workflow explicitly allows approved substitutions or package-size adjustments.

Do not allow the same user to submit and approve a requisition where separation of duties is configured.

Approval actions must be audited.

---

# Phase 9 — Stock Reservation

After approval, stock may be reserved for the requisition.

Reservation must:

1. Be tied to the requisition item.
2. Be tied to the source stock location.
3. Respect available quantity.
4. Respect batch and expiry policy.
5. Prevent double allocation.
6. Support partial reservation.
7. Preserve outstanding quantity.
8. Release unused reservations after cancellation or expiry.
9. Be transactionally safe.
10. Be audited where required.

Reservation states may include:

```text
not_reserved
partially_reserved
fully_reserved
reservation_conflict
released
expired
```

Do not create a separate stock ledger for reserved quantities.

Use the existing inventory reservation architecture.

---

# Phase 10 — Stock Issue Workflow

Expose stock issuing under:

```text
/stores/issues
```

Authorized Stores users should be able to:

* Start an issue
* Select an approved requisition
* Confirm source stock location
* Select valid batches
* Confirm issue quantities
* Record partial issue
* Record unavailable quantities
* Record substitutions where authorized
* Prepare issue documentation
* Mark items ready for departmental collection
* Confirm handover
* Complete the issue

Issue states may include:

```text
not_started
in_progress
partially_issued
ready_for_collection
handed_over
completed
cancelled
reversed
```

Stock issuing must not:

* Exceed the approved quantity
* Exceed the outstanding quantity
* Exceed available stock
* Use expired or quarantined stock
* Deduct stock twice
* Issue from an unauthorized stock location
* Silently modify the original requisition

All issue actions must remain under `/stores/*`.

---

# Phase 11 — Partial Stock Issue

Support partial issue where the full approved quantity is not available or cannot be supplied immediately.

A partial issue must preserve:

* Requested quantity
* Approved quantity
* Previously issued quantity
* Quantity issued now
* Outstanding quantity
* Reason
* Batch allocations
* Responsible Stores user
* Date and time
* Follow-up requirement

Potential reasons include:

```text
insufficient_stock
package_size
allocation_limit
department_request
substitution_pending
stock_under_quarantine
other
```

The system should:

1. Preserve the original requisition.
2. Preserve every issue event.
3. Maintain outstanding quantity.
4. Prevent over-issuing.
5. Keep the requisition visible in the partial-issue worklist.
6. Allow later completion where valid.
7. Update stock correctly.
8. Audit every issue event.

Do not mark the requisition fully completed while valid outstanding quantities remain unless those quantities are formally cancelled or waived.

---

# Phase 12 — Product Substitution

Where Stores product substitution is permitted, use a formal workflow.

Potential substitutions include:

* Equivalent brand
* Equivalent generic product
* Equivalent pack size
* Equivalent unit configuration
* Approved alternative consumable

A substitution should record:

* Requested product
* Supplied product
* Equivalence basis
* Requested quantity
* Converted issue quantity
* Reason
* Approving user
* Requesting department acceptance where required
* Stock impact
* Date and time

Do not allow arbitrary substitution through direct editing of the requisition item.

Substitution must respect:

* Product equivalence configuration
* Permission
* Approval policy
* Unit conversion
* Clinical restrictions where relevant
* Audit requirements

---

# Phase 13 — Departmental Collection and Handover

Where the requesting department physically collects supplies, support a formal handover process.

Handover should record:

* Requisition
* Issue
* Requesting department
* Collecting user
* Issuing Stores user
* Collection date and time
* Items and quantities
* Condition at handover
* Acknowledgement
* Notes

Potential handover states include:

```text
not_ready
ready_for_collection
partially_collected
collected
receipt_acknowledged
```

Do not mark issued stock as received by the requesting department merely because the Stores issue was prepared.

Where departmental receipt acknowledgement exists, keep:

* Stores handover
* Department receipt

as distinct states.

---

# Phase 14 — Goods Receipt Workflow

Expose goods receipt under:

```text
/stores/receipts
```

Goods receipt may originate from:

* Purchase order
* Supplier delivery
* Donation
* Opening balance
* Approved direct receipt
* Inter-facility transfer
* Return from another location

The receipt workflow should support:

* Supplier or source
* Purchase order
* Delivery note
* Invoice reference where authorized
* Product
* Ordered quantity
* Delivered quantity
* Accepted quantity
* Rejected quantity
* Unit of measure
* Batch or lot
* Serial number where supported
* Manufacturing date
* Expiry date
* Unit cost where authorized
* Receiving location
* Receiving user
* Inspection state
* Receipt date and time

Do not allow receipt completion without required batch or expiry information for configured products.

Goods receipt must:

1. Validate the source.
2. Validate products and quantities.
3. Create or update batches correctly.
4. Post stock movements transactionally.
5. Preserve rejected quantities.
6. Update purchase-order receipt state where applicable.
7. Preserve valuation data where authorized.
8. Audit the receipt.

---

# Phase 15 — Goods Inspection and Rejection

Before receipt completion, support inspection where applicable.

Inspection may validate:

* Product identity
* Quantity
* Packaging
* Seal integrity
* Batch number
* Expiry date
* Temperature state
* Damage
* Recall state
* Purchase-order match
* Unit-of-measure match

Rejected delivery quantities should record:

* Product
* Quantity
* Reason
* Supplier
* Delivery reference
* Rejecting user
* Date and time
* Return or replacement requirement

Potential rejection reasons include:

```text
wrong_product
wrong_quantity
damaged
expired
near_expiry
incorrect_batch
temperature_breach
packaging_failure
purchase_order_mismatch
quality_failure
other
```

Do not add rejected quantities to available stock.

---

# Phase 16 — Batch, Lot, Serial, and Expiry Tracking

Preserve product-level configuration for whether an item requires:

* Batch tracking
* Lot tracking
* Serial tracking
* Expiry tracking

Batch records may include:

* Product
* Batch or lot number
* Stock location
* Received quantity
* Available quantity
* Reserved quantity
* Expiry date
* Manufacturing date
* Supplier
* Receipt reference
* Unit cost
* Quarantine state
* Recall state
* Disposal state

Serial-tracked products must prevent duplicate serial numbers.

Do not require batch or serial tracking for products that are not configured for it.

Do not allow expired, quarantined, recalled, damaged, or disposed stock to appear as available.

---

# Phase 17 — Stock Transfer Workflow

Expose transfers under:

```text
/stores/transfers
```

Support:

* Store-to-store transfer
* Central-to-satellite transfer
* Store-to-Pharmacy transfer
* Store-to-Laboratory transfer
* Store-to-Theatre transfer
* Store-to-Ward transfer
* Inter-facility transfer where supported

A transfer should include:

* Source location
* Destination location
* Requested items
* Approved quantities
* Dispatched quantities
* Received quantities
* Batch allocations
* Requested user
* Approving user
* Dispatching user
* Receiving user
* Priority
* Transfer date
* Transport information where supported

Transfer states may include:

```text
draft
submitted
approved
reserved
awaiting_dispatch
dispatched
in_transit
partially_received
received
rejected
cancelled
```

Transfers must:

1. Prevent source stock from being issued twice.
2. Preserve stock in transit.
3. Avoid adding destination stock before receipt confirmation where the existing model uses in-transit stock.
4. Support partial receipt.
5. Record damaged or missing quantities.
6. Maintain batch traceability.
7. Be transactionally safe.
8. Be audited.

---

# Phase 18 — Departmental Returns

Expose returns under:

```text
/stores/returns
```

A departmental return may include:

* Requesting or returning department
* Original issue
* Product
* Batch
* Quantity
* Return reason
* Packaging condition
* Storage-condition confidence
* Expiry state
* Tamper state
* Receiving Stores user
* Return date and time
* Stock disposition

Potential outcomes include:

```text
accepted_return_to_stock
accepted_to_quarantine
accepted_for_disposal
rejected
```

Do not assume every returned item can return to available stock.

Stock should only return to available inventory where:

* The product is eligible
* Packaging is acceptable
* Storage conditions are trustworthy
* The batch is valid
* The item is not expired, recalled, or damaged

Use the existing quarantine, adjustment, and disposal services where applicable.

---

# Phase 19 — Supplier Returns

Where supplier-return functionality exists, expose it through Stores.

Supplier returns may relate to:

* Damaged delivery
* Wrong product
* Wrong quantity
* Recall
* Quality failure
* Near expiry
* Expired product
* Contract rejection

A supplier return should record:

* Supplier
* Original receipt
* Product
* Batch
* Quantity
* Reason
* Return date
* Dispatch reference
* Replacement expected
* Credit note reference where authorized
* Responsible user

Do not reduce stock twice when a rejected receipt quantity was never accepted into inventory.

---

# Phase 20 — Stock Adjustments

Expose stock adjustments under:

```text
/stores/adjustments
```

Adjustments should only be used for authorized inventory corrections.

Potential adjustment types include:

```text
increase
decrease
correction
opening_balance
damage
loss
expiry
found_stock
unit_conversion
system_correction
```

An adjustment must record:

* Product
* Stock location
* Batch where applicable
* Previous quantity
* Adjustment quantity
* New quantity
* Adjustment type
* Reason
* Supporting reference
* Requesting user
* Approving user where required
* Date and time

Do not allow direct editing of stock balances.

All balance changes must be represented by stock-ledger movements.

Adjustments above configured thresholds should require approval.

---

# Phase 21 — Physical Stock Counts

Expose physical stock counts under:

```text
/stores/stock-counts
```

Support:

* Full stock count
* Cycle count
* Category count
* Location count
* Batch count
* Spot count

A stock count should include:

* Count reference
* Stock location
* Scope
* Count date
* Count team
* Count status
* Product
* Batch
* System quantity
* Counted quantity
* Variance
* Recount quantity
* Final approved quantity
* Notes

Potential states include:

```text
draft
scheduled
in_progress
submitted
recount_required
awaiting_approval
approved
reconciled
cancelled
```

During a count, follow the existing policy regarding whether stock movement is:

* Frozen
* Restricted
* Allowed with movement tracking
* Counted using a cut-off timestamp

Do not create unexplained adjustment movements automatically.

Variance resolution must remain explicit and audited.

---

# Phase 22 — Stock Reconciliation

Expose reconciliation under:

```text
/stores/reconciliations
```

Reconciliation should compare:

* Opening stock
* Receipts
* Transfers in
* Returns in
* Issues
* Transfers out
* Disposals
* Adjustments
* Expected closing stock
* Physical count
* Variance

A reconciliation should record:

* Count reference
* Products and batches
* Variance reasons
* Approved adjustments
* Responsible user
* Approving user
* Completion date

Potential variance reasons include:

```text
counting_error
unposted_receipt
unposted_issue
incorrect_unit
damage
loss
theft
expired_stock
system_error
unknown
```

Do not reconcile by silently replacing ledger balances.

Use authorized adjustment movements tied to the reconciliation.

---

# Phase 23 — Low Stock and Stockout Management

Expose stock alerts under:

```text
/stores/low-stock
/stores/stock-outs
```

The alert system should use existing product or location configuration such as:

* Reorder level
* Minimum stock
* Maximum stock
* Safety stock
* Average consumption
* Lead time
* Reserved quantity
* Available quantity

The workspace should distinguish:

```text
healthy
below_reorder
low_stock
critical_stock
out_of_stock
overstock
```

Where consumption history exists, display useful planning indicators such as:

* Average daily consumption
* Estimated days of stock remaining
* Last issue date
* Pending purchase quantity
* Pending transfer quantity
* Outstanding requisition quantity

Do not generate procurement actions automatically unless the existing workflow supports it.

---

# Phase 24 — Expiry Management

Expose expiry management under:

```text
/stores/expiries
```

Expiry views should support configurable windows such as:

* Expired
* Expiring within 30 days
* Expiring within 60 days
* Expiring within 90 days
* Expiring within 180 days

Display:

* Product
* Batch
* Location
* Available quantity
* Reserved quantity
* Expiry date
* Days to expiry
* Last movement
* Related requisitions
* Suggested action where supported

Potential actions include:

* Prioritize issue
* Transfer to higher-consumption location
* Quarantine
* Return to supplier
* Dispose
* Mark reviewed

Do not permit issue or transfer of expired stock as available inventory.

Near-expiry restrictions should follow configured policy.

---

# Phase 25 — Quarantine

Expose quarantined stock under:

```text
/stores/quarantine
```

Stock may be quarantined because of:

* Quality concern
* Damage
* Temperature breach
* Recall investigation
* Pending inspection
* Return assessment
* Suspected counterfeit
* Documentation problem
* Near-expiry review

A quarantine record should include:

* Product
* Batch
* Quantity
* Location
* Reason
* Quarantine date
* Responsible user
* Review date
* Review outcome
* Release or disposal decision

Quarantined stock must not be available for:

* Reservation
* Issue
* Transfer
* Dispensing
* Clinical consumption

unless formally released by an authorized user.

---

# Phase 26 — Recall Management

Expose product recalls under:

```text
/stores/recalls
```

A recall may include:

* Product
* Batch or lot
* Recall source
* Recall level
* Reason
* Effective date
* Affected locations
* Quantity on hand
* Quantity issued
* Quantity recovered
* Quantity disposed
* Status

The system should support:

1. Identifying affected stock.
2. Blocking further use.
3. Locating affected batches across authorized stock locations.
4. Recording recovery.
5. Recording department notifications.
6. Recording supplier or regulator communication where supported.
7. Recording final disposition.
8. Auditing all actions.

Do not expose patient-level dispensing details unless the user possesses the required permission and the recall workflow requires them.

---

# Phase 27 — Damage and Loss

Expose damage and loss recording under:

```text
/stores/damages
```

A damage or loss record should include:

* Product
* Batch
* Quantity
* Location
* Type
* Reason
* Date and time
* Discovering user
* Supporting notes
* Investigation state
* Stock disposition
* Approval state

Potential types include:

```text
physical_damage
breakage
spillage
temperature_damage
water_damage
fire_damage
loss
theft
unknown
```

Stock must not be reduced without an authorized stock movement.

High-value or unusual losses should support escalation and approval.

---

# Phase 28 — Disposal and Destruction

Expose disposal under:

```text
/stores/disposals
```

Disposal may apply to:

* Expired stock
* Damaged stock
* Recalled stock
* Contaminated stock
* Failed quality inspection
* Unusable returned stock

A disposal record should include:

* Product
* Batch
* Quantity
* Reason
* Disposal method
* Requested user
* Approving user
* Witnesses where required
* Disposal date
* Disposal reference
* Supporting documentation
* Final stock movement

Potential states include:

```text
requested
awaiting_approval
approved
scheduled
completed
rejected
cancelled
```

Do not reduce available stock twice if the item was already moved to quarantine or damaged stock.

Use clear inventory states and one final disposal movement.

---

# Phase 29 — Purchase Requests and Procurement Awareness

Where purchase-request functionality exists, expose Stores operational access under:

```text
/stores/purchase-requests
```

Stores users may be allowed to:

* Create a purchase request
* Suggest quantity
* Link low-stock or stockout evidence
* Link consumption history
* Submit for approval
* Track status
* View related purchase orders

The purchase-request process should not automatically grant Stores users authority to:

* Approve purchases
* Select suppliers
* Confirm prices
* Create financial commitments

unless explicitly permitted.

Reuse the existing procurement and approval architecture.

---

# Phase 30 — Purchase Orders and Supplier Deliveries

Where purchase orders exist, expose a read or operational receipt view under:

```text
/stores/purchase-orders
```

Stores staff may need to see:

* Supplier
* Purchase-order number
* Expected products
* Ordered quantities
* Previously received quantities
* Outstanding quantities
* Expected delivery date
* Delivery state
* Related receipts

Purchase-order financial details should remain permission-controlled.

Goods receipt must update purchase-order receipt progress without altering approved financial values improperly.

---

# Phase 31 — Product Catalogue Visibility

Expose the product catalogue under:

```text
/stores/products
```

The operational Stores view may include:

* Product name
* Product code
* Category
* Unit
* Pack size
* Batch-tracking requirement
* Expiry-tracking requirement
* Serial-tracking requirement
* Reorder level
* Stock locations
* Active or inactive state
* Current balance
* Reserved quantity
* Available quantity

The ability to view a product must not automatically grant permission to edit:

* Product definitions
* Prices
* Billing mappings
* Clinical mappings
* Insurance prices
* Procurement settings

Use existing administrative permissions.

---

# Phase 32 — Stock Ledger and Movement History

Every inventory balance change must be traceable through the existing stock ledger.

Expose movement history under Stores where permitted.

Movement types may include:

```text
receipt
issue
transfer_out
transfer_in
return_in
return_out
adjustment_in
adjustment_out
disposal
quarantine_in
quarantine_out
reservation
reservation_release
```

The ledger view should display:

* Date and time
* Product
* Batch
* Stock location
* Movement type
* Quantity in
* Quantity out
* Running balance where supported
* Reference type
* Reference number
* Responsible user
* Notes

Do not permit direct editing or deletion of posted stock-ledger movements.

Corrections must use reversals or adjustment workflows.

---

# Phase 33 — Stock Valuation

Where stock valuation exists, preserve the configured valuation method, such as:

* Weighted average
* FIFO
* Specific batch cost
* Standard cost

Do not implement a competing valuation method for the Stores workspace.

Valuation views and reports should be permission-controlled.

The workspace may show:

* Quantity
* Unit cost
* Total value
* Location value
* Category value
* Expired-stock value
* Quarantined-stock value
* Damaged-stock value

Do not expose supplier pricing or inventory value to users without financial inventory permissions.

---

# Phase 34 — Stores Handoffs and Coordination

Expose Stores handoffs under:

```text
/stores/handoffs
```

Reuse the existing Journey Intelligence handoff infrastructure where appropriate.

Support handoffs such as:

* Requisition clarification required
* Item unavailable
* Alternative product proposed
* Department collection ready
* Transfer awaiting receipt
* Goods receipt awaiting inspection
* Purchase-order discrepancy
* Stockout escalation
* Near-expiry action required
* Recall notification
* Adjustment awaiting approval
* Stock-count variance review
* Disposal awaiting authorization

The handoff worklist should show:

* Related requisition, issue, transfer, receipt, or stock item
* Sending department
* Receiving department
* Required action
* Priority
* Due time
* SLA state
* Acknowledgement
* Resolution

Authorized users may:

* Claim
* Assign
* Acknowledge
* Resolve
* Escalate
* Reassign

All Stores-side links must preserve `/stores/*` context.

---

# Phase 35 — Workspace-Aware URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::STORES) {
    return route('stores.requisitions.show', $requisition);
}
```

across controllers and Blade views.

Extend a service such as:

```php
DepartmentWorkspaceRouteResolver
WorkspaceUrlResolver
DepartmentRouteResolver
```

The resolver should support methods equivalent to:

```php
dashboard()

requisitionIndex()
requisitionShow(Requisition $requisition)

issueIndex()
issueShow(StockIssue $issue)

receiptIndex()
receiptShow(GoodsReceipt $receipt)

transferIndex()
transferShow(StockTransfer $transfer)

returnIndex()
returnShow(StockReturn $return)

adjustmentIndex()
adjustmentShow(StockAdjustment $adjustment)

stockCountIndex()
stockCountShow(StockCount $stockCount)

productIndex()
productShow(Product $product)

stockIndex()
stockShow(StockItem $stockItem)

batchIndex()
batchShow(Batch $batch)

quarantineIndex()
recallIndex()
damageIndex()
disposalIndex()

purchaseRequestIndex()
purchaseOrderIndex()
supplierIndex()

handoffIndex()
reportIndex()
```

For a Stores user, the resolver must return `stores.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the active department and stock-location context rather than only the user’s primary department.

---

# Phase 36 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Stores users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Requisition worklists
* Requisition details
* Issue pages
* Goods-receipt pages
* Transfer pages
* Return pages
* Adjustment pages
* Stock-count pages
* Reconciliation pages
* Product lists
* Stock pages
* Batch pages
* Expiry pages
* Quarantine pages
* Recall pages
* Disposal pages
* Purchase-request pages
* Purchase-order pages
* Supplier pages
* Dashboard cards
* Breadcrumbs
* Notifications
* Handoff links
* Action dropdowns
* Empty-state actions
* Report drilldowns
* Flash-message action links

Avoid shared-view code such as:

```php
route('requisitions.show', $requisition)
```

Use the centralized workspace route resolver.

Do not alter API, integration, signed, print, export, procurement callback, or background-job URLs unless explicitly part of the Stores browser workspace.

---

# Phase 37 — Workspace-Aware Redirects

All successful Stores actions must redirect back into `/stores/*`.

Examples:

After approving a requisition:

```text
/stores/requisitions/{requisition}
```

After issuing stock:

```text
/stores/issues/{issue}
```

After completing a goods receipt:

```text
/stores/receipts/{receipt}
```

After dispatching a transfer:

```text
/stores/transfers/{transfer}
```

After receiving a transfer:

```text
/stores/transfers/{transfer}
```

After submitting a stock adjustment:

```text
/stores/adjustments/{adjustment}
```

After completing a stock count:

```text
/stores/stock-counts/{stockCount}
```

After recording a disposal:

```text
/stores/disposals/{disposal}
```

Avoid hardcoding Stores redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toRequisition($requisition);
$workspaceRedirects->toStockIssue($issue);
$workspaceRedirects->toGoodsReceipt($receipt);
$workspaceRedirects->toStockTransfer($transfer);
$workspaceRedirects->toStockCount($stockCount);
$workspaceRedirects->toStoresDashboard();
```

Validation failures must return users to the same `/stores/*` route with input preserved.

---

# Phase 38 — Login and Department Switching

When a user logs in and their active department type is `stores`, redirect them to:

```text
/stores
```

When a multi-department user switches to a Stores department, redirect them to:

```text
/stores
```

When switching away from Stores, redirect to the selected department’s appropriate workspace.

The menu, dashboard, route context, inventory data, and stock-location scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports active department selection.

---

# Phase 39 — Stores Workspace Authorization

The `/stores` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `stores`, unless authorized admin preview applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested stock location, product, requisition, receipt, issue, transfer, return, or count.
7. Active-department or stock-location assignment where required.

A user from another department who manually enters:

```text
/stores/stock
```

must not receive access merely because they possess a broad inventory-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* Workspace unavailable page
* Safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 40 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current model does not represent the required action.

Potential permissions may include:

```text
stores.workspace.view

stores.requisitions.view
stores.requisitions.approve
stores.requisitions.reject
stores.requisitions.reserve
stores.requisitions.issue

stores.issues.view
stores.issues.manage
stores.issues.complete

stores.receipts.view
stores.receipts.create
stores.receipts.complete

stores.transfers.view
stores.transfers.create
stores.transfers.approve
stores.transfers.dispatch
stores.transfers.receive

stores.returns.view
stores.returns.manage

stores.adjustments.view
stores.adjustments.create
stores.adjustments.approve

stores.stock_counts.view
stores.stock_counts.manage
stores.stock_counts.approve

stores.reconciliations.view
stores.reconciliations.manage

stores.products.view
stores.stock.view
stores.batches.view
stores.expiries.view

stores.quarantine.view
stores.quarantine.manage
stores.recalls.view
stores.recalls.manage
stores.damages.view
stores.damages.manage
stores.disposals.view
stores.disposals.manage
stores.disposals.approve

stores.purchase_requests.view
stores.purchase_requests.create
stores.purchase_orders.view
stores.suppliers.view

stores.stock_valuation.view
stores.handoffs.view
stores.handoffs.manage
stores.reports.view
```

Inspect current permission names before adding new permissions.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, approval services, and inventory services must independently enforce authorization.

---

# Phase 41 — Separation of Duties

Where configured, enforce separation of duties for high-risk inventory actions.

Examples:

* Requisition submitter should not approve their own requisition.
* Adjustment creator should not approve the adjustment.
* Stock-count recorder should not be the only count approver.
* Disposal requester should not complete disposal alone.
* Transfer dispatcher and receiver should be distinct where operationally required.
* Goods receipt and purchase-order approval should remain distinct.

Use configurable rules rather than hardcoding one universal workflow.

Separation-of-duty exceptions must be explicit, permission-controlled, reasoned, and audited.

---

# Phase 42 — Legacy Route Compatibility

Keep existing generic stock, product, requisition, transfer, and receipt routes operational for:

* Other departments
* Existing bookmarks
* APIs
* Print flows
* Signed URLs
* Internal notifications
* Background jobs
* Procurement integrations
* Export downloads
* Stock integrations

For interactive browser requests from an active Stores department, generic routes may redirect to Stores equivalents where safe.

Examples:

```text
/requisitions/{requisition}
→ /stores/requisitions/{requisition}

/stock/{stockItem}
→ /stores/stock/{stockItem}

/inventory/transfers/{transfer}
→ /stores/transfers/{transfer}
```

Do not blindly redirect:

* JSON requests
* APIs
* signed URLs
* print routes
* exports
* integration callbacks
* background requests
* procurement integrations

Avoid redirect loops.

---

# Phase 43 — Breadcrumbs and Active Menu State

Stores pages must display Stores-specific breadcrumbs.

Examples:

```text
Stores > Dashboard
Stores > Requisitions
Stores > Requisitions > Requisition Details
Stores > Issues
Stores > Goods Receipts
Stores > Transfers
Stores > Returns
Stores > Current Stock
Stores > Batches
Stores > Stock Counts
Stores > Reconciliation
Stores > Quarantine
Stores > Disposals
Stores > Reports
```

The sidebar must correctly highlight parent items for nested routes.

For example:

```text
stores.requisitions.show
stores.issues.show
stores.transfers.show
stores.stock_counts.show
```

should highlight the appropriate parent menu item.

Use active-route patterns rather than exact route-name equality only.

---

# Phase 44 — Shared View Workspace Context

Pass a clear Stores workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'stores',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'stores.',
    'workspaceTitle' => __('stores.workspace.title'),
    'workspaceScope' => 'inventory_operations',
    'workspaceStockLocation' => $stockLocation,
]
```

Use an existing DTO or view-context object where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Links
* Form actions
* Back buttons
* Requisition navigation
* Issue navigation
* Receipt navigation
* Transfer navigation
* Product and stock navigation
* Quick actions
* Empty states
* Notifications

Do not repeatedly inspect session state or department type inside Blade templates.

---

# Phase 45 — Inventory Integrity and Operational Safety

Preserve existing inventory safeguards, including:

* Negative-stock prevention
* Double-allocation prevention
* Duplicate issue prevention
* Duplicate receipt prevention
* Batch and expiry validation
* Unit-conversion validation
* Serial-number uniqueness
* Transactional stock movement
* Reservation integrity
* Transfer in-transit integrity
* Stock-count variance approval
* Adjustment approval
* Quarantine blocking
* Recall blocking
* Disposal traceability
* Stock-ledger auditability

Do not allow:

* Direct stock-balance editing
* Issue beyond approved quantity
* Issue beyond available quantity
* Expired or quarantined stock to be issued
* The same receipt to post stock twice
* Transfer receipt beyond dispatched quantity
* Returned stock to become available without assessment
* Disposal without an approved inventory movement
* Ledger movements to be silently deleted
* Stock counts to overwrite balances directly

Overrides must be explicit, permission-controlled, reasoned, and audited.

---

# Phase 46 — Activity Logging and Audit

Record relevant Stores actions through the existing `ActivityLog` infrastructure.

Audit events should cover actions such as:

* Requisition submitted
* Requisition approved
* Requisition partially approved
* Requisition rejected
* Stock reserved
* Reservation released
* Stock issue started
* Stock partially issued
* Stock issue completed
* Department handover confirmed
* Goods receipt created
* Goods receipt completed
* Delivery quantity rejected
* Batch created
* Stock transfer requested
* Stock transfer approved
* Stock transfer dispatched
* Stock transfer received
* Departmental return accepted
* Departmental return rejected
* Stock adjustment requested
* Stock adjustment approved
* Stock count started
* Stock count submitted
* Stock-count variance approved
* Reconciliation completed
* Stock quarantined
* Quarantine released
* Recall initiated
* Damaged stock recorded
* Disposal requested
* Disposal approved
* Disposal completed
* Purchase request created
* Handoff acknowledged
* Handoff resolved

Do not log sensitive supplier financial details where audit policy prohibits them.

Audit records should include sufficient context such as:

* Actor
* Department
* Stock location
* Product
* Batch
* Quantity
* Requisition identifier
* Issue identifier
* Receipt identifier
* Transfer identifier
* Adjustment identifier
* Count identifier
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 47 — Localization

Add complete English and French localization for the Stores workspace.

Prefer an existing Stores localization file if one exists, otherwise use:

```text
lang/en/stores.php
lang/fr/stores.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Requisition states
* Approval states
* Reservation states
* Issue states
* Receipt states
* Transfer states
* Return states
* Adjustment types and states
* Stock-count states
* Variance reasons
* Stock alert states
* Expiry states
* Quarantine reasons
* Recall states
* Damage types
* Disposal states
* Goods-rejection reasons
* Handoffs
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Override reasons

Maintain complete English and French parity.

Do not hardcode visible Stores labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 48 — Stores Reports and Statistics

Create or adapt Stores reports under:

```text
/stores/reports
```

Recommended reports include:

* Stock balance report
* Stock movement report
* Stock ledger report
* Requisition volume report
* Requisition fulfilment report
* Partial issue report
* Department consumption report
* Product consumption report
* Goods receipt report
* Supplier delivery report
* Transfer report
* Return report
* Adjustment report
* Stock-count report
* Variance report
* Reconciliation report
* Low-stock report
* Stockout report
* Expiry report
* Quarantine report
* Recall report
* Damage and loss report
* Disposal report
* Stock valuation report
* Inventory ageing report
* Staff activity report
* Stock-location performance report

Reports must respect:

* Permissions
* Active department
* Stock location
* Product visibility
* Financial valuation permissions
* Export permissions

Consumption statistics should distinguish between:

* Issued quantity
* Returned quantity
* Net issued quantity
* Reserved quantity
* Transferred quantity
* Disposed quantity
* Adjusted quantity

Do not treat stock issued to a department as confirmed patient consumption unless the clinical consumption workflow records it separately.

---

# Phase 49 — Menu Configuration and Future Extensibility

Implement the Stores menu through the existing menu registry or department menu profile service.

Do not define it directly inside the sidebar Blade template.

The menu configuration should support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active-route patterns
* Badge counts
* Department-type availability
* Active-department scoping
* Stock-location scoping
* Feature flags
* Pending requisition counts
* Pending issue counts
* Transfer counts
* Stock-count variance counts
* Low-stock counts
* Stockout counts
* Expiry-alert counts
* Quarantine counts
* Disposal-approval counts

The architecture must remain extensible for future department menu personalization, including:

```text
radiology
finance
maternity
theatre
blood_bank
mortuary
ambulance
support
administrative
```

Do not implement those other workspaces in this phase.

---

# Phase 50 — Focused Automated Verification

Add focused automated tests for the Stores workspace.

## Route tests

Verify:

* Stores routes exist.
* Route names use `stores.*`.
* URLs use `/stores/*`.
* Stores department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* A Stores department user can access authorized Stores pages.
* A non-Stores department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.
* Stock-location scoping is respected.

## Dashboard tests

Verify:

* The Stores dashboard loads.
* Metrics use the active Stores stock location.
* Pending requisition and transfer counts are accurate.
* Low-stock, stockout, expiry, and quarantine counts are accurate.
* Stock valuation is hidden without permission.
* Links point to `/stores/*`.
* Empty states render safely.

## Requisition tests

Verify:

* Requisitions appear in the correct worklists.
* Approval state is reflected correctly.
* Partial approval preserves unapproved quantities.
* Rejection requires a reason.
* Reservation cannot exceed approved or available quantity.
* Requisition completion reflects issued quantities correctly.

## Issue tests

Verify:

* Issue cannot exceed approved quantity.
* Issue cannot exceed outstanding quantity.
* Issue cannot exceed available stock.
* Partial issue preserves outstanding quantity.
* Batch traceability is preserved.
* Expired or quarantined batches cannot be issued.
* Duplicate stock deductions are prevented.

## Receipt tests

Verify:

* Goods receipt creates correct stock movements.
* Purchase-order receipt progress updates correctly.
* Rejected quantities do not enter available stock.
* Batch and expiry requirements are enforced.
* Duplicate receipt posting is prevented.
* Receipt actions are audited.

## Transfer tests

Verify:

* Transfer reserves or deducts source stock correctly.
* Dispatched stock enters the correct in-transit state.
* Destination stock is updated at the correct workflow point.
* Partial receipt preserves outstanding quantity.
* Received quantity cannot exceed dispatched quantity.
* Missing or damaged quantities are recorded.
* Transfers are audited.

## Return tests

Verify:

* Department returns preserve the original issue.
* Valid returns can restore available stock.
* Invalid returns go to quarantine or disposal.
* Returned quantities cannot exceed issued quantities.
* Return actions are audited.

## Adjustment tests

Verify:

* Direct balance editing is not available.
* Adjustments create stock-ledger movements.
* Approval is required where configured.
* Creator and approver separation works where configured.
* Adjustments are audited.

## Stock-count tests

Verify:

* Physical counts preserve system quantities.
* Variances are calculated correctly.
* Recounts work where required.
* Approval creates linked adjustment movements.
* Count reconciliation does not silently replace ledger balances.
* Count actions are audited.

## Expiry and quarantine tests

Verify:

* Expired stock cannot be issued.
* Quarantined stock cannot be reserved, issued, transferred, or dispensed.
* Authorized release restores the appropriate state.
* Recall blocks affected stock.
* Disposal removes stock through one traceable movement.

## Redirect tests

Verify:

* Login redirects to `/stores`.
* Switching to Stores redirects to `/stores`.
* Requisition actions remain under `/stores/*`.
* Issue, receipt, transfer, return, adjustment, count, and disposal actions remain under `/stores/*`.
* No redirect loops occur.
* JSON, API, signed, print, export, and integration requests are not incorrectly redirected.

## Privacy, authorization, and audit tests

Verify:

* Supplier financial data is permission-controlled.
* Stock valuation is permission-controlled.
* Cross-location stock is not exposed without authorization.
* Stores actions generate required audit records.
* Ledger history cannot be silently altered.

Run focused Stores workspace tests and essential route, view, localization, stock-ledger, transaction, permission, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Stores workspace phases are complete.

---

# Phase 51 — Manual Acceptance Scenarios

## Scenario A — Stores login

1. Log in as a user whose active department type is `stores`.
2. Confirm the landing URL is `/stores`.
3. Confirm the Stores-specific menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — Requisition approval

1. Open pending requisitions.
2. Select a department requisition.
3. Approve full or partial quantities.
4. Confirm the route remains under `/stores/*`.
5. Confirm the approval is audited.

## Scenario C — Stock reservation and issue

1. Open an approved requisition.
2. Reserve available stock.
3. Start an issue.
4. Select valid batches.
5. Issue the approved quantity.
6. Confirm stock, reservation, and requisition states update correctly.

## Scenario D — Partial issue

1. Open a requisition with insufficient stock.
2. Issue the available quantity.
3. Confirm the requisition becomes partially issued.
4. Confirm outstanding quantity is preserved.
5. Complete the remaining issue later.
6. Confirm over-issuing is prevented.

## Scenario E — Departmental collection

1. Mark an issue ready for collection.
2. Record the collecting department user.
3. Complete the handover.
4. Confirm departmental receipt remains distinct where acknowledgement is required.
5. Confirm the action is audited.

## Scenario F — Goods receipt

1. Open a purchase order awaiting delivery.
2. Record delivered products.
3. Capture batches and expiry dates.
4. Reject one damaged quantity.
5. Complete the receipt.
6. Confirm only accepted quantities enter available stock.

## Scenario G — Stock transfer

1. Create a transfer to another stock location.
2. Approve and dispatch it.
3. Confirm the stock enters the correct in-transit state.
4. Receive it at the destination.
5. Confirm both locations update correctly.
6. Confirm batch traceability remains intact.

## Scenario H — Departmental return

1. Open a completed stock issue.
2. Record a return.
3. Assess condition and storage suitability.
4. Return eligible stock to available inventory.
5. Move unsuitable stock to quarantine or disposal.
6. Confirm stock movements are correct.

## Scenario I — Stock adjustment

1. Create a stock adjustment.
2. Record the reason and supporting reference.
3. Submit it for approval.
4. Approve it with a separate authorized user.
5. Confirm the ledger records the adjustment.
6. Confirm direct balance editing is unavailable.

## Scenario J — Physical stock count

1. Create a stock count.
2. Record physical quantities.
3. Submit the count.
4. Review variances.
5. Approve reconciliation.
6. Confirm linked adjustment movements are created.
7. Confirm historical stock movements remain preserved.

## Scenario K — Expired stock

1. Open an expired batch.
2. Attempt to reserve or issue it.
3. Confirm the system blocks the action.
4. Move the batch through quarantine or disposal.
5. Confirm the final stock movement is audited.

## Scenario L — Recall

1. Create or open a product recall.
2. Confirm affected batches are blocked.
3. Identify quantities across authorized stock locations.
4. Record recovered quantities.
5. Complete the final disposition.
6. Confirm recall actions are audited.

## Scenario M — Active stock-location scoping

1. Use a user assigned to multiple Stores departments.
2. Switch the active department.
3. Confirm stock, requisitions, receipts, and transfers change to the selected location.
4. Confirm unauthorized locations are not visible.

## Scenario N — Permission control

1. Remove stock-adjustment approval permission.
2. Confirm the approval action disappears.
3. Enter the approval route directly.
4. Confirm access is denied.

## Scenario O — Legacy compatibility

1. Enter a generic requisition, stock, or transfer route as a Stores user.
2. Confirm it safely resolves or redirects to the Stores equivalent where configured.
3. Confirm APIs, signed URLs, print routes, exports, and integrations remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `stores` receive a dedicated Stores menu.
2. Their default dashboard uses `/stores`.
3. Supported Stores pages use `/stores/*` URLs.
4. Route names use the `stores.*` namespace.
5. Requisitions, stock, receipts, issues, and transfers are scoped to the active Stores department and stock location.
6. Forms submit through Stores routes.
7. Redirects remain inside the Stores workspace.
8. Breadcrumbs and active menu states are Stores-aware.
9. Permissions and enabled modules control menu visibility.
10. A non-Stores department user cannot access the workspace.
11. Multi-department users are evaluated using the active department.
12. Existing product, inventory, requisition, stock-ledger, receipt, issue, transfer, count, procurement, reporting, and audit logic is reused.
13. Core inventory and stock-ledger logic is not duplicated.
14. Requisition approval and reservation are fully tracked.
15. Stock reservation prevents double allocation.
16. Stock issue cannot exceed approved, outstanding, or available quantity.
17. Partial issues preserve outstanding quantities.
18. Goods receipts preserve purchase-order and batch traceability.
19. Rejected delivery quantities do not enter available stock.
20. Transfers preserve source, in-transit, and destination integrity.
21. Returned stock is assessed before returning to available inventory.
22. Direct stock-balance editing is not permitted.
23. Stock adjustments use traceable ledger movements.
24. Stock counts preserve system quantities and create approved reconciliation movements.
25. Expired, quarantined, recalled, damaged, or disposed stock cannot be issued.
26. Disposal and destruction remain approval-controlled and traceable.
27. Separation of duties is enforced where configured.
28. Stock valuation and supplier financial data remain permission-controlled.
29. Generic routes remain functional for other departments and integrations.
30. APIs, signed URLs, print routes, exports, and integrations are not incorrectly redirected.
31. Relevant Stores actions are audited.
32. English and French localization are complete and in parity.
33. Focused Stores workspace tests pass.
34. One broad relevant suite passes after all phases are complete.
35. No broken links, route loops, duplicate route names, stock-location leakage, duplicate stock deductions, unexplained balance replacements, or Pharmacy-dispensing contamination remain.

---

# Deliverables

Provide:

1. Stores workspace route group.
2. Stores-specific controllers or thin adapters where required.
3. Stores operations dashboard.
4. Stores department menu profile.
5. Active-department and stock-location scoping.
6. Requisition worklists.
7. Requisition approval integration.
8. Stock reservation workflow.
9. Full and partial stock issuing.
10. Product substitution workflow.
11. Department collection and handover.
12. Goods-receipt workflow.
13. Delivery inspection and rejection.
14. Batch, lot, serial, and expiry tracking.
15. Stock-transfer workflow.
16. Departmental and supplier returns.
17. Stock-adjustment workflow.
18. Physical stock-count workflow.
19. Stock reconciliation.
20. Low-stock and stockout monitoring.
21. Expiry management.
22. Quarantine and recall management.
23. Damage, loss, disposal, and destruction workflows.
24. Purchase-request and purchase-order awareness.
25. Product catalogue and stock-ledger views.
26. Stock valuation permission integration.
27. Stores handoff integration.
28. Workspace-aware URL resolver updates.
29. Workspace-aware redirect resolver updates.
30. Updated shared links and forms.
31. Login and department-switch integration.
32. Stores breadcrumbs and active-menu handling.
33. Permission and separation-of-duty integration.
34. English and French localization.
35. Focused feature tests.
36. A final implementation report containing:

* Files created
* Files modified
* Stores route map
* Stores menu map
* Stock-location mapping
* Dashboard metrics
* Requisition workflow
* Reservation behaviour
* Full and partial issue behaviour
* Goods-receipt behaviour
* Batch and expiry behaviour
* Transfer behaviour
* Return behaviour
* Adjustment behaviour
* Stock-count and reconciliation behaviour
* Quarantine, recall, damage, and disposal behaviour
* Procurement integration
* Reused services
* Redirect behaviour
* Permissions used
* Separation-of-duty rules
* Inventory-integrity checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, stock listing, or requisition listing alone. The final implementation must provide a functional, traceable, stock-location-aware, department-specific Stores workspace throughout the complete hospital inventory lifecycle.
