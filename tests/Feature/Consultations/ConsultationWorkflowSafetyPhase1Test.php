<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\ServiceRendering;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationWorkflowSafetyPhase1Test extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Department $consultationDepartment;

    private Department $labDepartment;

    private Department $procedureDepartment;

    private ServiceCatalog $consultationService;

    private ServiceCatalog $labService;

    private ServiceCatalog $procedureService;

    private Drug $drug;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('Doctor', 'web');
        Role::findOrCreate('Pharmacist', 'web');
        Role::findOrCreate('Lab Technician', 'web');
        foreach ([
            'consultations.view',
            'consultations.create',
            'prescriptions.create',
            'procedure.request',
            'lab.requests.create',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->consultationDepartment = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);
        $this->labDepartment = Department::factory()->create([
            'name' => 'Laboratory',
            'type' => DepartmentType::INVESTIGATION->value,
            'status' => 'active',
        ]);
        $this->procedureDepartment = Department::factory()->create([
            'name' => 'Procedure Room',
            'type' => DepartmentType::PROCEDURE->value,
            'status' => 'active',
        ]);

        $this->doctor = User::factory()->create(['department_id' => $this->consultationDepartment->id]);
        $this->doctor->assignRole($role);

        $this->consultationService = $this->service($this->consultationDepartment, 'General Consultation', ServiceType::CONSULTATION);
        $this->labService = $this->service($this->labDepartment, 'Full Blood Count', ServiceType::INVESTIGATION);
        $this->procedureService = $this->service($this->procedureDepartment, 'Wound Dressing', ServiceType::PROCEDURE);

        $category = DrugCategory::create(['name' => 'Antibiotics', 'is_active' => true]);
        $this->drug = Drug::create([
            'category_id' => $category->id,
            'name' => 'Amoxicillin',
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'unit' => 'tablet',
            'price' => 10,
            'requires_prescription' => true,
            'is_active' => true,
        ]);
    }

    public function test_double_submit_prescription_with_same_idempotency_key_creates_one_prescription(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $payload = $this->prescriptionPayload($route);

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $payload, 'rx-key-1')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $payload, 'rx-key-1')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Prescription::where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('prescriptions', [
            'visit_id' => $visit->id,
            'consultation_route_id' => $route->id,
        ]);
    }

    public function test_double_submit_lab_request_with_same_idempotency_key_creates_one_lab_request(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $payload = $this->labPayload($route);

        $this->postJsonWithKey(route('admin.consultations.lab-request.store', $visit), $payload, 'lab-key-1')->assertOk();
        $this->postJsonWithKey(route('admin.consultations.lab-request.store', $visit), $payload, 'lab-key-1')->assertOk();

        $this->assertSame(1, LabRequest::where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('lab_requests', [
            'visit_id' => $visit->id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
        ]);
    }

    public function test_double_submit_procedure_request_with_same_idempotency_key_creates_one_procedure_request(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $payload = $this->procedurePayload($route);

        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $payload, 'proc-key-1')->assertOk();
        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $payload, 'proc-key-1')->assertOk();

        $this->assertSame(1, ProcedureRequest::where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('procedure_requests', [
            'visit_id' => $visit->id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
        ]);
    }

    public function test_same_idempotency_key_with_different_payload_is_rejected(): void
    {
        [$visit, $route] = $this->consultingVisit();

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $this->prescriptionPayload($route), 'rx-key-mismatch')
            ->assertOk();

        $changed = $this->prescriptionPayload($route);
        $changed['notes'] = 'Different payload';

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $changed, 'rx-key-mismatch')
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');

        $this->assertSame(1, Prescription::where('visit_id', $visit->id)->count());
    }

    public function test_completed_consultation_route_blocks_prescription_lab_and_procedure_creation(): void
    {
        [$visit, $route] = $this->consultingVisit(['status' => VisitConsultationRoute::STATUS_COMPLETED]);

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $this->prescriptionPayload($route), 'completed-rx')
            ->assertStatus(423);
        $this->postJsonWithKey(route('admin.consultations.lab-request.store', $visit), $this->labPayload($route), 'completed-lab')
            ->assertStatus(423);
        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $this->procedurePayload($route), 'completed-proc')
            ->assertStatus(423);

        $this->assertSame(0, Prescription::where('visit_id', $visit->id)->count());
        $this->assertSame(0, LabRequest::where('visit_id', $visit->id)->count());
        $this->assertSame(0, ProcedureRequest::where('visit_id', $visit->id)->count());
    }

    public function test_locked_consultation_route_blocks_clinical_entry_mutation(): void
    {
        [$visit, $route] = $this->consultingVisit(['locked_at' => now()]);

        $this->postJsonWithKey(route('admin.consultations.complaints.store', $visit), [
            'consultation_route_id' => $route->id,
            'description' => 'Chest pain',
        ], 'locked-complaint')
            ->assertStatus(423);

        $this->assertSame(0, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_cancelled_consultation_route_blocks_mutation(): void
    {
        [$visit, $route] = $this->consultingVisit(['status' => VisitConsultationRoute::STATUS_CANCELLED]);

        $this->postJsonWithKey(route('admin.consultations.complaints.store', $visit), [
            'consultation_route_id' => $route->id,
            'description' => 'Chest pain',
        ], 'cancelled-complaint')
            ->assertStatus(423);

        $this->assertSame(0, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_wrong_consultation_route_id_for_visit_is_rejected(): void
    {
        [$visit] = $this->consultingVisit();
        [, $wrongRoute] = $this->consultingVisit();

        $payload = $this->procedurePayload($wrongRoute);

        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $payload, 'wrong-route')
            ->assertStatus(422);

        $this->assertSame(0, ProcedureRequest::where('visit_id', $visit->id)->count());
    }

    public function test_missing_consultation_route_id_is_resolved_safely_to_current_session(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $payload = $this->labPayload($route);
        unset($payload['consultation_route_id']);

        $this->postJsonWithKey(route('admin.consultations.lab-request.store', $visit), $payload, 'missing-route-lab')
            ->assertOk();

        $this->assertDatabaseHas('lab_requests', [
            'visit_id' => $visit->id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
        ]);
    }

    public function test_duplicate_submit_does_not_duplicate_invoice_items_or_service_renderings(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $payload = $this->procedurePayload($route);

        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $payload, 'billing-safe-proc')->assertOk();
        $this->postJsonWithKey(route('admin.consultations.procedures.store', $visit), $payload, 'billing-safe-proc')->assertOk();

        $this->assertSame(1, ProcedureRequest::where('visit_id', $visit->id)->count());
        $this->assertSame(0, InvoiceItem::where('visit_id', $visit->id)->count());
        $this->assertSame(0, ServiceRendering::where('visit_id', $visit->id)->count());
    }

    private function postJsonWithKey(string $uri, array $payload, string $key)
    {
        return $this->actingAs($this->doctor)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson($uri, $payload);
    }

    private function consultingVisit(array $routeOverrides = []): array
    {
        $patient = \App\Models\Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $this->consultationDepartment->id,
        ]);

        $route = VisitConsultationRoute::create(array_merge([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->consultationDepartment->id,
            'service_id' => $this->consultationService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ], $routeOverrides));

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        return [$visit, $route->fresh('medicalRecord')];
    }

    private function prescriptionPayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'notes' => 'Safety fixture',
            'items' => [[
                'drug_id' => $this->drug->id,
                'drug_name' => $this->drug->name,
                'dosage' => '500mg',
                'frequency' => 'twice daily',
                'duration' => '5 days',
                'quantity' => 10,
                'route' => 'oral',
                'instructions' => 'After meals',
            ]],
        ];
    }

    private function labPayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'target_department_id' => $this->labDepartment->id,
            'items' => [[
                'service_id' => $this->labService->id,
                'name' => $this->labService->name,
            ]],
            'urgency' => 'routine',
            'clinical_info' => 'Safety fixture',
        ];
    }

    private function procedurePayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'department_id' => $this->procedureDepartment->id,
            'service_catalog_id' => $this->procedureService->id,
            'priority' => 'routine',
            'indication' => 'Safety fixture',
        ];
    }

    private function service(Department $department, string $name, ServiceType $type): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => strtoupper(substr($type->value, 0, 4)).random_int(1000, 9999),
            'category' => $type->value,
            'department_id' => $department->id,
            'department_type' => $department->type instanceof DepartmentType ? $department->type->value : $department->type,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }
}
