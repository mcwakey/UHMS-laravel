<?php

namespace Tests\Feature;

use App\Models\Bed;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\Triage;
use App\Models\User;
use App\Models\Ward;
use App\Services\EmergencyCaseService;
use Database\Seeders\MedicationFrequencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Emergency case view fixes: separated ward/bed vs bay, multi-select
 * investigations/procedures, triage reflecting on the visit, monitoring task
 * interaction, and a clean page render.
 */
class EmergencyCaseViewEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private EmergencyCase $case;
    private Department $procedureDept;
    private array $procedureServiceIds;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(MedicationFrequencySeeder::class);

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('ER Enhancements', 'web');
        foreach ([
            'emergency.case.view', 'emergency.case.update', 'emergency.triage.perform',
            'emergency.bay.assign', 'emergency.investigation.request', 'emergency.procedure.request',
            'visits.view', 'invoices.create',
        ] as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $this->user->assignRole($role);
        $this->actingAs($this->user);

        $emergencyDept = Department::factory()->create(['name' => 'Emergency / Casualty', 'code' => 'EMR', 'type' => 'consultation']);
        ServiceCatalog::create(['name' => 'Emergency Consultation', 'code' => 'EMR-CON', 'category' => 'consultation', 'price' => 250, 'is_active' => true, 'is_billable' => true, 'department_id' => $emergencyDept->id]);

        $this->procedureDept = Department::factory()->create(['name' => 'Minor Theatre', 'code' => 'PRC', 'type' => 'procedure']);
        $this->procedureServiceIds = [
            ServiceCatalog::create(['name' => 'Wound Suturing', 'code' => 'P1', 'category' => 'procedure', 'price' => 120, 'is_active' => true, 'is_billable' => true, 'department_id' => $this->procedureDept->id])->id,
            ServiceCatalog::create(['name' => 'Plaster Cast', 'code' => 'P2', 'category' => 'procedure', 'price' => 200, 'is_active' => true, 'is_billable' => true, 'department_id' => $this->procedureDept->id])->id,
        ];

        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->case = app(EmergencyCaseService::class)->create([
            'patient_id' => $patient->id,
            'arrival_mode' => 'AMBULANCE',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'RTA',
        ], $this->user);
    }

    public function test_emergency_show_page_renders_with_new_controls(): void
    {
        $response = $this->get(route('admin.emergency.cases.show', $this->case))->assertOk();

        // Ward/Bed separated from Bay; multi-select investigations/procedures;
        // MAR select2; task interaction; billing price (not 0).
        $response->assertSee('Assign Bay');
        $response->assertSee('Link Ward', false); // '/' is JSON-escaped in the page envelope
        $response->assertSee('Add Monitoring Task');
        $response->assertSee('Care Team', false);
        $response->assertSee('data-er-select2', false);
        $response->assertSee('250.00'); // emergency consultation billed price renders
    }

    public function test_emergency_triage_reflects_on_visit_triage_assessment(): void
    {
        $this->post(route('admin.emergency.triage.store', $this->case), [
            'final_triage_category' => 'YELLOW',
            'triage_category' => 'YELLOW',
            'heart_rate' => 88,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'temperature' => 37.0,
            'spo2' => 98,
            'respiratory_rate' => 18,
            'triage_notes' => 'Stable',
            'triage_override_reason' => 'Clinical judgement on presentation',
        ])->assertRedirect();

        $triage = Triage::where('visit_id', $this->case->visit_id)->first();
        $this->assertNotNull($triage, 'Emergency triage must create the visit Triage record.');
        $this->assertSame(88, (int) $triage->heart_rate);
        $this->assertSame(98, (int) $triage->spo2);
        // YELLOW maps to URGENT on the visit triage scale.
        $this->assertSame('urgent', $triage->triage_score instanceof \BackedEnum ? $triage->triage_score->value : $triage->triage_score);
        $this->assertSame('urgent', $this->case->visit->fresh()->triage_score instanceof \BackedEnum ? $this->case->visit->fresh()->triage_score->value : $this->case->visit->fresh()->triage_score);
    }

    public function test_multiple_investigations_create_one_request_with_each_item(): void
    {
        $labDept = Department::factory()->create(['name' => 'Laboratory', 'code' => 'LAB', 'type' => 'investigation']);
        $serviceIds = [
            ServiceCatalog::create(['name' => 'Full Blood Count', 'code' => 'FBC', 'category' => 'investigation', 'price' => 40, 'is_active' => true, 'is_billable' => true, 'department_id' => $labDept->id])->id,
            ServiceCatalog::create(['name' => 'Malaria RDT', 'code' => 'MRDT', 'category' => 'investigation', 'price' => 25, 'is_active' => true, 'is_billable' => true, 'department_id' => $labDept->id])->id,
        ];

        $this->post(route('admin.emergency.investigations.store', $this->case), [
            'target_department_id' => $labDept->id,
            'service_id' => $serviceIds, // multi
            'urgency' => 'emergency',
            'clinical_info' => 'Febrile RTA',
        ])->assertRedirect();

        $requests = \App\Models\LabRequest::where('emergency_case_id', $this->case->id)->withCount('items')->get();
        $this->assertCount(1, $requests, 'Multiple selected tests should be one lab request.');
        $this->assertSame(2, (int) $requests->first()->items_count);
    }

    public function test_multiple_procedures_create_one_request_each(): void
    {
        $this->post(route('admin.emergency.procedures.store', $this->case), [
            'department_id' => $this->procedureDept->id,
            'service_catalog_id' => $this->procedureServiceIds, // multi
            'priority' => 'emergency',
            'indication' => 'Laceration + fracture',
        ])->assertRedirect();

        $this->assertSame(2, ProcedureRequest::where('emergency_case_id', $this->case->id)->count());
    }

    public function test_monitoring_task_can_be_added_and_completed(): void
    {
        $this->post(route('admin.emergency.tasks.store', $this->case), [
            'title' => 'Reposition patient',
            'priority' => 'high',
        ])->assertRedirect();

        $task = ClinicalTask::where('emergency_case_id', $this->case->id)
            ->where('title', 'Reposition patient')->first();
        $this->assertNotNull($task);
        $this->assertSame(ClinicalTask::STATUS_SCHEDULED, $task->status);

        $this->patch(route('admin.emergency.tasks.complete', [$this->case, $task]))->assertRedirect();
        $this->assertSame(ClinicalTask::STATUS_COMPLETED, $task->fresh()->status);
        $this->assertSame($this->user->id, (int) $task->fresh()->completed_by);
    }

    public function test_ward_bed_can_be_assigned_separately_from_bay(): void
    {
        $bay = EmergencyBay::create([
            'name' => 'Bay 1', 'code' => 'BAY1', 'bay_type' => 'TREATMENT',
            'status' => EmergencyBay::STATUS_AVAILABLE, 'is_active' => true,
        ]);
        $ward = Ward::create(['name' => 'Ward A', 'code' => 'WA', 'is_active' => true]);
        $bed = Bed::create(['ward_id' => $ward->id, 'bed_number' => 'B1', 'bed_type' => 'standard', 'status' => 'available', 'daily_rate' => 50]);

        // Bay first (its own form/action).
        $this->post(route('admin.emergency.bay.assign', $this->case), [
            'emergency_bay_id' => $bay->id,
        ])->assertRedirect();

        // Then ward/bed via the separate action.
        $this->post(route('admin.emergency.bay.assign-ward-bed', $this->case), [
            'ward_id' => $ward->id,
            'bed_id' => $bed->id,
        ])->assertRedirect();

        $assignment = $this->case->fresh()->activeBayAssignment;
        $this->assertNotNull($assignment);
        $this->assertSame($ward->id, (int) $assignment->ward_id);
        $this->assertSame($bed->id, (int) $assignment->bed_id);
        $this->assertSame($bay->id, (int) $assignment->emergency_bay_id);
    }

    public function test_ward_bed_assignment_requires_a_bay_first(): void
    {
        $ward = Ward::create(['name' => 'Ward B', 'code' => 'WB', 'is_active' => true]);

        $this->from(route('admin.emergency.cases.show', $this->case))
            ->post(route('admin.emergency.bay.assign-ward-bed', $this->case), ['ward_id' => $ward->id])
            ->assertSessionHasErrors('bed_id');

        $this->assertNull($this->case->fresh()->activeBayAssignment);
    }
}
