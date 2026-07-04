<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\ResultType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\Complaint;
use App\Models\ConsultationTask;
use App\Models\Department;
use App\Models\Investigation;
use App\Models\LabRequestItem;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationWorkspaceStabilisationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Department $department;

    private ServiceCatalog $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('Doctor', 'web');
        foreach ([
            'consultations.view',
            'consultations.create',
            'complaints.view',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);

        $this->service = ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'GEN-CON',
            'department_id' => $this->department->id,
            'category' => ServiceType::CONSULTATION->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->doctor = User::factory()->create([
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'department_id' => $this->department->id,
        ]);
        $this->doctor->assignRole($role);
    }

    public function test_hopc_hydrates_duration_and_severity_from_linked_complaint(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $complaint = Complaint::create([
            'medical_record_id' => $route->medicalRecord->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'consultation_route_id' => $route->id,
            'description' => 'Persistent headache',
            'duration' => '3',
            'duration_unit' => 'days',
            'severity' => 'severe',
            'created_by' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.hopc.store', $visit), [
                'consultation_route_id' => $route->id,
                'complaint_id' => $complaint->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('history_of_presenting_complaints', [
            'medical_record_id' => $route->medicalRecord->id,
            'complaint_id' => $complaint->id,
            'content' => 'Persistent headache',
            'duration' => '3 days',
            'severity' => 'severe',
        ]);
    }

    public function test_investigation_accepts_multiple_selected_services(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $lab = Department::factory()->create([
            'name' => 'Laboratory',
            'type' => DepartmentType::INVESTIGATION->value,
            'result_type' => ResultType::PARAMETERS->value,
        ]);
        $fbc = $this->makeInvestigationService($lab, 'Full Blood Count', 'FBC');
        $rbs = $this->makeInvestigationService($lab, 'Random Blood Sugar', 'RBS');

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.investigations.store', $visit), [
                'consultation_route_id' => $route->id,
                'department_id' => $lab->id,
                'service_ids' => [$fbc->id, $rbs->id],
                'urgency' => 'routine',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2);

        $this->assertSame(2, Investigation::where('medical_record_id', $route->medicalRecord->id)->count());
        $this->assertSame(2, LabRequestItem::whereIn('service_id', [$fbc->id, $rbs->id])->count());
    }

    public function test_task_frequency_expands_to_finite_rows_and_idempotency_blocks_duplicates(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $payload = [
            '_idempotency_key' => 'task-frequency-bd',
            'consultation_route_id' => $route->id,
            'title' => 'Review vitals',
            'frequency' => 'BD',
            'priority' => 'medium',
            'start_at' => '2026-07-04 08:00:00',
        ];

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.tasks.store', $visit), $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.tasks.store', $visit), $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $tasks = ConsultationTask::where('medical_record_id', $route->medicalRecord->id)
            ->where('title', 'Review vitals')
            ->orderBy('scheduled_at')
            ->get();

        $this->assertCount(2, $tasks);
        $this->assertSame(['BD', 'BD'], $tasks->pluck('frequency')->all());
        $this->assertSame('2026-07-04 08:00:00', $tasks[0]->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-04 20:00:00', $tasks[1]->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_final_note_is_saved_separately_from_generated_summary(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $this->actingAs($this->doctor)
            ->patchJson(route('admin.consultations.final-note.update', $visit), [
                'consultation_route_id' => $route->id,
                'final_note' => 'Final clinician note goes here.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('medical_records', [
            'id' => $route->medicalRecord->id,
            'final_note' => 'Final clinician note goes here.',
            'final_note_updated_by' => $this->doctor->id,
        ]);
    }

    private function makeConsultingVisit(): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        return [$visit, $route->fresh('medicalRecord')];
    }

    private function makeInvestigationService(Department $department, string $name, string $code): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => $code,
            'department_id' => $department->id,
            'category' => ServiceType::INVESTIGATION->value,
            'price' => 25,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }
}
