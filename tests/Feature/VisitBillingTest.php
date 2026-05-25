<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for the visit-creation billing path.
 *
 * The historical bug: VisitController::store() called BOTH attachServices()
 * AND autoCreateInvoiceForVisit(), causing each selected service to be billed
 * TWICE on the invoice (once as source_type='service_catalog' and once as
 * source_type='visit_service'). The fix: attachServices() is the sole billing
 * path, and BillingService::addItemToVisitInvoice() now blocks cross-
 * source-type duplicates on the same (invoice, service_catalog_id).
 */
class VisitBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::create(['name' => 'Admin']);
        foreach (['visits.view', 'visits.create', 'visits.edit', 'patients.view'] as $p) {
            $role->givePermissionTo(Permission::create(['name' => $p]));
        }
        $this->user->assignRole($role);
    }

    private function makeService(float $price = 50.0): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'GC'.random_int(1000, 9999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $this->department->id,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function storePayload(int $patientId, array $services = []): array
    {
        return [
            'patient_id' => $patientId,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'Test visit',
            'services' => $services,
        ];
    }

    public function test_creating_visit_with_one_service_creates_exactly_one_invoice_item(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $service = $this->makeService(75.0);

        $this->actingAs($this->user)->post(
            route('admin.visits.store'),
            $this->storePayload($patient->id, [
                ['service_catalog_id' => $service->id, 'quantity' => 1],
            ])
        )->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();

        $count = InvoiceItem::where('visit_id', $visit->id)
            ->where('service_catalog_id', $service->id)
            ->count();

        $this->assertSame(
            1,
            $count,
            'Selected service should be billed exactly once (no double billing).'
        );
    }

    public function test_creating_visit_with_multiple_services_creates_one_item_per_service(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $svcA = $this->makeService(60.0);
        $svcB = $this->makeService(120.0);

        $this->actingAs($this->user)->post(
            route('admin.visits.store'),
            $this->storePayload($patient->id, [
                ['service_catalog_id' => $svcA->id, 'quantity' => 1],
                ['service_catalog_id' => $svcB->id, 'quantity' => 1],
            ])
        )->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();

        $items = InvoiceItem::where('visit_id', $visit->id)->get();
        $this->assertCount(2, $items, 'Two distinct services should yield exactly two invoice items.');
        $this->assertSame(
            [$svcA->id, $svcB->id],
            $items->pluck('service_catalog_id')->sort()->values()->all()
        );
    }

    public function test_consultation_service_selection_creates_pending_consultation_route(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $service = $this->makeService();

        $this->actingAs($this->user)->post(
            route('admin.visits.store'),
            $this->storePayload($patient->id, [
                ['service_catalog_id' => $service->id, 'quantity' => 1],
            ])
        )->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();

        $route = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->department->id)
            ->first();

        $this->assertNotNull($route, 'A consultation route should be created for the selected consultation department.');
        $this->assertSame(VisitConsultationRoute::STATUS_PENDING, $route->status);
        $this->assertSame($this->department->id, $route->department_id);
        $this->assertSame(1, VisitConsultationRouteService::where('visit_consultation_route_id', $route->id)
            ->where('service_id', $service->id)
            ->count());
    }

    public function test_billing_service_blocks_cross_source_type_duplicates(): void
    {
        $this->actingAs($this->user);

        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $service = $this->makeService();

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::REGISTERED,
        ]);

        $billing = app(BillingService::class);

        // First bill: source_type='service_catalog'
        $billing->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id);

        // Second bill of the SAME service via a different source_type MUST be rejected.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate billing prevented');

        $billing->addItemToVisitInvoice($visit, $service, 'visit_service', 9999);
    }
}
