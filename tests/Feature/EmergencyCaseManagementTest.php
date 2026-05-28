<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Services\EmergencyMedicationService;
use App\Services\EmergencySessionService;
use App\Services\EmergencyTriageService;
use App\Services\VisitPreviewService;
use Database\Seeders\MedicationFrequencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

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
            'emergency.investigation.request',
            'emergency.procedure.request',
            'emergency.consumables.use',
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
        $this->assertDatabaseHas('emergency_sessions', [
            'emergency_case_id' => $case->id,
            'visit_id' => $case->visit_id,
            'patient_id' => $this->patient->id,
            'status' => 'ACTIVE',
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
            'auto_triage_category' => 'RED',
            'final_triage_category' => 'RED',
            'emergency_status' => EmergencyCase::STATUS_TRIAGED,
        ]);
        $session = $case->fresh('activeEmergencySession')->activeEmergencySession;
        $this->assertNotNull($session);
        $this->assertDatabaseHas('vitals', [
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'patient_id' => $case->patient_id,
            'monitoring_context' => 'EMERGENCY_TRIAGE',
        ]);
        $this->assertDatabaseHas('clinical_tasks', [
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'task_type' => ClinicalTask::TYPE_VITALS_MONITORING,
        ]);
        $this->assertDatabaseHas('emergency_session_contributors', [
            'emergency_session_id' => $session->id,
            'user_id' => $this->user->id,
            'role' => 'Triage',
        ]);

        app(EmergencyTriageService::class)->record($case->fresh(), [
            'triage_category' => 'RED',
            'triage_score' => 95,
            'triage_notes' => 'Repeat assessment',
            'heart_rate' => 138,
            'spo2' => 89,
        ], $this->user);

        $this->assertSame(1, ClinicalTask::where('emergency_case_id', $case->id)
            ->where('task_type', ClinicalTask::TYPE_VITALS_MONITORING)
            ->count());
    }

    public function test_triage_override_requires_reason(): void
    {
        $case = $this->makeCase();

        $this->expectException(ValidationException::class);

        app(EmergencyTriageService::class)->record($case, [
            'final_triage_category' => 'RED',
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 78,
            'respiratory_rate' => 16,
            'temperature' => 36.8,
            'spo2' => 98,
            'avpu' => 'A',
            'pain_score' => 1,
        ], $this->user);
    }

    public function test_emergency_session_tracks_contributors_without_replacing_primary_team(): void
    {
        $doctor = User::factory()->create(['department_id' => $this->department->id]);
        $nurse = User::factory()->create(['department_id' => $this->department->id]);
        $clinician = User::factory()->create(['department_id' => $this->department->id]);
        $case = $this->makeCase([
            'assigned_doctor_id' => $doctor->id,
            'assigned_nurse_id' => $nurse->id,
        ]);

        $sessions = app(EmergencySessionService::class);
        $session = $sessions->getOrCreateForCase($case, $doctor);
        $sessions->recordContribution($case, $clinician, 'Procedure');

        $session->refresh();

        $this->assertSame($doctor->id, $session->main_doctor_id);
        $this->assertSame($nurse->id, $session->primary_nurse_id);
        $this->assertDatabaseHas('emergency_session_contributors', [
            'emergency_session_id' => $session->id,
            'emergency_case_id' => $case->id,
            'user_id' => $clinician->id,
            'role' => 'Procedure',
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
        $this->assertNotNull($order->emergency_session_id);
        $this->assertDatabaseHas('medication_administration_schedules', [
            'medication_order_id' => $order->id,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $order->emergency_session_id,
        ]);
        $this->assertDatabaseHas('clinical_tasks', [
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $order->emergency_session_id,
            'task_type' => ClinicalTask::TYPE_MEDICATION_ADMINISTRATION,
        ]);
    }

    public function test_emergency_investigation_uses_service_from_selected_department(): void
    {
        $case = $this->makeCase();
        $department = Department::factory()->create([
            'name' => 'Radiology',
            'code' => 'RAD',
            'type' => 'radiology',
            'status' => 'active',
        ]);
        $service = ServiceCatalog::create([
            'name' => 'Emergency X-Ray Chest',
            'code' => 'XR-CHEST-ER',
            'category' => 'radiology',
            'price' => 50,
            'is_active' => true,
            'is_billable' => true,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)->post(route('admin.emergency.investigations.store', $case), [
            'target_department_id' => $department->id,
            'service_id' => $service->id,
            'urgency' => 'emergency',
            'clinical_info' => 'Chest trauma after accident',
        ])->assertRedirect();

        $this->assertDatabaseHas('lab_requests', [
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'target_department_id' => $department->id,
            'is_emergency' => true,
        ]);
        $this->assertDatabaseHas('lab_request_items', [
            'service_id' => $service->id,
            'name' => $service->name,
        ]);
    }

    public function test_emergency_procedure_uses_service_from_selected_department(): void
    {
        $case = $this->makeCase();
        $department = Department::factory()->create([
            'name' => 'Emergency Theatre',
            'code' => 'ETH',
            'type' => 'procedure',
            'status' => 'active',
        ]);
        $service = ServiceCatalog::create([
            'name' => 'Emergency Wound Debridement',
            'code' => 'ER-DEBRIDE',
            'category' => 'procedure',
            'price' => 120,
            'is_active' => true,
            'is_billable' => true,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)->post(route('admin.emergency.procedures.store', $case), [
            'department_id' => $department->id,
            'service_catalog_id' => $service->id,
            'priority' => 'emergency',
            'indication' => 'Contaminated wound requiring urgent debridement',
        ])->assertRedirect();

        $this->assertDatabaseHas('procedure_requests', [
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'department_id' => $department->id,
            'service_catalog_id' => $service->id,
            'is_emergency' => true,
        ]);
    }

    public function test_emergency_consumable_uses_stock_and_bills_visit_invoice(): void
    {
        $case = $this->makeCase();
        $location = StockLocation::create([
            'name' => 'Emergency Store Test',
            'type' => 'emergency',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'IV Cannula',
            'code' => 'IVC-ER',
            'product_type' => ProductType::CONSUMABLE,
            'unit' => 'piece',
            'base_price' => 12.50,
            'is_billable' => true,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
        StockBalance::create([
            'product_id' => $product->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 5,
        ]);

        $this->actingAs($this->user)->post(route('admin.emergency.consumables.store', $case), [
            'product_id' => $product->id,
            'quantity' => 2,
            'notes' => 'Used during resuscitation',
        ])->assertRedirect();

        $usage = $case->consumableUsages()->first();
        $session = $case->fresh('activeEmergencySession')->activeEmergencySession;

        $this->assertNotNull($usage);
        $this->assertSame(3.0, (float) StockBalance::where('product_id', $product->id)->where('stock_location_id', $location->id)->value('quantity_on_hand'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'stock_location_id' => $location->id,
            'movement_type' => StockMovementType::EMERGENCY_ADMINISTRATION_OUT->value,
        ]);
        $this->assertDatabaseHas('consumable_usages', [
            'id' => $usage->id,
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $case->visit_id,
            'product_id' => $product->id,
            'source_type' => InvoiceItem::SOURCE_EMERGENCY_CONSUMABLE,
            'source_id' => $usage->id,
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
        app(EmergencySessionService::class)->getOrCreateForCase($case, $this->user);

        $preview = app(VisitPreviewService::class)->build($case->visit->fresh());
        $titles = collect($preview['timeline'])->pluck('title');

        $this->assertTrue($titles->contains('Emergency Case Opened'));
        $this->assertTrue($titles->contains('Emergency Session Active'));
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
