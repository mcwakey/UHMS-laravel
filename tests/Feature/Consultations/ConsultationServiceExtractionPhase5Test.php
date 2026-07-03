<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\Consultations\ConsultationClinicalEntryController;
use App\Http\Controllers\Doctor\Consultations\ConsultationOrderController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPlanningController;
use App\Http\Controllers\Doctor\Consultations\ConsultationPrescriptionController;
use App\Http\Controllers\Doctor\Consultations\ConsultationSessionController;
use App\Http\Requests\Consultations\StoreConsultationLabRequest;
use App\Http\Requests\Consultations\StoreConsultationPrescriptionRequest;
use App\Http\Requests\Consultations\StoreConsultationProcedureRequest;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\ConsultationActionContext;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\ConsultationOrderWorkflowService;
use App\Services\Consultation\ConsultationPrescriptionWorkflowService;
use App\Services\Consultation\ConsultationSessionWorkflowService;
use App\Services\Consultation\ConsultationWorkspacePayloadService;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationServiceExtractionPhase5Test extends TestCase
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
            'visits.transition',
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

    public function test_workflow_concerns_delegate_heavy_orchestration_to_services(): void
    {
        $expectations = [
            'HandlesConsultationOrders.php' => ['orderWorkflow', 'createLabRequest', 'createProcedureRequest'],
            'HandlesConsultationPrescriptions.php' => ['prescriptionWorkflow', 'storePrescription'],
            'HandlesConsultationSessions.php' => ['sessionWorkflow', 'completeRoute', 'cancelRoute'],
            'HandlesConsultationPlanning.php' => ['planningWorkflow', 'createFollowUp', 'refer'],
            'HandlesConsultationWorkspace.php' => ['workspacePayloads', 'summaryPayload'],
            'HandlesConsultationClinicalEntries.php' => ['clinicalEntryWorkflow', 'createComplaint', 'createDiagnosis'],
        ];

        foreach ($expectations as $file => $needles) {
            $source = file_get_contents(app_path('Http/Controllers/Doctor/Consultations/Concerns/'.$file));
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $source);
            }
        }

        $this->assertStringNotContainsString('idempotency->run', file_get_contents(app_path('Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationOrders.php')));
        $this->assertStringNotContainsString('idempotency->run', file_get_contents(app_path('Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationPrescriptions.php')));
        $this->assertStringNotContainsString('idempotency->run', file_get_contents(app_path('Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationClinicalEntries.php')));
        $this->assertStringContainsString('Compatibility shell', file_get_contents(app_path('Http/Controllers/Doctor/ConsultationController.php')));
    }

    public function test_workspace_payload_service_returns_required_consultation_context(): void
    {
        [$visit, $route] = $this->consultingVisit();

        $payload = app(ConsultationWorkspacePayloadService::class)->summaryPayload($visit, $route->id);

        $this->assertSame($route->id, $payload['route']->id);
        $this->assertSame($route->medicalRecord->id, $payload['record']->id);
        $this->assertArrayHasKey('consultationSummary', $payload);
    }

    public function test_order_workflow_service_preserves_consultation_route_context_for_lab_and_procedure(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $context = new ConsultationActionContext($visit, $route, $route->medicalRecord, $this->doctor);
        Auth::login($this->doctor);

        $orders = app(ConsultationOrderWorkflowService::class);

        $lab = $orders->createLabRequest($this->requestWithUser('phase5-lab'), $visit, $context, $this->labPayload($route));
        $procedure = $orders->createProcedureRequest($this->requestWithUser('phase5-procedure'), $visit, $context, $this->procedurePayload($route), $this->doctor);

        $this->assertSame($route->id, $lab->consultation_route_id);
        $this->assertSame($route->medicalRecord->id, $lab->medical_record_id);
        $this->assertSame($route->id, $procedure->consultation_route_id);
        $this->assertSame($route->medicalRecord->id, $procedure->medical_record_id);
    }

    public function test_prescription_workflow_service_preserves_idempotency_replay_behaviour(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $context = new ConsultationActionContext($visit, $route, $route->medicalRecord, $this->doctor);
        Auth::login($this->doctor);

        $service = app(ConsultationPrescriptionWorkflowService::class);
        $request = $this->requestWithUser('phase5-prescription');

        $first = $service->create($request, $visit, $context, $this->prescriptionPayload($route));
        $second = $service->create($request, $visit, $context, $this->prescriptionPayload($route));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Prescription::where('visit_id', $visit->id)->count());
    }

    public function test_session_workflow_service_preserves_completed_route_blocking(): void
    {
        [$visit, $route] = $this->consultingVisit(['status' => VisitConsultationRoute::STATUS_COMPLETED]);

        $this->expectException(ConsultationActionException::class);

        app(ConsultationSessionWorkflowService::class)->completeRoute($visit, $route, $this->doctor, 'Already completed');
    }

    public function test_form_requests_and_route_ownership_remain_stable(): void
    {
        $expectedRequests = [
            [ConsultationPrescriptionController::class, 'storePrescription', StoreConsultationPrescriptionRequest::class],
            [ConsultationOrderController::class, 'storeLabRequest', StoreConsultationLabRequest::class],
            [ConsultationOrderController::class, 'storeProcedureRequest', StoreConsultationProcedureRequest::class],
        ];

        foreach ($expectedRequests as [$controller, $method, $request]) {
            $reflection = new ReflectionMethod($controller, $method);
            $this->assertSame($request, $reflection->getParameters()[0]->getType()?->getName());
        }

        $this->assertSame(ConsultationOrderController::class.'@storeLabRequest', Route::getRoutes()->getByName('admin.consultations.lab-request.store')?->getActionName());
        $this->assertSame(ConsultationSessionController::class.'@completeRoute', Route::getRoutes()->getByName('admin.consultations.routes.complete')?->getActionName());
        $this->assertSame(ConsultationPlanningController::class.'@refer', Route::getRoutes()->getByName('admin.consultations.refer')?->getActionName());
        $this->assertFalse((new \ReflectionClass(ConsultationController::class))->hasMethod('storeLabRequest'));
        $this->assertTrue(method_exists(ConsultationClinicalEntryController::class, 'storeDiagnosis'));
    }

    public function test_phase_two_config_and_view_cache_still_compile(): void
    {
        $markup = file_get_contents(resource_path('views/consultations/show.blade.php'))."\n"
            .collect(glob(resource_path('views/consultations/partials/*.blade.php')) ?: [])
                ->map(fn (string $file): string => file_get_contents($file))
                ->implode("\n");

        $this->assertStringContainsString('id="consultation-page-config"', $markup);
        $this->assertStringContainsString("@vite('resources/js/Pages/consultation-show.js')", $markup);
        $this->assertSame(0, Artisan::call('view:cache'));
        $this->assertSame(0, Artisan::call('view:clear'));
    }

    public function test_browser_fixture_strategy_is_available_for_smoke(): void
    {
        $seeder = file_get_contents(database_path('seeders/Testing/ConsultationWorkspaceE2ESeeder.php'));
        $fixtureService = file_get_contents(app_path('Services/Consultation/ConsultationBrowserFixtureService.php'));
        $spec = file_get_contents(base_path('tests-e2e/tests/consultation-workspace.spec.ts'));

        $this->assertStringContainsString("App::environment(['local', 'testing'])", $seeder);
        $this->assertStringContainsString('consultation.e2e@uhms.test', $fixtureService);
        $this->assertStringContainsString('ConsultationBrowserFixtureService', $seeder);
        $this->assertStringContainsString('consultationWorkspaceFixture', $spec);
        $this->assertStringNotContainsString('test.describe.skip', $spec);
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

    private function requestWithUser(string $key): Request
    {
        $request = Request::create('/phase5-consultation', 'POST', [], [], [], [
            'HTTP_IDEMPOTENCY_KEY' => $key,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setUserResolver(fn () => $this->doctor);

        return $request;
    }

    private function prescriptionPayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'notes' => 'Phase 5 fixture',
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
            'clinical_info' => 'Phase 5 fixture',
        ];
    }

    private function procedurePayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'department_id' => $this->procedureDepartment->id,
            'service_catalog_id' => $this->procedureService->id,
            'priority' => 'routine',
            'indication' => 'Phase 5 fixture',
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
