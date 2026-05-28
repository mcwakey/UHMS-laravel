<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ProductType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\ConsumableUsage;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ProductPrice;
use App\Models\Department;
use App\Models\DrugStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\ServiceCatalog;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use App\Models\Visit;
use App\Services\ConsumableUsageService;
use App\Services\ProcurementService;
use App\Services\ProductStockMovementService;
use App\Services\ProductStockService;
use App\Services\ProductService;
use App\Services\PurchaseReturnService;
use App\Services\StockRequisitionService;
use App\Services\SupplierLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UnifiedInventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $department = Department::factory()->create([
            'type' => DepartmentType::ADMINISTRATIVE->value,
        ]);

        $this->user = User::factory()->create([
            'department_id' => $department->id,
        ]);

        $role = Role::findOrCreate('Unified Inventory Tester', 'web');

        foreach ([
            'pharmacy.drugs.manage',
            'lab.tests.manage',
            'procedure.view',
            'stock.view',
            'stock.location.manage',
            'store.purchase.view',
            'store.purchase.create',
            'store.purchase.approve',
            'store.purchase.receive',
            'store.return.view',
            'store.return.create',
            'store.return.approve',
            'store.transfer.view',
            'store.transfer.create',
            'store.requisition.view',
            'store.requisition.create',
            'store.requisition.approve',
            'store.requisition.issue',
            'store.requisition.acknowledge',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);
    }

    public function test_purchase_order_receiving_writes_product_ledger_only_and_receives_into_main_store(): void
    {
        $this->actingAs($this->user);

        $mainStore = $this->mainStoreLocation();
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Pharmacy', 'PHM');
        $product = $this->product('Paracetamol 500mg', 'PARA-500', ProductType::DRUG, $pharmacy);
        [$purchaseOrder, $item] = $this->purchaseOrderForProduct($product, 100, 12.50);

        app(ProcurementService::class)->receiveItems($purchaseOrder, [[
            'item_id' => $item->id,
            'quantity_received' => 100,
            'batch_number' => 'BATCH-A',
            'expiry_date' => now()->addYear()->toDateString(),
        ]]);

        $movement = StockMovement::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertNull($movement->drug_id);
        $this->assertSame($mainStore->id, $movement->stock_location_id);
        $this->assertSame(StockMovementType::PURCHASE_RECEIVED, $movement->movement_type);
        $this->assertSame(100.0, (float) $movement->quantity);
        $this->assertSame(100.0, $this->quantityFor($product, $mainStore));
        $this->assertSame(100, $item->refresh()->quantity_received);
        $this->assertSame(PurchaseOrderStatus::RECEIVED, $purchaseOrder->refresh()->status);
        $this->assertSame(0, DrugStock::query()->count());
        $this->assertSame(1250.0, (float) SupplierLedgerEntry::query()->sum('credit'));
    }

    public function test_transfer_moves_stock_from_main_store_to_department_location(): void
    {
        $this->actingAs($this->user);

        $mainStore = $this->mainStoreLocation();
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Pharmacy', 'PHM');
        $pharmacyLocation = $this->departmentLocation($pharmacy, 'Pharmacy Store', 'pharmacy');
        $product = $this->product('Ceftriaxone 1g', 'CEF-1G', ProductType::DRUG, $pharmacy);

        app(ProductStockService::class)->receive([
            'product_id' => $product->id,
            'stock_location_id' => $mainStore->id,
            'quantity' => 50,
            'unit_cost' => 900,
        ]);

        app(ProductStockService::class)->transfer([
            'product_id' => $product->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $pharmacyLocation->id,
            'quantity' => 20,
        ]);

        $this->assertSame(30.0, $this->quantityFor($product, $mainStore));
        $this->assertSame(20.0, $this->quantityFor($product, $pharmacyLocation));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $mainStore->id,
            'movement_type' => StockMovementType::TRANSFER_OUT->value,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $pharmacyLocation->id,
            'movement_type' => StockMovementType::TRANSFER_IN->value,
        ]);
    }

    public function test_department_catalogues_report_only_department_location_stock(): void
    {
        $this->actingAs($this->user);

        $mainStore = $this->mainStoreLocation();
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Pharmacy', 'PHM');
        $lab = $this->department(DepartmentType::INVESTIGATION, 'Laboratory', 'LAB');
        $theatre = $this->department(DepartmentType::PROCEDURE, 'Theatre', 'THR');
        $ward = $this->department(DepartmentType::TREATMENT, 'Children Ward', 'CHW');
        $emergency = $this->department(DepartmentType::SUPPORT, 'Emergency Department', 'ER');

        $pharmacyLocation = $this->departmentLocation($pharmacy, 'Pharmacy Store', 'pharmacy');
        $labLocation = $this->departmentLocation($lab, 'Laboratory Store', 'lab');
        $theatreLocation = $this->departmentLocation($theatre, 'Theatre Store', 'theatre');
        $wardLocation = $this->departmentLocation($ward, 'Children Ward Store', 'ward');
        $emergencyLocation = $this->departmentLocation($emergency, 'Emergency Store', 'emergency');

        $drug = $this->product('Amoxicillin 250mg', 'AMOX-250', ProductType::DRUG, $pharmacy);
        $reagent = $this->product('Glucose Reagent', 'GLU-REAG', ProductType::REAGENT, $lab);
        $suture = $this->product('Absorbable Suture', 'SUT-01', ProductType::SURGICAL_SUPPLY, $theatre);
        $wardGlove = $this->product('Ward Nitrile Gloves', 'WRD-GLV', ProductType::MEDICAL_SUPPLY, $ward);
        $emergencyDressing = $this->product('Emergency Dressing Pack', 'ER-DRS', ProductType::MEDICAL_SUPPLY, $emergency);

        $stock = app(ProductStockService::class);
        foreach ([$drug, $reagent, $suture, $wardGlove, $emergencyDressing] as $product) {
            $stock->receive([
                'product_id' => $product->id,
                'stock_location_id' => $mainStore->id,
                'quantity' => 40,
            ]);
        }

        $this->assertSame(0.0, $this->pharmacyAvailable($drug));
        $this->assertSame(0.0, $this->labAvailable($reagent));
        $this->assertSame(0.0, $this->theatreAvailable($suture));
        $this->assertSame(0.0, $this->wardAvailable($wardGlove));
        $this->assertSame(0.0, $this->emergencyAvailable($emergencyDressing));

        $stock->transfer([
            'product_id' => $drug->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $pharmacyLocation->id,
            'quantity' => 12,
        ]);
        $stock->transfer([
            'product_id' => $reagent->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $labLocation->id,
            'quantity' => 8,
        ]);
        $stock->transfer([
            'product_id' => $suture->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $theatreLocation->id,
            'quantity' => 5,
        ]);
        $stock->transfer([
            'product_id' => $wardGlove->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $wardLocation->id,
            'quantity' => 9,
        ]);
        $stock->transfer([
            'product_id' => $emergencyDressing->id,
            'from_location_id' => $mainStore->id,
            'to_location_id' => $emergencyLocation->id,
            'quantity' => 7,
        ]);

        $this->assertSame(12.0, $this->pharmacyAvailable($drug));
        $this->assertSame(8.0, $this->labAvailable($reagent));
        $this->assertSame(5.0, $this->theatreAvailable($suture));
        $this->assertSame(9.0, $this->wardAvailable($wardGlove));
        $this->assertSame(7.0, $this->emergencyAvailable($emergencyDressing));
    }

    public function test_department_catalogue_mutation_endpoints_do_not_create_parallel_item_rows(): void
    {
        $this->actingAs($this->user);

        $this->post(route('admin.pharmacy.drugs.store'), [
            'name' => 'Parallel Drug',
        ])->assertStatus(410);

        $this->post(route('admin.investigations.items.store'), [
            'name' => 'Parallel Investigation Item',
        ])->assertForbidden();
    }

    public function test_purchase_return_posting_creates_product_return_out_and_supplier_debit(): void
    {
        $this->actingAs($this->user);

        $mainStore = $this->mainStoreLocation();
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Return Pharmacy', 'RPH');
        $product = $this->product('Returnable Syringe', 'RET-SYR', ProductType::MEDICAL_SUPPLY, $pharmacy);
        $supplier = Supplier::create(['name' => 'Return Supplier', 'is_active' => true]);

        app(ProductStockService::class)->receive([
            'product_id' => $product->id,
            'stock_location_id' => $mainStore->id,
            'quantity' => 20,
            'unit_cost' => 7.5,
        ]);

        $purchaseReturn = app(PurchaseReturnService::class)->create([
            'supplier_id' => $supplier->id,
            'stock_location_id' => $mainStore->id,
            'return_date' => now()->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_cost' => 7.5,
            ]],
        ]);

        app(PurchaseReturnService::class)->approve($purchaseReturn);
        app(PurchaseReturnService::class)->post($purchaseReturn->refresh());

        $this->assertSame(15.0, $this->quantityFor($product, $mainStore));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'drug_id' => null,
            'stock_location_id' => $mainStore->id,
            'movement_type' => StockMovementType::RETURN_OUT->value,
        ]);
        $this->assertDatabaseHas('supplier_ledger_entries', [
            'supplier_id' => $supplier->id,
            'entry_type' => SupplierLedgerEntry::TYPE_RETURN_TO_SUPPLIER,
            'source_type' => PurchaseReturn::class,
            'debit' => 37.5,
            'credit' => 0,
        ]);
        $this->assertSame(0, DrugStock::query()->count());
    }

    public function test_stock_requisition_updates_department_stock_only_after_acknowledgement(): void
    {
        $this->actingAs($this->user);

        $mainStore = $this->mainStoreLocation();
        $department = $this->department(DepartmentType::TREATMENT, 'Children Ward', 'CHW');
        $departmentLocation = $this->departmentLocation($department, 'Children Ward Store', 'ward');
        $product = $this->product('Ward Gloves', 'WARD-GLV', ProductType::MEDICAL_SUPPLY, $department);

        app(ProductStockService::class)->receive([
            'product_id' => $product->id,
            'stock_location_id' => $mainStore->id,
            'quantity' => 30,
            'unit_cost' => 2,
        ]);

        $requisition = app(StockRequisitionService::class)->create([
            'department_id' => $department->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity_requested' => 10,
            ]],
        ]);

        $item = $requisition->items()->firstOrFail();
        app(StockRequisitionService::class)->approve($requisition, [$item->id => 10]);
        app(StockRequisitionService::class)->issue($requisition->refresh());

        $this->assertSame(20.0, $this->quantityFor($product, $mainStore));
        $this->assertSame(0.0, $this->quantityFor($product, $departmentLocation));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $mainStore->id,
            'movement_type' => StockMovementType::TRANSFER_OUT->value,
        ]);

        app(StockRequisitionService::class)->acknowledge($requisition->refresh());

        $this->assertSame(10.0, $this->quantityFor($product, $departmentLocation));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $departmentLocation->id,
            'movement_type' => StockMovementType::TRANSFER_IN->value,
        ]);
    }

    public function test_consumable_usage_deducts_department_stock_and_bills_only_billable_products(): void
    {
        $this->actingAs($this->user);

        $department = $this->department(DepartmentType::INVESTIGATION, 'Usage Lab', 'ULB');
        $departmentLocation = $this->departmentLocation($department, 'Usage Lab Store', 'lab');
        $billableProduct = $this->product('Billable Reagent', 'BILL-REAG', ProductType::REAGENT, $department);
        $nonBillableProduct = $this->product('Control Swab', 'CTRL-SWAB', ProductType::CONSUMABLE, $department);
        $nonBillableProduct->update(['is_billable' => false]);

        app(ProductStockMovementService::class)->createMovement([
            'product_id' => $billableProduct->id,
            'stock_location_id' => $departmentLocation->id,
            'movement_type' => StockMovementType::OPENING_STOCK,
            'quantity' => 6,
        ]);
        app(ProductStockMovementService::class)->createMovement([
            'product_id' => $nonBillableProduct->id,
            'stock_location_id' => $departmentLocation->id,
            'movement_type' => StockMovementType::OPENING_STOCK,
            'quantity' => 4,
        ]);

        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'current_department_id' => $department->id,
        ]);
        $service = ServiceCatalog::create([
            'name' => 'Usage Test Service',
            'code' => 'UTS-001',
            'category' => 'lab',
            'price' => 25,
            'is_active' => true,
            'is_billable' => true,
            'department_id' => $department->id,
            'department_type' => DepartmentType::INVESTIGATION->value,
        ]);

        $result = app(ConsumableUsageService::class)->recordUsageForSource(
            $visit->fresh('department'),
            $service,
            'investigation_result',
            123,
            [
                ['product_id' => $billableProduct->id, 'quantity' => 2],
                ['product_id' => $nonBillableProduct->id, 'quantity' => 1],
            ],
            $this->user->id,
        );

        $this->assertCount(2, $result['usages']);
        $this->assertSame(4.0, $this->quantityFor($billableProduct, $departmentLocation));
        $this->assertSame(3.0, $this->quantityFor($nonBillableProduct, $departmentLocation));
        $this->assertSame(1, InvoiceItem::where('source_type', InvoiceItem::SOURCE_INVESTIGATION_CONSUMABLE)->count());
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $billableProduct->id,
            'source_type' => InvoiceItem::SOURCE_INVESTIGATION_CONSUMABLE,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('consumable_usages', [
            'product_id' => $billableProduct->id,
            'source_type' => 'investigation_result',
            'source_id' => 123,
            'is_billable' => true,
        ]);
        $this->assertNull(ConsumableUsage::where('product_id', $nonBillableProduct->id)->value('invoice_item_id'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $billableProduct->id,
            'stock_location_id' => $departmentLocation->id,
            'movement_type' => StockMovementType::INVESTIGATION_CONSUMED->value,
            'quantity' => 2,
        ]);
    }

    public function test_supplier_ledger_manual_entries_are_restricted_to_safe_types(): void
    {
        $supplier = Supplier::create(['name' => 'Manual Ledger Supplier', 'is_active' => true]);
        $ledger = app(SupplierLedgerService::class);

        $this->expectException(\InvalidArgumentException::class);
        $ledger->recordEntry(
            supplier: $supplier,
            entryType: SupplierLedgerEntry::TYPE_GOODS_RECEIVED,
            debit: 0,
            credit: 50,
            description: 'Manual GRN attempt',
        );
    }

    public function test_supplier_ledger_manual_payment_records_debit_only(): void
    {
        $supplier = Supplier::create(['name' => 'Payment Supplier', 'is_active' => true]);

        app(SupplierLedgerService::class)->recordManualEntry($supplier, [
            'entry_type' => SupplierLedgerEntry::TYPE_PAYMENT,
            'amount' => 25,
            'description' => 'Cash payment',
        ]);

        $this->assertDatabaseHas('supplier_ledger_entries', [
            'supplier_id' => $supplier->id,
            'entry_type' => SupplierLedgerEntry::TYPE_PAYMENT,
            'debit' => 25,
            'credit' => 0,
        ]);
    }

    public function test_procurement_stats_and_product_filter_use_purchase_order_items(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'Stats Pharmacy', 'SPH');
        $firstProduct = $this->product('Stats Product A', 'STAT-A', ProductType::DRUG, $department);
        $secondProduct = $this->product('Stats Product B', 'STAT-B', ProductType::DRUG, $department);
        [$firstPo, $firstItem] = $this->purchaseOrderForProduct($firstProduct, 10, 5);
        [$secondPo] = $this->purchaseOrderForProduct($secondProduct, 4, 3);

        $firstItem->update(['quantity_received' => 3]);
        $secondPo->update(['status' => PurchaseOrderStatus::CANCELLED]);

        $filtered = app(ProcurementService::class)->list(['product_id' => $firstProduct->id]);
        $stats = app(ProcurementService::class)->getStats();

        $this->assertSame(1, $filtered->total());
        $this->assertTrue($filtered->getCollection()->contains('id', $firstPo->id));
        $this->assertSame(50.0, $stats['total_ordered_value']);
        $this->assertSame(15.0, $stats['total_received_value']);
        $this->assertSame(35.0, $stats['outstanding_value']);
    }

    public function test_product_filters_cover_status_billable_and_insurance_prices(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'Filter Pharmacy', 'FPH');
        $pricedProduct = $this->product('Priced Filter Product', 'PFP', ProductType::DRUG, $department);
        $inactiveProduct = $this->product('Inactive Filter Product', 'IFP', ProductType::DRUG, $department);
        $inactiveProduct->update(['is_active' => false, 'is_billable' => false]);

        ProductPrice::create([
            'product_id' => $pricedProduct->id,
            'insurance_type' => 'nhia',
            'price' => 25,
            'is_active' => true,
        ]);

        $activePriced = app(ProductService::class)->listProducts([
            'status' => 'active',
            'is_billable' => '1',
            'has_insurance_prices' => '1',
        ]);
        $inactiveUnpriced = app(ProductService::class)->listProducts([
            'status' => 'inactive',
            'is_billable' => '0',
            'has_insurance_prices' => '0',
        ]);

        $this->assertTrue($activePriced->getCollection()->contains('id', $pricedProduct->id));
        $this->assertFalse($activePriced->getCollection()->contains('id', $inactiveProduct->id));
        $this->assertTrue($inactiveUnpriced->getCollection()->contains('id', $inactiveProduct->id));
        $this->assertFalse($inactiveUnpriced->getCollection()->contains('id', $pricedProduct->id));
    }

    public function test_main_store_cannot_be_edited_or_deactivated_from_admin_routes(): void
    {
        $this->actingAs($this->user);
        $mainStore = $this->mainStoreLocation();

        $this->withoutMiddleware()->patch(route('admin.stock-locations.toggle', $mainStore))->assertRedirect();
        $this->assertTrue((bool) $mainStore->refresh()->is_active);

        $this->withoutMiddleware()->put(route('admin.stock-locations.update', $mainStore), [
            'name' => 'Renamed Store',
            'type' => 'other',
            'is_active' => 0,
            'is_main' => 0,
        ])->assertRedirect();

        $this->assertSame('Main Store', $mainStore->refresh()->name);
        $this->assertTrue((bool) $mainStore->is_active);
        $this->assertTrue((bool) $mainStore->is_main);
    }

    private function mainStoreLocation(): StockLocation
    {
        StockLocation::query()->where('is_main', true)->update(['is_main' => false]);

        return StockLocation::updateOrCreate(
            ['name' => 'Main Store'],
            [
                'type' => 'store',
                'department_id' => null,
                'is_active' => true,
                'is_main' => true,
            ],
        );
    }

    private function department(DepartmentType $type, string $name, string $code): Department
    {
        return Department::create([
            'name' => $name,
            'code' => $code,
            'type' => $type->value,
            'status' => 'active',
            'is_stock_managed' => true,
        ]);
    }

    private function departmentLocation(Department $department, string $name, string $type): StockLocation
    {
        return StockLocation::updateOrCreate(
            ['name' => $name],
            [
                'type' => $type,
                'department_id' => $department->id,
                'is_active' => true,
                'is_main' => false,
            ],
        );
    }

    private function product(string $name, string $code, ProductType $type, Department $department): Product
    {
        $product = Product::create([
            'name' => $name,
            'code' => $code,
            'product_type' => $type->value,
            'unit' => 'unit',
            'reorder_level' => 5,
            'default_cost' => 10,
            'base_price' => 15,
            'is_billable' => true,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $product->departments()->sync([$department->id => ['is_active' => true]]);

        return $product;
    }

    private function purchaseOrderForProduct(Product $product, int $quantity, float $unitCost): array
    {
        $supplier = Supplier::create([
            'name' => 'Unified Supplier',
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePONumber(),
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'total_amount' => $quantity * $unitCost,
            'status' => PurchaseOrderStatus::APPROVED,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $item = $purchaseOrder->items()->create([
            'drug_id' => null,
            'product_id' => $product->id,
            'item_type' => 'product',
            'quantity_ordered' => $quantity,
            'quantity_received' => 0,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
        ]);

        return [$purchaseOrder, $item];
    }

    private function quantityFor(Product $product, StockLocation $location): float
    {
        return (float) StockBalance::query()
            ->where('product_id', $product->id)
            ->where('stock_location_id', $location->id)
            ->value('quantity_on_hand');
    }

    private function pharmacyAvailable(Product $product): float
    {
        $response = $this->withoutMiddleware()->get(route('admin.pharmacy.drugs.index', ['search' => $product->code]));
        $response->assertOk();

        $paginator = $response->viewData('drugs');
        $catalogueProduct = $paginator->getCollection()->firstWhere('id', $product->id);

        $this->assertNotNull($catalogueProduct);

        return (float) $catalogueProduct->available_in_pharmacy;
    }

    private function labAvailable(Product $product): float
    {
        $response = $this->withoutMiddleware()->get(route('admin.investigations.items.index', ['search' => $product->code]));
        $response->assertOk();

        $paginator = $response->viewData('products');
        $catalogueProduct = $paginator->getCollection()->firstWhere('id', $product->id);

        $this->assertNotNull($catalogueProduct);

        return (float) $catalogueProduct->available_in_lab;
    }

    private function theatreAvailable(Product $product): float
    {
        $response = $this->withoutMiddleware()->get(route('admin.theatre.consumables.index', ['search' => $product->code]));
        $response->assertOk();

        $paginator = $response->viewData('products');
        $catalogueProduct = $paginator->getCollection()->firstWhere('id', $product->id);

        $this->assertNotNull($catalogueProduct);

        return (float) $catalogueProduct->available_in_theatre;
    }

    private function wardAvailable(Product $product): float
    {
        $response = $this->withoutMiddleware()->get(route('admin.wards.consumables.index', ['search' => $product->code]));
        $response->assertOk();

        $paginator = $response->viewData('products');
        $catalogueProduct = $paginator->getCollection()->firstWhere('id', $product->id);

        $this->assertNotNull($catalogueProduct);

        return (float) $catalogueProduct->department_available_quantity;
    }

    private function emergencyAvailable(Product $product): float
    {
        $response = $this->withoutMiddleware()->get(route('admin.emergency.consumables.index', ['search' => $product->code]));
        $response->assertOk();

        $paginator = $response->viewData('products');
        $catalogueProduct = $paginator->getCollection()->firstWhere('id', $product->id);

        $this->assertNotNull($catalogueProduct);

        return (float) $catalogueProduct->department_available_quantity;
    }
}