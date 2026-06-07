<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PrescriptionStatus;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\InvoiceItem;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PharmacyBillingSelection;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Services\PharmacyBillingSelectionService;
use App\Services\PharmacyService;
use App\Services\ProductStockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PharmacyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Pharmacist']);
        foreach ([
            'pharmacy.dispensing.view', 'pharmacy.dispensing.create',
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            'stock.view',
            'patients.view', 'visits.view', 'prescriptions.view',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    public function test_dispensing_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.pharmacy.dispensing.index'));
        $response->assertStatus(200);
    }

    public function test_drug_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.pharmacy.drugs.index'));
        $response->assertStatus(200);
    }

    public function test_drug_stock_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.product-stock.balances'));
        $response->assertStatus(200);
    }

    public function test_selected_prescription_items_are_billed_without_deducting_stock(): void
    {
        $this->actingAs($this->user);

        $department = $this->pharmacyDepartment();
        $location = $this->pharmacyLocation($department);
        [$firstProduct, $firstDrug] = $this->drugProduct($department, $location, 'AMOX', 10);
        [, $secondDrug] = $this->drugProduct($department, $location, 'CEFT', 8);
        [$prescription, $firstItem, $secondItem] = $this->prescriptionWithItems([
            [$firstDrug, 5],
            [$secondDrug, 4],
        ]);

        $created = app(PharmacyBillingSelectionService::class)->billSelectedItems($prescription, [
            $firstItem->id => ['selected' => true, 'quantity' => 2],
            $secondItem->id => ['selected' => false, 'quantity' => 4],
        ]);

        $this->assertCount(1, $created);
        $this->assertSame(10.0, $this->quantityFor($firstProduct, $location));
        $this->assertDatabaseHas('pharmacy_billing_selections', [
            'prescription_item_id' => $firstItem->id,
            'product_id' => $firstProduct->id,
            'billed_quantity' => 2,
            'dispensed_quantity' => 0,
            'status' => PharmacyBillingSelection::STATUS_BILLED,
        ]);
        $this->assertDatabaseMissing('pharmacy_billing_selections', [
            'prescription_item_id' => $secondItem->id,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $firstProduct->id,
            'source_type' => InvoiceItem::SOURCE_PHARMACY_BILLING_SELECTION,
            'quantity' => 2,
        ]);
        $this->assertSame(PrescriptionStatus::PARTIALLY_BILLED->value, $prescription->refresh()->status->value);
    }

    public function test_unbilled_prescription_item_cannot_be_dispensed(): void
    {
        $this->actingAs($this->user);

        $department = $this->pharmacyDepartment();
        $location = $this->pharmacyLocation($department);
        $this->drugProduct($department, $location, 'UNBILLED', 10);
        [, $item] = $this->prescriptionWithOneItem('UNBILLED', 3);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has not been billed');

        app(PharmacyService::class)->dispenseItem($item, 1);
    }

    public function test_dispensing_billed_quantity_deducts_stock_once_and_updates_selection(): void
    {
        $this->actingAs($this->user);

        $department = $this->pharmacyDepartment();
        $location = $this->pharmacyLocation($department);
        [$product] = $this->drugProduct($department, $location, 'DISP', 10);
        [$prescription, $item] = $this->prescriptionWithOneItem('DISP', 3);

        app(PharmacyBillingSelectionService::class)->billSelectedItems($prescription, [
            $item->id => ['selected' => true, 'quantity' => 3],
        ]);

        $this->assertSame(10.0, $this->quantityFor($product, $location));

        // Pay-before-dispense: settle the bill, then dispense.
        $this->settleBilledItems($prescription);
        app(PharmacyService::class)->dispenseItem($item->refresh(), 2, 'First pickup');

        $selection = PharmacyBillingSelection::query()->where('prescription_item_id', $item->id)->firstOrFail();

        $this->assertSame(8.0, $this->quantityFor($product, $location));
        $this->assertSame(2.0, (float) $selection->dispensed_quantity);
        $this->assertSame(PharmacyBillingSelection::STATUS_PARTIALLY_DISPENSED, $selection->status);
        $this->assertSame(1, InvoiceItem::where('source_type', InvoiceItem::SOURCE_PHARMACY_BILLING_SELECTION)->count());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $location->id,
            'movement_type' => StockMovementType::PHARMACY_DISPENSED->value,
            'quantity' => 2,
        ]);
    }

    private function pharmacyDepartment(): Department
    {
        return Department::updateOrCreate(
            ['code' => 'PHM'],
            [
                'name' => 'Pharmacy',
                'type' => DepartmentType::PHARMACY->value,
                'status' => 'active',
                'is_stock_managed' => true,
            ],
        );
    }

    private function pharmacyLocation(Department $department): StockLocation
    {
        return StockLocation::updateOrCreate(
            ['name' => 'Pharmacy Store'],
            [
                'type' => 'pharmacy',
                'department_id' => $department->id,
                'is_active' => true,
                'is_main' => false,
            ],
        );
    }

    private function drugProduct(Department $department, StockLocation $location, string $code, int $stockQuantity): array
    {
        $category = DrugCategory::firstOrCreate(['name' => 'Test Pharmacy Category']);

        $product = Product::create([
            'name' => "{$code} Test Drug",
            'code' => "{$code}-001",
            'product_type' => ProductType::DRUG->value,
            'unit' => 'tablet',
            'reorder_level' => 1,
            'default_cost' => 4,
            'base_price' => 10,
            'is_billable' => true,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
        $product->departments()->sync([$department->id => ['is_active' => true]]);

        $drug = Drug::create([
            'product_id' => $product->id,
            'category_id' => $category->id,
            'name' => "{$code} Test Drug",
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'unit' => 'tablet',
            'price' => 10,
            'reorder_level' => 1,
            'requires_prescription' => true,
            'is_active' => true,
        ]);

        app(ProductStockMovementService::class)->createMovement([
            'product_id' => $product->id,
            'stock_location_id' => $location->id,
            'movement_type' => StockMovementType::OPENING_STOCK,
            'quantity' => $stockQuantity,
        ]);

        return [$product, $drug];
    }

    private function prescriptionWithOneItem(string $drugCode, int $quantity): array
    {
        $drug = Drug::where('name', "{$drugCode} Test Drug")->firstOrFail();

        return $this->prescriptionWithItems([[$drug, $quantity]]);
    }

    public function test_pharmacy_billing_and_dispensing_log_distinctly_on_patient_timeline(): void
    {
        $this->actingAs($this->user);

        $department = $this->pharmacyDepartment();
        $location = $this->pharmacyLocation($department);
        [$product] = $this->drugProduct($department, $location, 'TLINE', 10);
        [$prescription, $item] = $this->prescriptionWithOneItem('TLINE', 3);

        app(PharmacyBillingSelectionService::class)->billSelectedItems($prescription, [
            $item->id => ['selected' => true, 'quantity' => 3],
        ]);
        $this->settleBilledItems($prescription);
        app(PharmacyService::class)->dispenseItem($item->refresh(), 2, 'First pickup'); // partial 2 of 3

        $patient = $prescription->patient;
        $timeline = app(\App\Services\ActivityLogService::class)->getPatientTimeline($patient)->get();
        $events = $timeline->pluck('event')->all();

        // Billing and dispensing are DISTINCT pharmacy events (not collapsed).
        $this->assertContains('PRESCRIPTION_ITEMS_BILLED', $events);
        $this->assertContains('PARTIAL_DISPENSE_COMPLETED', $events);
        $this->assertSame(1, $timeline->where('event', 'PRESCRIPTION_ITEMS_BILLED')->count());
        $this->assertSame(1, $timeline->where('event', 'PARTIAL_DISPENSE_COMPLETED')->count());

        // Billing carries product + invoice line context.
        $billed = $timeline->firstWhere('event', 'PRESCRIPTION_ITEMS_BILLED');
        $this->assertSame('PHARMACY', $billed->log_name);
        $this->assertSame($product->id, (int) $billed->properties['product_id']);
        $this->assertNotNull($billed->properties['invoice_item_id']);

        // Dispensing carries stock-deduction context (references the ledger movement).
        $dispensed = $timeline->firstWhere('event', 'PARTIAL_DISPENSE_COMPLETED');
        $this->assertSame($location->id, (int) $dispensed->properties['stock_location_id']);
        $this->assertNotNull($dispensed->properties['stock_movement_id']);
        $this->assertStringContainsString('TLINE', $dispensed->description);

        // Dispensing must NOT create a MAR dose-administration log.
        $this->assertNotContains('DOSE_ADMINISTERED', $events);
    }

    private function prescriptionWithItems(array $drugRows): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
        ]);
        $medicalRecord = MedicalRecord::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->user->id,
        ]);
        $prescription = Prescription::create([
            'medical_record_id' => $medicalRecord->id,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->user->id,
            'prescription_number' => Prescription::generatePrescriptionNumber(),
            'status' => PrescriptionStatus::PENDING->value,
        ]);

        $items = collect($drugRows)->map(function (array $row) use ($prescription) {
            [$drug, $quantity] = $row;

            return PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'drug_id' => $drug->id,
                'drug_name' => $drug->name,
                'dosage' => '1 tablet',
                'frequency' => 'Daily',
                'duration' => '3 days',
                'quantity' => $quantity,
                'route' => 'oral',
            ]);
        })->values();

        return array_merge([$prescription], $items->all());
    }

    /** Mark the invoice lines created for this prescription's billing as fully paid. */
    private function settleBilledItems(Prescription $prescription): void
    {
        $itemIds = PharmacyBillingSelection::where('prescription_id', $prescription->id)
            ->pluck('invoice_item_id')->filter();

        InvoiceItem::whereIn('id', $itemIds)->get()->each(function (InvoiceItem $i) {
            $i->forceFill([
                'paid_amount' => $i->patient_payable,
                'balance' => 0,
                'payment_status' => 'paid',
            ])->save();
        });
    }

    private function quantityFor(Product $product, StockLocation $location): float
    {
        return (float) StockBalance::query()
            ->where('product_id', $product->id)
            ->where('stock_location_id', $location->id)
            ->value('quantity_on_hand');
    }
}
