<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PrescriptionStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ResultType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\Complaint;
use App\Models\ConsultationTask;
use App\Models\Department;
use App\Models\Investigation;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\QueueEntry;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitDepartmentHistory;
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
            'prescriptions.create',
            'procedure.request',
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

    public function test_investigation_request_handoff_pauses_session_and_queues_patient_at_department(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $lab = Department::factory()->create([
            'name' => 'Laboratory',
            'type' => DepartmentType::INVESTIGATION->value,
            'result_type' => ResultType::PARAMETERS->value,
        ]);
        $fbc = $this->makeInvestigationService($lab, 'Full Blood Count', 'FBC');

        QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => $this->department->id,
            'queue_number' => 1,
            'priority' => $visit->priority->value,
            'status' => 'serving',
        ]);

        $labRequest = LabRequest::create([
            'request_number' => LabRequest::generateRequestNumber(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $this->doctor->id,
            'department_id' => $this->department->id,
            'target_department_id' => $lab->id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
            'clinical_info' => 'Rule out infection',
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
        $labRequest->items()->create([
            'service_id' => $fbc->id,
            'name' => $fbc->name,
            'status' => 'pending',
        ]);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.investigation-departments.send-to-department', [$visit, $lab]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('visit_status', VisitStatus::WAITING_INVESTIGATION->value);

        $this->assertSame(VisitConsultationRoute::STATUS_PAUSED, $route->fresh()->status);
        $this->assertSame(VisitStatus::WAITING_INVESTIGATION, $visit->fresh()->status);
        $this->assertSame($lab->id, $visit->fresh()->current_department_id);

        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => $this->department->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => $lab->id,
            'status' => 'waiting',
        ]);
        $this->assertDatabaseHas('visit_department_history', [
            'visit_id' => $visit->id,
            'department_id' => $lab->id,
            'type' => VisitDepartmentHistory::TYPE_INVESTIGATION,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
        ]);
        $this->assertDatabaseHas('visit_consultation_route_logs', [
            'visit_consultation_route_id' => $route->id,
            'from_status' => VisitConsultationRoute::STATUS_ACTIVE,
            'to_status' => VisitConsultationRoute::STATUS_PAUSED,
            'action' => 'sent_to_investigation_department',
        ]);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => VisitStatus::CONSULTING->value,
            'to_status' => VisitStatus::WAITING_INVESTIGATION->value,
        ]);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.activate', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertSame($this->department->id, $visit->fresh()->current_department_id);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => VisitStatus::WAITING_INVESTIGATION->value,
            'to_status' => VisitStatus::CONSULTING->value,
        ]);
    }

    public function test_prescription_and_procedure_department_handoffs_pause_session_and_queue_patient(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $pharmacy = Department::factory()->create([
            'name' => 'Main Pharmacy',
            'type' => DepartmentType::PHARMACY->value,
            'status' => 'active',
        ]);
        $theatre = Department::factory()->create([
            'name' => 'Theatre',
            'type' => DepartmentType::THEATRE->value,
            'status' => 'active',
        ]);
        $procedureService = ServiceCatalog::create([
            'name' => 'Minor Procedure',
            'code' => 'MIN-PROC',
            'department_id' => $theatre->id,
            'category' => ServiceType::PROCEDURE->value,
            'price' => 200,
            'is_active' => true,
            'is_billable' => true,
        ]);

        QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => $this->department->id,
            'queue_number' => 1,
            'priority' => $visit->priority->value,
            'status' => 'serving',
        ]);

        $prescription = Prescription::create([
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
            'prescription_number' => Prescription::generatePrescriptionNumber(),
            'status' => PrescriptionStatus::PENDING,
        ]);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.prescription-departments.send-to-department', [$visit, $pharmacy]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('visit_status', VisitStatus::PHARMACY->value);

        $this->assertSame(VisitConsultationRoute::STATUS_PAUSED, $route->fresh()->status);
        $this->assertSame(VisitStatus::PHARMACY, $visit->fresh()->status);
        $this->assertSame($pharmacy->id, $visit->fresh()->current_department_id);
        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => $pharmacy->id,
            'status' => 'waiting',
        ]);
        $this->assertDatabaseHas('visit_department_history', [
            'visit_id' => $visit->id,
            'department_id' => $pharmacy->id,
            'type' => VisitDepartmentHistory::TYPE_PHARMACY,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
        ]);
        $this->assertDatabaseHas('visit_consultation_route_logs', [
            'visit_consultation_route_id' => $route->id,
            'action' => 'sent_to_pharmacy_department',
        ]);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.activate', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $procedureRequest = ProcedureRequest::create([
            'request_number' => ProcedureRequest::generateNumber(),
            'visit_id' => $visit->id,
            'medical_record_id' => $route->medicalRecord->id,
            'consultation_route_id' => $route->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $this->doctor->id,
            'department_id' => $theatre->id,
            'service_catalog_id' => $procedureService->id,
            'priority' => 'routine',
            'indication' => 'Needs wound closure',
            'status' => ProcedureStatus::REQUESTED,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.procedure-departments.send-to-department', [$visit, $theatre]), [
                'consultation_route_id' => $route->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('visit_status', VisitStatus::ACTIVE->value);

        $this->assertSame(VisitConsultationRoute::STATUS_PAUSED, $route->fresh()->status);
        $this->assertSame(VisitStatus::ACTIVE, $visit->fresh()->status);
        $this->assertSame($theatre->id, $visit->fresh()->current_department_id);
        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => $theatre->id,
            'status' => 'waiting',
        ]);
        $this->assertDatabaseHas('visit_department_history', [
            'visit_id' => $visit->id,
            'department_id' => $theatre->id,
            'type' => VisitDepartmentHistory::TYPE_PROCEDURE,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
        ]);
        $this->assertDatabaseHas('visit_consultation_route_logs', [
            'visit_consultation_route_id' => $route->id,
            'action' => 'sent_to_procedure_department',
        ]);

        $this->assertNotNull($prescription->fresh());
        $this->assertNotNull($procedureRequest->fresh());
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

    public function test_readiness_fragment_reflects_new_clinical_entries(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $record = $route->medicalRecord;

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.readiness-fragment', [
                'visit' => $visit,
                'consultation_route_id' => $route->id,
            ]))
            ->assertOk()
            ->assertSee(__('consultation.completion.not_ready'));

        $base = [
            'consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
        ];
        $record->complaints()->create($base + ['description' => 'Persistent headache']);
        $record->physicalExaminations()->create($base + ['findings' => 'Normal neurological examination']);
        $record->diagnoses()->create($base + ['description' => 'Tension headache', 'type' => 'provisional', 'is_primary' => true]);
        $record->treatments()->create($base + ['type' => 'advice', 'description' => 'Hydration and review plan']);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.readiness-fragment', [
                'visit' => $visit,
                'consultation_route_id' => $route->id,
            ]))
            ->assertOk()
            ->assertSee(__('consultation.completion.ready'))
            ->assertSee('ti-circle-check text-success', false);
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
