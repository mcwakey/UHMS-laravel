<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\ServiceRendering;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
use App\Services\ServiceRenderingService;
use App\Services\VisitPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceRenderingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'Treatment Room',
            'code' => 'TRT',
            'type' => DepartmentType::TREATMENT->value,
        ]);

        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        $role = Role::findOrCreate('Service Rendering Tester', 'web');
        foreach ([
            'service_rendering.view',
            'service_rendering.start',
            'service_rendering.mark_rendered',
            'service_rendering.mark_not_rendered',
            'service_rendering.cancel',
            'service_rendering.edit_notes',
            'service_rendering.reports',
            'visits.preview',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
        ]);
    }

    public function test_non_specialized_invoice_item_creates_service_rendering(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Wound Dressing');

        $item = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'emergency_service',
            sourceId: $service->id,
            departmentId: $this->department->id,
        );

        $this->assertDatabaseHas('service_renderings', [
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'invoice_item_id' => $item->id,
            'service_id' => $service->id,
            'department_id' => $this->department->id,
            'status' => ServiceRendering::STATUS_PENDING,
        ]);
    }

    public function test_specialized_services_do_not_create_service_renderings(): void
    {
        $this->actingAs($this->user);

        foreach ([
            ['Consultation Fee', 'consultation', DepartmentType::CONSULTATION, InvoiceItem::SOURCE_CONSULTATION_SERVICE],
            ['Malaria Test', 'lab', DepartmentType::INVESTIGATION, InvoiceItem::SOURCE_INVESTIGATION_SERVICE],
            ['Appendectomy', 'procedure', DepartmentType::PROCEDURE, InvoiceItem::SOURCE_PROCEDURE_SERVICE],
            ['Dispensed Drug', 'pharmacy', DepartmentType::PHARMACY, InvoiceItem::SOURCE_PHARMACY_PRODUCT],
        ] as [$name, $category, $type, $source]) {
            $department = Department::factory()->create(['type' => $type->value]);
            $visit = $this->visit();
            $service = $this->service($name, $category, $department, $type);

            app(BillingService::class)->addItemToVisitInvoice(
                visit: $visit,
                service: $service,
                sourceType: $source,
                sourceId: $service->id,
                departmentId: $department->id,
            );
        }

        $this->assertSame(0, ServiceRendering::count());
    }

    public function test_service_rendering_worklist_is_visible_to_authorized_department_user(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Nebulization');
        app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);

        $this->get(route('admin.service-renderings.index'))
            ->assertOk()
            ->assertSee('Nebulization')
            ->assertSee('PENDING');
    }

    public function test_other_department_user_without_view_all_cannot_start_rendering(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Dressing Change');
        app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);
        $rendering = ServiceRendering::firstOrFail();

        $otherDepartment = Department::factory()->create(['type' => DepartmentType::TREATMENT->value]);
        $otherUser = User::factory()->create([
            'department_id' => $otherDepartment->id,
            'status' => 'active',
        ]);
        $role = Role::findOrCreate('Other Rendering Tester', 'web');
        $role->givePermissionTo(Permission::findOrCreate('service_rendering.view', 'web'));
        $role->givePermissionTo(Permission::findOrCreate('service_rendering.start', 'web'));
        $otherUser->assignRole($role);

        $this->actingAs($otherUser)
            ->post(route('admin.service-renderings.start', $rendering))
            ->assertForbidden();
    }

    public function test_rendering_actions_update_rendering_only_not_invoice_payment(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Injection Administration');
        $item = app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);
        $originalPaymentStatus = $item->payment_status;
        $originalBalance = (float) $item->balance;

        $rendering = ServiceRendering::firstOrFail();

        $this->post(route('admin.service-renderings.start', $rendering))
            ->assertRedirect();

        $this->post(route('admin.service-renderings.mark-rendered', $rendering), [
            'result_summary' => 'Injection administered safely.',
            'notes' => 'No immediate reaction.',
        ])->assertRedirect();

        $rendering->refresh();
        $item->refresh();

        $this->assertSame(ServiceRendering::STATUS_RENDERED, $rendering->status);
        $this->assertSame($this->user->id, $rendering->rendered_by);
        $this->assertNotNull($rendering->rendered_at);
        $this->assertSame($originalPaymentStatus, $item->payment_status);
        $this->assertSame($originalBalance, (float) $item->balance);
    }

    public function test_not_rendered_requires_reason(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Ear Syringing');
        app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);

        $this->post(route('admin.service-renderings.mark-not-rendered', ServiceRendering::firstOrFail()), [])
            ->assertSessionHasErrors('reason_not_rendered');
    }

    public function test_service_rendering_creation_is_idempotent_for_invoice_item(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Physiotherapy Session');
        $item = app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);

        app(ServiceRenderingService::class)->createForInvoiceItem($item, $this->user);
        app(ServiceRenderingService::class)->createForInvoiceItem($item, $this->user);

        $this->assertSame(1, ServiceRendering::where('invoice_item_id', $item->id)->count());
    }

    public function test_visit_preview_includes_service_rendering_context(): void
    {
        $this->actingAs($this->user);

        $visit = $this->visit();
        $service = $this->service('Wound Dressing Review');
        app(BillingService::class)->addItemToVisitInvoice($visit, $service, 'service_catalog', $service->id, departmentId: $this->department->id);
        $rendering = ServiceRendering::firstOrFail();

        app(ServiceRenderingService::class)->markRendered($rendering, $this->user, [
            'result_summary' => 'Dressing completed.',
        ]);

        $preview = app(VisitPreviewService::class)->build($visit->fresh());
        $titles = collect($preview['timeline'])->pluck('title')->implode(' | ');

        $this->assertStringContainsString('Wound Dressing Review Awaiting Rendering', $titles);
        $this->assertStringContainsString('Wound Dressing Review Rendered', $titles);
        $this->assertSame(1, $preview['summary']['service_renderings_count']);
    }

    private function visit(): Visit
    {
        return Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'status' => VisitStatus::REGISTERED,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]);
    }

    private function service(
        string $name,
        string $category = 'treatment',
        ?Department $department = null,
        ?DepartmentType $departmentType = null,
    ): ServiceCatalog {
        $department ??= $this->department;
        $departmentType ??= DepartmentType::TREATMENT;

        return ServiceCatalog::create([
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)).random_int(1000, 9999),
            'category' => $category,
            'department_id' => $department->id,
            'department_type' => $departmentType->value,
            'price' => 25,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }
}
