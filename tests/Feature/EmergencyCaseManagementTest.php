<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Services\EmergencyMedicationService;
use App\Services\EmergencyTriageService;
use App\Services\VisitPreviewService;
use Database\Seeders\MedicationFrequencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmergencyCaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(MedicationFrequencySeeder::class);

        $this->department = Department::factory()->create([
            'name' => 'Emergency',
            'code' => 'ER',
            'type' => 'treatment',
        ]);

        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
        ]);

        $role = Role::findOrCreate('Emergency Test User', 'web');
        foreach ([
            'emergency.board.view',
            'emergency.case.create',
            'emergency.case.view',
            'emergency.case.update',
            'emergency.triage.perform',
            'emergency.bay.assign',
            'emergency.notes.create',
            'emergency.vitals.record',
            'emergency.medication.administer',
            'emergency.reports.view',
            'emergency.settings.manage',
            'emergency.medication_board.view',
            'emergency.mar_chart.view',
            'medication_administration.view',
            'medication_administration.administer',
            'visits.view',
            'visits.preview',
            'patients.merge.confirm_identity',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);
    }

    public function test_user_can_create_emergency_case_for_existing_patient(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.emergency.cases.store'), [
            'patient_id' => $this->patient->id,
            'arrival_mode' => 'AMBULANCE',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'Road traffic accident',
            'initial_condition' => 'Unstable',
        ]);

        $case = EmergencyCase::first();

        $response->assertRedirect(route('admin.emergency.cases.show', $case));
        $this->assertNotNull($case);
        $this->assertSame($this->patient->id, $case->patient_id);
        $this->assertStringStartsWith('ER-', $case->emergency_number);
        $this->assertDatabaseHas('visits', [
            'id' => $case->visit_id,
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'status' => VisitStatus::EMERGENCY->value,
        ]);
        $this->assertDatabaseHas('emergency_case_logs', [
            'emergency_case_id' => $case->id,
            'action' => 'ARRIVAL',
        ]);
    }

    public function test_unknown_patient_emergency_case_creates_temporary_patient(): void
    {
        $this->actingAs($this->user)->post(route('admin.emergency.cases.store'), [
            'temporary_display_name' => 'Unknown Male Adult',
            'temporary_gender' => 'male',
            'estimated_age' => 40,
            'temporary_reason' => 'Unconscious patient without ID',
            'arrival_mode' => 'UNKNOWN',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'Unconscious',
        ])->assertRedirect();

        $case = EmergencyCase::with('patient')->first();

        $this->assertTrue($case->patient->is_temporary);
        $this->assertStringStartsWith('TEMP-ER-', $case->patient->patient_number);
    }

    public function test_emergency_board_shows_active_cases(): void
    {
        $case = $this->makeCase();

        $response = $this->actingAs($this->user)->get(route('admin.emergency.board'));

        $response->assertOk();
        $response->assertSee($case->emergency_number);
        $response->assertSee('Emergency Board');
    }

    public function test_emergency_case_detail_loads_control_sheet(): void
    {
        $case = $this->makeCase();

        $response = $this->actingAs($this->user)->get(route('admin.emergency.cases.show', $case));

        $response->assertOk();
        $response->assertSee($case->emergency_number);
        $response->assertSee('Emergency Control Sheet');
    }

    public function test_temporary_emergency_identity_confirmation_is_modal_based(): void
    {
        $temporaryPatient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'patient_number' => 'TEMP-ER-TEST-0001',
            'first_name' => 'Unknown',
            'last_name' => 'Emergency',
            'is_temporary' => true,
            'temporary_reason' => 'Unidentified arrival',
            'status' => 'active',
        ]);
        $case = $this->makeCase(['patient_id' => $temporaryPatient->id]);
        $case->visit()->update(['patient_id' => $temporaryPatient->id]);

        $response = $this->actingAs($this->user)->get(route('admin.emergency.cases.show', $case));

        $response->assertOk()
            ->assertSee('confirmEmergencyIdentityModal', false)
            ->assertSee('Register Patient')
            ->assertDontSee('card border-warning mb-3', false);
    }

    public function test_emergency_bays_and_reports_pages_load(): void
    {
        $this->makeCase();

        $this->actingAs($this->user)
            ->get(route('admin.emergency.bays.index'))
            ->assertOk()
            ->assertSee('Emergency Bays');

        $this->actingAs($this->user)
            ->get(route('admin.emergency.reports.index'))
            ->assertOk()
            ->assertSee('Emergency Reports');
    }

    public function test_triage_records_vitals_and_creates_monitoring_task_for_red_case(): void
    {
        $case = $this->makeCase();

        app(EmergencyTriageService::class)->record($case, [
            'triage_category' => 'RED',
            'triage_score' => 95,
            'triage_notes' => 'Immediate resuscitation',
            'heart_rate' => 140,
            'spo2' => 88,
        ], $this->user);

        $this->assertDatabaseHas('emergency_cases', [
            'id' => $case->id,
            'triage_category' => 'RED',
            'emergency_status' => EmergencyCase::STATUS_TRIAGED,
        ]);
        $this->assertDatabaseHas('vitals', [
            'emergency_case_id' => $case->id,
            'patient_id' => $case->patient_id,
            'monitoring_context' => 'EMERGENCY_TRIAGE',
        ]);
        $this->assertDatabaseHas('clinical_tasks', [
            'emergency_case_id' => $case->id,
            'task_type' => ClinicalTask::TYPE_VITALS_MONITORING,
        ]);
    }

    public function test_bay_assignment_marks_bay_occupied_and_blocks_double_assignment(): void
    {
        $case = $this->makeCase();
        $secondCase = $this->makeCase();
        $bay = EmergencyBay::create([
            'name' => 'Resus 1',
            'code' => 'RES-1',
            'bay_type' => 'RESUSCITATION',
            'status' => EmergencyBay::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)->post(route('admin.emergency.bay.assign', $case), [
            'emergency_bay_id' => $bay->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('emergency_bays', [
            'id' => $bay->id,
            'status' => EmergencyBay::STATUS_OCCUPIED,
        ]);

        $this->actingAs($this->user)->post(route('admin.emergency.bay.assign', $secondCase), [
            'emergency_bay_id' => $bay->id,
        ])->assertSessionHasErrors('emergency_bay_id');
    }

    public function test_stat_emergency_medication_creates_order_schedule_and_task(): void
    {
        $case = $this->makeCase();
        $product = Product::create([
            'name' => 'Adrenaline',
            'code' => 'ADR-STAT',
            'product_type' => ProductType::DRUG,
            'unit' => 'ampoule',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $order = app(EmergencyMedicationService::class)->order($case, [
            'product_id' => $product->id,
            'dose' => '1mg',
            'route' => 'IV',
            'frequency_code' => 'STAT',
            'quantity_ordered' => 1,
        ], $this->user);

        $this->assertSame($case->id, $order->emergency_case_id);
        $this->assertDatabaseHas('medication_administration_schedules', [
            'medication_order_id' => $order->id,
            'emergency_case_id' => $case->id,
        ]);
        $this->assertDatabaseHas('clinical_tasks', [
            'emergency_case_id' => $case->id,
            'task_type' => ClinicalTask::TYPE_MEDICATION_ADMINISTRATION,
        ]);
    }

    public function test_visit_preview_includes_emergency_timeline(): void
    {
        $case = $this->makeCase([
            'chief_complaint' => 'Severe breathlessness',
        ]);
        $case->notes()->create([
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'note_type' => 'DOCTOR_ASSESSMENT',
            'content' => 'Patient reviewed in emergency.',
            'created_by' => $this->user->id,
        ]);

        $preview = app(VisitPreviewService::class)->build($case->visit->fresh());
        $titles = collect($preview['timeline'])->pluck('title');

        $this->assertTrue($titles->contains('Emergency Case Opened'));
        $this->assertTrue($titles->contains('Emergency Doctor Assessment'));
        $this->assertSame($case->emergency_number, $preview['summary']['emergency_number']);
    }

    private function makeCase(array $overrides = []): EmergencyCase
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY,
            'status' => VisitStatus::EMERGENCY,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        return EmergencyCase::create(array_merge([
            'emergency_number' => 'ER-2026-'.str_pad((string) (EmergencyCase::count() + 1), 6, '0', STR_PAD_LEFT),
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'arrival_mode' => 'WALK_IN',
            'arrival_time' => now(),
            'chief_complaint' => 'Emergency complaint',
            'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
