<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Creating a visit with visit_type = Emergency must automatically open the
 * Emergency / Casualty clinical session (emergency case + consultation route +
 * billed emergency consultation), overriding the normal OPD triage flow — even
 * when a consultation service was also selected.
 */
class VisitEmergencyTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private ServiceCatalog $emergencyService;
    private ServiceCatalog $opdService;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('Visit Emergency Tester', 'web');
        foreach (['visits.view', 'visits.create'] as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $this->user->assignRole($role);

        $emergencyDept = Department::factory()->create([
            'name' => 'Emergency / Casualty', 'code' => 'EMR', 'type' => 'consultation',
        ]);
        $this->emergencyService = ServiceCatalog::create([
            'name' => 'Emergency Consultation', 'code' => 'EMR-CON', 'category' => 'consultation',
            'price' => 250, 'is_active' => true, 'is_billable' => true, 'department_id' => $emergencyDept->id,
        ]);

        $opdDept = Department::factory()->create([
            'name' => 'General Medicine', 'code' => 'OPD', 'type' => 'consultation',
        ]);
        $this->opdService = ServiceCatalog::create([
            'name' => 'General Consultation', 'code' => 'OPD-CON', 'category' => 'consultation',
            'price' => 100, 'is_active' => true, 'is_billable' => true, 'department_id' => $opdDept->id,
        ]);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
    }

    public function test_emergency_visit_without_service_creates_case_session_and_bills_consultation(): void
    {
        $this->actingAs($this->user)->post(route('admin.visits.store'), [
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'priority' => 'normal',
            'chief_complaint' => 'Collapse at home',
        ])->assertRedirect();

        $visit = Visit::where('patient_id', $this->patient->id)->latest('id')->first();
        $this->assertSame(VisitType::EMERGENCY, $visit->visit_type);
        // Overrides OPD triage — the visit is in EMERGENCY status, not WAITING/TRIAGE.
        $this->assertSame(VisitStatus::EMERGENCY, $visit->status);

        $case = EmergencyCase::where('visit_id', $visit->id)->first();
        $this->assertNotNull($case, 'Emergency case auto-created for emergency visit.');
        $this->assertDatabaseHas('emergency_sessions', ['emergency_case_id' => $case->id, 'status' => 'ACTIVE']);

        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)->first();
        $this->assertSame($this->emergencyService->id, (int) $route->service_id);
        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $visit->id,
            'service_catalog_id' => $this->emergencyService->id,
            'source_type' => 'emergency_service',
        ]);
    }

    public function test_emergency_visit_with_selected_consultation_service_is_still_emergency(): void
    {
        $this->actingAs($this->user)->post(route('admin.visits.store'), [
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'priority' => 'normal',
            'chief_complaint' => 'Chest pain',
            'services' => [
                ['service_catalog_id' => $this->opdService->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $visit = Visit::where('patient_id', $this->patient->id)->latest('id')->first();
        $this->assertSame(VisitStatus::EMERGENCY, $visit->status);

        // Emergency case is created despite a selected consultation service.
        $this->assertDatabaseHas('emergency_cases', ['visit_id' => $visit->id]);

        // Both the selected OPD service AND the emergency consultation are billed.
        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $visit->id, 'service_catalog_id' => $this->opdService->id,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $visit->id, 'service_catalog_id' => $this->emergencyService->id,
            'source_type' => 'emergency_service',
        ]);
    }

    public function test_normal_outpatient_visit_is_unaffected(): void
    {
        $this->actingAs($this->user)->post(route('admin.visits.store'), [
            'patient_id' => $this->patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => 'normal',
            'services' => [
                ['service_catalog_id' => $this->opdService->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $visit = Visit::where('patient_id', $this->patient->id)->latest('id')->first();
        $this->assertNotSame(VisitStatus::EMERGENCY, $visit->status);
        $this->assertSame(0, EmergencyCase::where('visit_id', $visit->id)->count());
    }
}
