<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\InsuranceType;
use App\Enums\Priority;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\BillingService;
use App\Services\VisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VisitConsultationRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    private User $otherDoctor;

    private Department $department;

    private Specialty $specialty;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);

        $this->specialty = Specialty::create([
            'name' => 'Internal Medicine',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $adminRole = Role::findOrCreate('Admin', 'web');
        foreach (['visits.view', 'visits.create', 'visits.edit', 'consultations.view', 'consultations.create'] as $permission) {
            $adminRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $doctorRole = Role::findOrCreate('Doctor', 'web');

        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $this->admin->assignRole($adminRole);

        $this->doctor = User::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'department_id' => $this->department->id,
        ]);
        $this->doctor->assignRole($doctorRole);
        $this->doctor->specialties()->attach($this->specialty->id);

        $otherDepartment = Department::factory()->create([
            'name' => 'Surgery',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $otherSpecialty = Specialty::create([
            'name' => 'Surgery',
            'department_id' => $otherDepartment->id,
            'is_active' => true,
        ]);

        $this->otherDoctor = User::factory()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'department_id' => $otherDepartment->id,
        ]);
        $this->otherDoctor->assignRole($doctorRole);
        $this->otherDoctor->specialties()->attach($otherSpecialty->id);
    }

    public function test_department_visit_options_load_services_and_specialty_linked_doctors(): void
    {
        $service = $this->makeService($this->department);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.departments.visit-options', $this->department));

        $response->assertOk()
            ->assertJsonPath('services.0.id', $service->id)
            ->assertJsonPath('services.0.base_price', 50)
            ->assertJsonPath('services.0.department_id', $this->department->id)
            ->assertJsonPath('services.0.department_type', DepartmentType::CONSULTATION->value)
            ->assertJsonFragment(['id' => $this->doctor->id]);

        $this->assertNotContains(
            $this->otherDoctor->id,
            collect($response->json('doctors'))->pluck('id')->all()
        );
    }

    public function test_create_visit_saves_selected_doctor_on_consultation_route_not_visit(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);

        $this->actingAs($this->admin)
            ->post(route('admin.visits.store'), $this->visitPayload($patient, [[
                'service_catalog_id' => $service->id,
                'department_id' => $this->department->id,
                'doctor_id' => $this->doctor->id,
                'quantity' => 1,
            ]]))
            ->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();
        $route = VisitConsultationRoute::where('visit_id', $visit->id)->firstOrFail();

        $this->assertFalse(Schema::hasColumn('visits', 'assigned_doctor_id'));
        $this->assertSame($this->doctor->id, $route->doctor_id);
        $this->assertSame(VisitConsultationRoute::STATUS_PENDING, $route->status);
        $this->assertSame($this->department->id, $route->department_id);
        $this->assertDatabaseHas('visit_consultation_route_services', [
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'service_id' => $service->id,
        ]);
        $this->assertSame(1, InvoiceItem::where('visit_id', $visit->id)->where('service_catalog_id', $service->id)->count());
    }

    public function test_create_visit_notifies_vitals_users_that_patient_is_waiting_for_triage(): void
    {
        $triageRole = Role::findOrCreate('Triage Nurse', 'web');
        $triageRole->givePermissionTo(Permission::findOrCreate('vitals.create', 'web'));
        $triageUser = User::factory()->create(['department_id' => $this->department->id]);
        $triageUser->assignRole($triageRole);

        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);

        $this->actingAs($this->admin)
            ->post(route('admin.visits.store'), $this->visitPayload($patient, [[
                'service_catalog_id' => $service->id,
                'department_id' => $this->department->id,
                'quantity' => 1,
            ]]))
            ->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();
        $notification = $triageUser->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Patient waiting for triage', $notification->data['title'] ?? null);
        $this->assertSame('CONSULTATION', $notification->data['module'] ?? null);
        $this->assertSame('triage_queue', $notification->data['source_type'] ?? null);
        $this->assertSame($visit->id, $notification->data['source_id'] ?? null);
        $this->assertSame($visit->id, $notification->data['visit_id'] ?? null);
        $this->assertStringContainsString('/admin/triage/'.$visit->id, $notification->data['url'] ?? '');
    }

    public function test_visit_insurance_change_only_affects_future_billed_items(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $oldProvider = InsuranceProvider::create([
            'name' => 'Old Health Cover',
            'short_name' => 'OLD',
            'type' => InsuranceType::PRIVATE,
            'is_active' => true,
            'is_default' => false,
        ]);
        $newProvider = InsuranceProvider::create([
            'name' => 'New Health Cover',
            'short_name' => 'NEW',
            'type' => InsuranceType::CORPORATE,
            'is_active' => true,
            'is_default' => false,
        ]);
        $oldInsurance = PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $oldProvider->id,
            'member_type' => 'holder',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $newInsurance = PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $newProvider->id,
            'member_type' => 'holder',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $firstService = $this->makeService($this->department);
        $secondService = $this->makeService($this->department);

        $payload = $this->visitPayload($patient, [[
            'service_catalog_id' => $firstService->id,
            'department_id' => $this->department->id,
            'quantity' => 1,
        ]]);
        $payload['visit_insurance_id'] = $oldInsurance->id;

        $this->actingAs($this->admin)
            ->post(route('admin.visits.store'), $payload)
            ->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();
        $firstItem = InvoiceItem::where('visit_id', $visit->id)
            ->where('service_catalog_id', $firstService->id)
            ->firstOrFail();

        $this->assertSame($oldInsurance->id, $firstItem->patient_insurance_id);

        $this->actingAs($this->admin)
            ->patch(route('admin.visits.insurance.update', $visit), [
                'visit_insurance_id' => $newInsurance->id,
            ])
            ->assertRedirect();

        $this->assertSame($newInsurance->id, $visit->fresh()->visit_insurance_id);
        $this->assertSame($oldInsurance->id, $firstItem->fresh()->patient_insurance_id);

        app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit->fresh(),
            service: $secondService,
            sourceType: 'future_visit_service',
            sourceId: $secondService->id,
            departmentId: $this->department->id,
        );

        $secondItem = InvoiceItem::where('visit_id', $visit->id)
            ->where('service_catalog_id', $secondService->id)
            ->firstOrFail();

        $this->assertSame($newInsurance->id, $secondItem->patient_insurance_id);
        $this->assertSame($newProvider->id, $secondItem->insurance_provider_id);
    }

    public function test_multiple_consultation_services_in_same_department_create_one_department_route(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $firstService = $this->makeService($this->department);
        $secondService = $this->makeService($this->department);

        $this->actingAs($this->admin)
            ->post(route('admin.visits.store'), $this->visitPayload($patient, [
                [
                    'service_catalog_id' => $firstService->id,
                    'department_id' => $this->department->id,
                    'doctor_id' => $this->doctor->id,
                    'quantity' => 1,
                ],
                [
                    'service_catalog_id' => $secondService->id,
                    'department_id' => $this->department->id,
                    'doctor_id' => $this->doctor->id,
                    'quantity' => 1,
                ],
            ]))
            ->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();

        $this->assertSame(1, VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->department->id)
            ->count());

        $route = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->department->id)
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$firstService->id, $secondService->id],
            VisitConsultationRouteService::where('visit_consultation_route_id', $route->id)
                ->pluck('service_id')
                ->all()
        );
    }

    public function test_non_consultation_services_do_not_create_consultation_routes(): void
    {
        $labDepartment = Department::factory()->create([
            'name' => 'Laboratory',
            'type' => DepartmentType::INVESTIGATION->value,
        ]);
        $service = $this->makeService($labDepartment, DepartmentType::INVESTIGATION);
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.visits.store'), $this->visitPayload($patient, [[
                'service_catalog_id' => $service->id,
                'department_id' => $labDepartment->id,
                'doctor_id' => $this->doctor->id,
                'quantity' => 1,
            ]]))
            ->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->firstOrFail();

        $this->assertSame(0, VisitConsultationRoute::where('visit_id', $visit->id)->count());
        $this->assertSame(1, InvoiceItem::where('visit_id', $visit->id)->where('service_catalog_id', $service->id)->count());
    }

    public function test_doctor_not_linked_to_department_specialty_cannot_be_assigned(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);

        $this->actingAs($this->admin)
            ->postJson(route('admin.visits.store'), $this->visitPayload($patient, [[
                'service_catalog_id' => $service->id,
                'department_id' => $this->department->id,
                'doctor_id' => $this->otherDoctor->id,
                'quantity' => 1,
            ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['services.0.doctor_id']);
    }

    public function test_create_visit_page_selected_services_table_has_no_assigned_staff_column(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.visits.create'));
        $content = $response->getContent();

        $response->assertOk();
        $this->assertStringContainsString('Assign Doctor\\/Staff', $content);
        $this->assertStringNotContainsString('Assigned Staff', $content);
        $this->assertStringNotContainsString('assigned_staff_id', $content);
    }

    public function test_consultation_queue_displays_route_doctor(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING_CONSULTATION,
            'current_department_id' => $this->department->id,
        ]);

        VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSee($visit->visit_number)
            ->assertSee('Dr. '.$this->doctor->full_name);
    }

    public function test_start_consultation_keeps_existing_route_doctor(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING_CONSULTATION,
            'current_department_id' => $this->department->id,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
        app(VisitWorkflowService::class)->startConsultation($visit, $this->admin);

        $route->refresh();
        $this->assertSame($this->doctor->id, $route->doctor_id);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->status);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
    }

    public function test_visit_page_route_activation_only_changes_the_route_status(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $service = $this->makeService($this->department);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING_CONSULTATION,
            'current_department_id' => $this->department->id,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.activate', [$visit, $route]), [
                'route_only' => '1',
            ])
            ->assertRedirect(route('admin.visits.show', $visit));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertSame(VisitStatus::WAITING_CONSULTATION, $visit->fresh()->status);
    }

    private function makeService(Department $department, DepartmentType $type = DepartmentType::CONSULTATION): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $department->name.' Service',
            'code' => 'SVC'.random_int(1000, 9999),
            'category' => $type === DepartmentType::CONSULTATION
                ? ServiceType::CONSULTATION->value
                : ServiceType::INVESTIGATION->value,
            'department_id' => $department->id,
            'department_type' => $type->value,
            'price' => 50,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }

    private function visitPayload(Patient $patient, array $services): array
    {
        return [
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'Routing regression',
            'services' => $services,
        ];
    }
}
