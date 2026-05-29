<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\BedType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\AdmissionBedCharge;
use App\Models\Bed;
use App\Models\Department;
use App\Models\EmergencyBay;
use App\Models\EmergencyBedCharge;
use App\Models\EmergencyCase;
use App\Models\EmergencyDailyConsumableCharge;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitPathwayEvent;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\EmergencyCaseService;
use App\Services\EmergencyDispositionService;
use App\Services\OutpatientSessionAutoCloseService;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VisitStatusPatientPathwayWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->department = Department::create([
            'name' => 'Emergency',
            'code' => 'ER',
            'type' => 'treatment',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'status' => 'active',
        ]);

        $role = Role::findOrCreate('Workflow Test User', 'web');
        foreach ([
            'visits.create',
            'visits.view',
            'ward.admit',
            'emergency.case.create',
            'emergency.disposition.manage',
            'invoices.create',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);
    }

    public function test_active_admission_blocks_new_opd_visit_unless_authorized_with_reason(): void
    {
        $this->createActiveAdmission();
        $this->actingAs($this->user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('currently admitted');

        app(VisitService::class)->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'visit_date' => today()->toDateString(),
        ]);
    }

    public function test_authorized_active_admission_override_creates_visit_and_pathway_event(): void
    {
        $this->createActiveAdmission();
        $this->user->givePermissionTo(Permission::findOrCreate('visits.create_while_admitted', 'web'));
        $this->actingAs($this->user);

        $visit = app(VisitService::class)->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'visit_date' => today()->toDateString(),
            'admission_override_reason' => 'Emergency OPD review requested by ward team.',
        ]);

        $this->assertSame($this->patient->id, $visit->patient_id);
        $this->assertDatabaseHas('visit_pathway_events', [
            'visit_id' => $visit->id,
            'event_type' => 'VISIT_REGISTERED',
        ]);
    }

    public function test_emergency_case_from_existing_visit_reuses_visit_and_creates_emergency_session(): void
    {
        $this->actingAs($this->user);
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::REGISTERED,
            'priority' => Priority::NORMAL,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $case = app(EmergencyCaseService::class)->create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'arrival_mode' => 'TRANSFER_FROM_OPD',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'Sudden collapse',
            'initial_condition' => 'Unstable',
        ], $this->user);

        $this->assertSame($visit->id, $case->visit_id);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatus::EMERGENCY->value,
        ]);
        $this->assertDatabaseHas('visit_consultation_routes', [
            'visit_id' => $visit->id,
            'emergency_case_id' => $case->id,
            'session_type' => VisitConsultationRoute::SESSION_TYPE_EMERGENCY,
        ]);
        $this->assertDatabaseHas('visit_pathway_events', [
            'visit_id' => $visit->id,
            'event_type' => 'EMERGENCY_SESSION_CREATED',
        ]);
    }

    public function test_auto_lock_completes_previous_day_outpatient_session_without_touching_emergency(): void
    {
        $this->actingAs($this->user);
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT,
            'visit_date' => today()->subDay()->toDateString(),
            'status' => VisitStatus::CONSULTING,
            'created_by' => $this->user->id,
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'session_type' => VisitConsultationRoute::SESSION_TYPE_CONSULTATION,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'started_at' => today()->subDay()->setTime(9, 0),
            'routed_by' => $this->user->id,
            'started_by' => $this->user->id,
        ]);

        $count = app(OutpatientSessionAutoCloseService::class)->closeStaleSessions();

        $this->assertSame(1, $count);
        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $route->fresh()->status);
        $this->assertNotNull($route->fresh()->locked_at);
        $this->assertSame(VisitStatus::COMPLETED, $visit->fresh()->status);
    }

    public function test_registered_visit_can_be_admitted_directly(): void
    {
        $this->actingAs($this->user);
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::REGISTERED,
            'priority' => Priority::NORMAL,
            'created_by' => $this->user->id,
        ]);
        $bed = $this->createWardBed('D001', 90);

        $admission = app(AdmissionService::class)->admit([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $bed->id,
            'admission_type' => 'admission',
            'admission_date' => now()->format('Y-m-d H:i:s'),
            'expected_discharge_date' => today()->addDay()->toDateString(),
        ]);

        $this->assertSame($visit->id, $admission->visit_id);
        $this->assertSame(VisitStatus::ADMITTED, $visit->fresh()->status);
        $this->assertSame(VisitType::INPATIENT, $visit->fresh()->visit_type);
        $this->assertTrue(VisitPathwayEvent::where('visit_id', $visit->id)->where('event_type', 'ADMISSION_STARTED')->exists());
    }

    public function test_emergency_bed_billing_continues_until_admission_bed_assignment_and_uses_one_visit_invoice(): void
    {
        $this->actingAs($this->user);
        $emergencyBay = $this->createEmergencyBayWithBed();
        ServiceCatalog::create([
            'name' => 'Emergency Daily Consumables',
            'code' => 'ER-CONSUMABLE-DAY',
            'category' => 'other',
            'price' => 25,
            'is_active' => true,
            'is_billable' => true,
            'department_id' => $this->department->id,
        ]);

        $case = app(EmergencyCaseService::class)->create([
            'patient_id' => $this->patient->id,
            'arrival_mode' => 'AMBULANCE',
            'arrival_time' => now()->subHours(3)->format('Y-m-d H:i:s'),
            'chief_complaint' => 'Severe trauma',
            'initial_condition' => 'Unstable',
            'emergency_bay_id' => $emergencyBay->id,
        ], $this->user);

        $this->assertDatabaseHas('emergency_bed_charges', [
            'emergency_case_id' => $case->id,
            'status' => EmergencyBedCharge::STATUS_ACTIVE,
        ]);

        app(EmergencyDispositionService::class)->dispose($case, [
            'disposition' => EmergencyCase::DISPOSITION_ADMITTED,
            'disposition_time' => now()->format('Y-m-d H:i:s'),
        ], $this->user);

        $this->assertNotNull($case->fresh()->emergency_bay_id);
        $this->assertSame(EmergencyBedCharge::STATUS_ACTIVE, EmergencyBedCharge::first()->status);
        $this->assertSame(EmergencyDailyConsumableCharge::STATUS_ACTIVE, EmergencyDailyConsumableCharge::first()->status);

        $admissionBed = $this->createWardBed('A001', 120);
        $admission = app(AdmissionService::class)->admit([
            'visit_id' => $case->visit_id,
            'patient_id' => $this->patient->id,
            'bed_id' => $admissionBed->id,
            'admission_type' => 'admission',
            'admission_date' => now()->format('Y-m-d H:i:s'),
            'expected_discharge_date' => today()->addDay()->toDateString(),
        ]);

        $this->assertSame($admission->id, $case->fresh()->admission_id);
        $this->assertNull($case->fresh()->emergency_bay_id);
        $this->assertSame(EmergencyBedCharge::STATUS_BILLED, EmergencyBedCharge::first()->fresh()->status);
        $this->assertNotNull(EmergencyBedCharge::first()->invoice_item_id);
        $this->assertNotNull(AdmissionBedCharge::first()->invoice_item_id);
        $this->assertSame('ADM-BED-DAY', ServiceCatalog::find(AdmissionBedCharge::first()->service_id)?->code);
        $this->assertSame(1, Invoice::where('visit_id', $case->visit_id)->count());
        $this->assertTrue(VisitPathwayEvent::where('visit_id', $case->visit_id)->where('event_type', 'ADMISSION_STARTED')->exists());
    }

    private function createActiveAdmission(): Admission
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_date' => today()->subDay()->toDateString(),
            'status' => VisitStatus::ADMITTED,
            'visit_type' => VisitType::INPATIENT,
            'created_by' => $this->user->id,
        ]);
        $bed = $this->createWardBed('G001', 100);

        return Admission::create([
            'admission_number' => Admission::generateAdmissionNumber(),
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $bed->id,
            'admitted_by' => $this->user->id,
            'admission_date' => today()->subDay(),
            'status' => AdmissionStatus::ADMITTED,
            'admission_type' => 'admission',
        ]);
    }

    private function createEmergencyBayWithBed(): EmergencyBay
    {
        $bed = $this->createWardBed('ER01', 75);

        return EmergencyBay::create([
            'name' => 'ER Bay 1',
            'code' => 'ERB1',
            'bay_type' => EmergencyBay::TYPE_OBSERVATION,
            'department_id' => $this->department->id,
            'ward_id' => $bed->ward_id,
            'bed_id' => $bed->id,
            'status' => EmergencyBay::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
    }

    private function createWardBed(string $number, float $rate): Bed
    {
        $ward = Ward::firstOrCreate(
            ['code' => 'TWARD'],
            [
                'name' => 'Test Ward',
                'department_id' => $this->department->id,
                'capacity' => 10,
                'is_active' => true,
            ]
        );

        return Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => $number,
            'bed_type' => BedType::STANDARD,
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => $rate,
        ]);
    }
}
