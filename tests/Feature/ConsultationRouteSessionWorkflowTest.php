<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationRouteSessionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctor;

    private User $otherDoctor;

    private Department $generalDepartment;

    private Department $dentalDepartment;

    private ServiceCatalog $generalService;

    private ServiceCatalog $dentalService;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('Admin', 'web');
        foreach ([
            'visits.view',
            'visits.preview',
            'consultations.view',
            'consultations.create',
            'procedure.request',
        ] as $permission) {
            $adminRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $doctorRole = Role::findOrCreate('Doctor', 'web');

        $this->generalDepartment = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $generalSpecialty = Specialty::create([
            'name' => 'General Medicine',
            'department_id' => $this->generalDepartment->id,
            'is_active' => true,
        ]);

        $this->dentalDepartment = Department::factory()->create([
            'name' => 'Dental',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $dentalSpecialty = Specialty::create([
            'name' => 'Dental Surgery',
            'department_id' => $this->dentalDepartment->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['department_id' => $this->generalDepartment->id]);
        $this->admin->assignRole($adminRole);

        $this->doctor = User::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'department_id' => $this->dentalDepartment->id,
        ]);
        $this->doctor->assignRole($doctorRole);
        $this->doctor->specialties()->attach($dentalSpecialty->id);

        $this->otherDoctor = User::factory()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'department_id' => $this->generalDepartment->id,
        ]);
        $this->otherDoctor->assignRole($doctorRole);
        $this->otherDoctor->specialties()->attach($generalSpecialty->id);

        $this->generalService = $this->makeService($this->generalDepartment, 'General Consultation');
        $this->dentalService = $this->makeService($this->dentalDepartment, 'Dental Consultation');
    }

    public function test_consultation_page_shows_current_session_and_all_routes(): void
    {
        [$visit, $activeRoute] = $this->makeConsultingVisit();

        $completedRoute = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->dentalDepartment->id,
            'service_id' => $this->dentalService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'routed_by' => $this->admin->id,
            'completed_by' => $this->doctor->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultations.routes.show', [$visit, $activeRoute]))
            ->assertOk()
            ->assertSee('Current Session')
            ->assertSee('Consultation Sessions for This Visit')
            ->assertSee($this->generalService->name)
            ->assertSee($this->dentalService->name)
            // Phase 9: route/session status now renders via <x-status-badge>, which shows
            // the human label ("Completed") instead of the raw enum string ("COMPLETED").
            ->assertSee(Str::of(VisitConsultationRoute::STATUS_COMPLETED)->replace('_', ' ')->title()->value());

        $this->assertDatabaseHas('medical_records', [
            'visit_id' => $visit->id,
            'consultation_route_id' => $activeRoute->id,
            'department_id' => $activeRoute->department_id,
            'service_id' => $activeRoute->service_id,
        ]);

        $this->assertDatabaseHas('visit_consultation_routes', [
            'id' => $completedRoute->id,
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
        ]);
    }

    public function test_consultation_script_globals_are_reentrant(): void
    {
        $view = file_get_contents(resource_path('views/consultations/show.blade.php'));

        $this->assertStringContainsString('window.consultationI18n =', $view);
        $this->assertStringNotContainsString('const consultationI18n', $view);
    }

    public function test_consultation_can_request_procedure_from_theatre_department(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $theatreDepartment = Department::factory()->create([
            'name' => 'Main Theatre',
            'type' => DepartmentType::THEATRE->value,
            'status' => 'active',
        ]);
        $procedureService = ServiceCatalog::create([
            'name' => 'Appendectomy',
            'code' => 'PROC'.random_int(1000, 9999),
            'category' => ServiceType::PROCEDURE->value,
            'department_id' => $theatreDepartment->id,
            'department_type' => DepartmentType::THEATRE->value,
            'price' => 250,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.theatre.department-services', [
                'department' => $theatreDepartment,
                'visit_id' => $visit->id,
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $procedureService->id,
                'name' => 'Appendectomy',
            ]);

        $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('admin.consultations.procedures.store', $visit), [
                'consultation_route_id' => $route->id,
                'department_id' => $theatreDepartment->id,
                'service_catalog_id' => $procedureService->id,
                'priority' => 'routine',
                'indication' => 'Needs operative review',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('procedure_requests', [
            'visit_id' => $visit->id,
            'department_id' => $theatreDepartment->id,
            'service_catalog_id' => $procedureService->id,
            'consultation_route_id' => $route->id,
            'status' => \App\Enums\ProcedureStatus::REQUESTED->value,
        ]);
        $this->assertSame(1, ProcedureRequest::where('visit_id', $visit->id)->count());
    }

    public function test_activating_another_session_keeps_visit_consulting_and_pauses_previous_active_route(): void
    {
        [$visit, $activeRoute] = $this->makeConsultingVisit();

        $pendingRoute = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->dentalDepartment->id,
            'service_id' => $this->dentalService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.activate', [$visit, $pendingRoute]))
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $pendingRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_PAUSED, $activeRoute->fresh()->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $pendingRoute->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertDatabaseHas('medical_records', [
            'consultation_route_id' => $pendingRoute->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Session start is on the patient activity timeline.
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'CONSULTATION',
            'event' => 'SESSION_STARTED',
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_service_based_routes_migrate_to_one_department_route_with_linked_services(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->dentalDepartment->id,
        ]);

        $secondDentalService = $this->makeService($this->dentalDepartment, 'Tooth Extraction Review');

        $firstRoute = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->dentalDepartment->id,
            'service_id' => $this->dentalService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);
        $secondRoute = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->dentalDepartment->id,
            'service_id' => $secondDentalService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->admin->id,
        ]);

        $this->artisan('consultation:migrate-service-routes-to-departments')
            ->assertExitCode(0);

        $this->assertSame(1, VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->dentalDepartment->id)
            ->count());

        $survivingRoute = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->dentalDepartment->id)
            ->firstOrFail();

        $this->assertContains($survivingRoute->id, [$firstRoute->id, $secondRoute->id]);
        $this->assertEqualsCanonicalizing(
            [$this->dentalService->id, $secondDentalService->id],
            VisitConsultationRouteService::where('visit_consultation_route_id', $survivingRoute->id)
                ->pluck('service_id')
                ->all()
        );
    }

    public function test_visit_page_transition_section_shows_routes_and_queue_form(): void
    {
        [$visit] = $this->makeConsultingVisit();

        $this->actingAs($this->admin)
            ->get(route('admin.visits.show', $visit))
            ->assertOk()
            ->assertSee('Current Consultation Routing')
            ->assertSee('Available Consultation Department Sessions')
            ->assertSee($this->generalService->name)
            ->assertSee('Consultation Department')
            // The 'Services to add' label was removed in the localisation refactor;
            // assert the service-select field itself still renders instead.
            ->assertSee('visitRouteServiceSelect');
    }

    public function test_completing_current_session_does_not_close_visit_when_other_routes_remain(): void
    {
        [$visit, $activeRoute] = $this->makeConsultingVisit();

        VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->dentalDepartment->id,
            'service_id' => $this->dentalService->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.complete', [$visit, $activeRoute]), [
                'notes' => 'General consultation complete',
            ])
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $activeRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $activeRoute->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertDatabaseHas('visit_consultation_route_logs', [
            'visit_consultation_route_id' => $activeRoute->id,
            'action' => 'completed',
            'to_status' => VisitConsultationRoute::STATUS_COMPLETED,
        ]);
    }

    public function test_queueing_another_consultation_session_bills_once_and_prevents_duplicate_billing(): void
    {
        [$visit] = $this->makeConsultingVisit();

        $payload = [
            'department_id' => $this->dentalDepartment->id,
            'service_ids' => [$this->dentalService->id],
            'doctor_id' => $this->doctor->id,
            'notes' => 'Dental review requested',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.store', $visit), $payload)
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.store', $visit), $payload)
            ->assertRedirect();

        $this->assertSame(1, VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->dentalDepartment->id)
            ->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE])
            ->count());
        $route = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $this->dentalDepartment->id)
            ->firstOrFail();

        $this->assertSame(1, VisitConsultationRouteService::where('visit_consultation_route_id', $route->id)
            ->where('service_id', $this->dentalService->id)
            ->count());

        $this->assertSame(1, InvoiceItem::where('visit_id', $visit->id)
            ->where('service_catalog_id', $this->dentalService->id)
            ->count());
    }

    public function test_doctor_not_linked_to_department_cannot_be_queued_for_session(): void
    {
        [$visit] = $this->makeConsultingVisit();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.routes.store', $visit), [
                'department_id' => $this->dentalDepartment->id,
                'service_ids' => [$this->dentalService->id],
                'doctor_id' => $this->otherDoctor->id,
            ])
            ->assertSessionHas('error', 'Selected doctor is not linked to this consultation department.');
    }

    public function test_unauthorized_user_cannot_activate_or_switch_sessions(): void
    {
        [$visit, $activeRoute] = $this->makeConsultingVisit();

        $viewerRole = Role::findOrCreate('Consultation Viewer', 'web');
        $viewerRole->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        /** @var User $viewer */
        $viewer = User::factory()->create();
        $viewer->assignRole($viewerRole)->fresh();

        $this->actingAs($viewer)
            ->post(route('admin.consultations.routes.activate', [$visit, $activeRoute]))
            ->assertForbidden();
    }

    public function test_visit_preview_includes_route_department_and_service_context(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $record = app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->admin);
        $record->complaints()->create([
            'description' => 'Dental pain on chewing',
            'severity' => 'moderate',
        ]);
        $record->diagnoses()->create([
            'description' => 'Dental caries',
            'type' => 'provisional',
            'is_primary' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.visits.preview', $visit))
            ->assertOk()
            ->assertSee($this->generalDepartment->name)
            ->assertSee($this->generalService->name)
            ->assertSee('Dental pain on chewing')
            ->assertSee('Dental caries');
    }

    private function makeConsultingVisit(): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->admin->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->admin->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->generalDepartment->id,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->generalDepartment->id,
            'service_id' => $this->generalService->id,
            'doctor_id' => $this->otherDoctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->admin->id,
            'started_by' => $this->otherDoctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);
        VisitConsultationRouteService::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'service_id' => $this->generalService->id,
        ]);

        return [$visit, $route];
    }

    private function makeService(Department $department, string $name): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => 'SVC'.random_int(1000, 9999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 50,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }
}
