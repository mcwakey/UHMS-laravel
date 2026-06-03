<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\ActivityLog;
use App\Models\ConsumableUsage;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\ConsumableUsageService;
use App\Services\ProductStockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stock-consumable usage must be traceable on the patient timeline with explicit
 * patient/visit/clinical context — and generic stock movements (no patient) must
 * NOT attach to any patient.
 */
class ConsumableUsageAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private StockLocation $location;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->department = Department::create([
            'name' => 'Audit Lab', 'code' => 'AUD', 'type' => DepartmentType::INVESTIGATION->value,
            'status' => 'active', 'is_stock_managed' => true,
        ]);
        $this->location = StockLocation::create([
            'name' => 'Audit Lab Store', 'type' => 'lab', 'department_id' => $this->department->id,
            'is_active' => true, 'is_main' => false,
        ]);
        $this->product = Product::create([
            'name' => 'Test Reagent', 'code' => 'TST-REAG', 'product_type' => ProductType::REAGENT->value,
            'unit' => 'unit', 'reorder_level' => 5, 'default_cost' => 10, 'base_price' => 15,
            'is_billable' => true, 'is_active' => true, 'created_by' => $this->user->id,
        ]);
        $this->product->departments()->sync([$this->department->id => ['is_active' => true]]);

        app(ProductStockMovementService::class)->createMovement([
            'product_id' => $this->product->id,
            'stock_location_id' => $this->location->id,
            'movement_type' => StockMovementType::OPENING_STOCK,
            'quantity' => 50,
        ]);
    }

    private function patientVisit(): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]);

        return [$patient, $visit->fresh('department')];
    }

    private function service(): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => 'Audit Svc', 'code' => 'AUD-001', 'category' => 'lab', 'price' => 25,
            'is_active' => true, 'is_billable' => true, 'department_id' => $this->department->id,
            'department_type' => DepartmentType::INVESTIGATION->value,
        ]);
    }

    private function record(Visit $visit, string $sourceType, int $sourceId): void
    {
        app(ConsumableUsageService::class)->recordUsageForSource(
            $visit,
            $this->service(),
            $sourceType,
            $sourceId,
            [['product_id' => $this->product->id, 'quantity' => 2]],
            $this->user->id,
        );
    }

    public function test_investigation_consumable_usage_appears_on_patient_timeline(): void
    {
        [$patient, $visit] = $this->patientVisit();
        $this->record($visit, 'investigation_result', 777);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => LogModule::STOCK->value,
            'event' => 'CONSUMABLE_USED',
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        $log = app(ActivityLogService::class)->getPatientTimeline($patient)
            ->where('event', 'CONSUMABLE_USED')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->product->id, (int) $log->properties['product_id']);
        $this->assertSame('investigation_result', $log->properties['source_type']);
        $this->assertSame(777, (int) $log->properties['investigation_request_id']);
        $this->assertEquals(2.0, (float) $log->properties['quantity']);
    }

    public function test_ward_consumable_usage_appears_on_patient_timeline(): void
    {
        [$patient, $visit] = $this->patientVisit();
        $this->record($visit, 'ward_care', 12);

        $this->assertSame(1, app(ActivityLogService::class)->getPatientTimeline($patient)
            ->where('event', 'CONSUMABLE_USED')->count());
    }

    public function test_procedure_consumable_usage_records_procedure_context(): void
    {
        [$patient, $visit] = $this->patientVisit();
        $this->record($visit, 'procedure_request', 55);

        $log = app(ActivityLogService::class)->getPatientTimeline($patient)
            ->where('event', 'CONSUMABLE_USED')->first();
        $this->assertNotNull($log);
        $this->assertSame(55, (int) $log->properties['procedure_request_id']);
    }

    public function test_emergency_consumable_context_lands_on_patient_timeline(): void
    {
        // Exercises the exact logging the EmergencyConsumableService performs:
        // $usage->toActivityContext() carries emergency_case_id.
        [$patient, $visit] = $this->patientVisit();

        $usage = ConsumableUsage::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'emergency_case_id' => 9090,
            'source_type' => 'emergency_care',
            'source_id' => 9090,
            'product_id' => $this->product->id,
            'stock_location_id' => $this->location->id,
            'quantity_used' => 1,
            'is_billable' => true,
            'used_by' => $this->user->id,
            'used_at' => now(),
        ]);

        app(ActivityLogService::class)->log(LogModule::STOCK, 'CONSUMABLE_USED',
            $usage->toActivityContext(), $usage, 'Emergency consumable');

        $log = app(ActivityLogService::class)->getPatientTimeline($patient)
            ->where('event', 'CONSUMABLE_USED')->first();
        $this->assertNotNull($log);
        $this->assertSame(9090, (int) $log->properties['emergency_case_id']);
        $this->assertSame($patient->id, (int) $log->patient_id);
    }

    public function test_generic_stock_movement_does_not_attach_to_a_patient(): void
    {
        [$patient] = $this->patientVisit();

        // A pure stock movement with no patient context (e.g. a stock adjustment).
        app(ProductStockMovementService::class)->createMovement([
            'product_id' => $this->product->id,
            'stock_location_id' => $this->location->id,
            'movement_type' => StockMovementType::OPENING_STOCK,
            'quantity' => 10,
        ]);

        $this->assertSame(0, app(ActivityLogService::class)->getPatientTimeline($patient)
            ->where('event', 'CONSUMABLE_USED')->count());
        // And no activity_log row anywhere wrongly carries this patient for a raw movement.
        $this->assertSame(0, ActivityLog::where('patient_id', $patient->id)->count());
    }
}
