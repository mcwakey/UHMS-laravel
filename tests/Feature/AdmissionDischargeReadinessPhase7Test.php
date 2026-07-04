<?php

namespace Tests\Feature;

use App\Enums\AdmissionDischargeClearanceStatus;
use App\Enums\AdmissionDischargeClearanceType;
use App\Enums\AdmissionDischargeSummaryStatus;
use App\Enums\BedStatus;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Events\PatientDischarged;
use App\Models\AdmissionDischargeClearance;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionDischargeReadinessPhase7Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Ward $ward;
    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        $role = Role::findOrCreate('Discharge Readiness Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'ward.discharge',
            'beds.capacity.view',
            'admission.medication_board.view',
            'admission.discharge.readiness.view',
            'admission.discharge.plan',
            'admission.discharge.clearance.view',
            'admission.discharge.clearance.manage',
            'admission.discharge.summary.view',
            'admission.discharge.summary.create',
            'admission.discharge.summary.update',
            'admission.discharge.summary.approve',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::ADMITTING,
        ]);
        MedicalRecord::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
            'department_id' => $department->id,
        ]);

        $this->ward = Ward::create([
            'name' => 'Male Ward',
            'code' => 'MW01',
            'department_id' => $department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);
        $this->bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'B001',
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }

    public function test_discharge_readiness_panel_renders_and_creates_clearance_shells(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->assertSee(__('admissions.discharge_readiness'))
            ->assertSee(__('admissions.discharge_clearance'))
            ->assertSee(__('admissions.structured_discharge_summary'));

        $this->assertDatabaseCount('admission_discharge_clearances', count(AdmissionDischargeClearanceType::cases()));
    }

    public function test_discharge_planning_can_be_started_and_updated(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.discharge-planning.start', $admission), [
                'expected_discharge_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'discharge_planning_note' => 'Likely discharge tomorrow.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertNotNull($admission->fresh()->discharge_planning_started_at);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-planning.update', $admission), [
                'expected_discharge_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'discharge_planning_note' => 'Shifted by one day.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame('Shifted by one day.', $admission->fresh()->discharge_planning_note);
    }

    public function test_clearance_can_be_cleared_blocked_and_revoked(): void
    {
        $admission = $this->admissionWithReadiness();
        $clearance = $admission->fresh('dischargeClearances')->dischargeClearances->firstWhere('clearance_type', AdmissionDischargeClearanceType::NURSING);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-clearances.update', [$admission, $clearance]), [
                'status' => AdmissionDischargeClearanceStatus::CLEARED->value,
                'note' => 'Nursing clear.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame(AdmissionDischargeClearanceStatus::CLEARED, $clearance->fresh()->status);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-clearances.update', [$admission, $clearance]), [
                'status' => AdmissionDischargeClearanceStatus::BLOCKED->value,
                'note' => 'Nursing education pending.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame(AdmissionDischargeClearanceStatus::BLOCKED, $clearance->fresh()->status);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-clearances.revoke', [$admission, $clearance]), [
                'note' => 'New observation required.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame(AdmissionDischargeClearanceStatus::REVOKED, $clearance->fresh()->status);
    }

    public function test_user_without_clearance_permission_cannot_manage_clearance(): void
    {
        $admission = $this->admissionWithReadiness();
        $clearance = $admission->fresh('dischargeClearances')->dischargeClearances->first();
        $viewer = User::factory()->create();
        $role = Role::findOrCreate('Discharge Viewer Only', 'web');
        foreach (['ward.view', 'admission.discharge.readiness.view', 'admission.discharge.clearance.view'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->patch(route('admin.admissions.discharge-clearances.update', [$admission, $clearance]), [
                'status' => AdmissionDischargeClearanceStatus::CLEARED->value,
            ])
            ->assertForbidden();
    }

    public function test_discharge_summary_can_be_created_updated_prepared_and_approved(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.discharge-summary.save', $admission), $this->summaryPayload([
                'primary_diagnosis' => 'Malaria',
            ]))
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $summary = $admission->fresh('dischargeSummaryRecord')->dischargeSummaryRecord;
        $this->assertSame(AdmissionDischargeSummaryStatus::DRAFT, $summary->summary_status);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-summary.update', $admission), $this->summaryPayload([
                'primary_diagnosis' => 'Severe malaria',
            ]))
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame('Severe malaria', $summary->fresh()->primary_diagnosis);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-summary.prepare', [$admission, $summary]))
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $this->assertSame(AdmissionDischargeSummaryStatus::PREPARED, $summary->fresh()->summary_status);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.discharge-summary.approve', [$admission, $summary]))
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-discharge');

        $summary->refresh();
        $this->assertSame(AdmissionDischargeSummaryStatus::APPROVED, $summary->summary_status);
        $this->assertSame($this->user->id, $summary->approved_by);
    }

    public function test_existing_discharge_still_works_when_enforcement_is_disabled(): void
    {
        Event::fake([PatientDischarged::class]);
        config([
            'admissions.discharge.require_clearance_before_discharge' => false,
            'admissions.discharge.require_summary_before_discharge' => false,
            'admissions.discharge.require_billing_clearance_before_discharge' => false,
            'admissions.bed_release_after_discharge' => 'cleaning',
        ]);
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.process-discharge', $admission), [
                'discharge_summary' => 'Stable at discharge.',
                'discharge_instructions' => 'Review in one week.',
            ])
            ->assertRedirect(route('admin.admissions.show', $admission));

        $this->assertSame('discharged', $admission->fresh()->status->value);
        $this->assertSame(BedStatus::CLEANING, $this->bed->fresh()->status);
        Event::assertDispatched(PatientDischarged::class);
    }

    public function test_discharge_is_blocked_when_clearance_enforcement_is_enabled(): void
    {
        config(['admissions.discharge.require_clearance_before_discharge' => true]);
        $admission = $this->admissionWithReadiness();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.process-discharge', $admission), [
                'discharge_summary' => 'Stable at discharge.',
            ])
            ->assertSessionHasErrors('discharge_readiness');

        $this->assertSame('admitted', $admission->fresh()->status->value);
    }

    public function test_discharge_is_blocked_when_summary_enforcement_is_enabled(): void
    {
        config(['admissions.discharge.require_summary_before_discharge' => true]);
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.process-discharge', $admission), [
                'discharge_summary' => 'Stable at discharge.',
            ])
            ->assertSessionHasErrors('discharge_readiness');

        $this->assertSame('admitted', $admission->fresh()->status->value);
    }

    public function test_ward_board_shows_discharge_indicators(): void
    {
        $admission = $this->admissionWithReadiness();
        $admission->update([
            'discharge_planning_started_at' => now(),
            'discharge_planning_started_by' => $this->user->id,
            'expected_discharge_at' => now(),
        ]);
        Invoice::create([
            'invoice_number' => 'INV-TST-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 100,
            'total_amount' => 100,
            'balance' => 100,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.wards.bed-map'))
            ->assertOk()
            ->assertSee(__('admissions.expected_today'))
            ->assertSee(__('admissions.summary_missing_short'))
            ->assertSee(__('admissions.billing_warning_short'));
    }

    private function admission()
    {
        $this->actingAs($this->user);

        return app(AdmissionService::class)->admit([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
        ]);
    }

    private function admissionWithReadiness()
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk();

        return $admission;
    }

    private function summaryPayload(array $overrides = []): array
    {
        return array_merge([
            'primary_diagnosis' => 'Observation',
            'secondary_diagnoses' => "Anaemia\nDehydration",
            'admission_reason' => 'Admitted for observation.',
            'hospital_course' => 'Improved on ward.',
            'investigations_summary' => 'Reviewed.',
            'procedures_summary' => 'None.',
            'treatment_given' => 'Supportive care.',
            'discharge_condition' => 'Stable',
            'discharge_medications' => 'Continue medications as prescribed.',
            'follow_up_instructions' => 'Return for review.',
            'follow_up_date' => now()->addWeek()->format('Y-m-d'),
            'warning_signs' => 'Return if symptoms worsen.',
            'final_outcome' => 'Improved',
        ], $overrides);
    }
}
