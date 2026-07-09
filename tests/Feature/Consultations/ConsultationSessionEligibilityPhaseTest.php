<?php

namespace Tests\Feature\Consultations;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\BedType;
use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\Ward;
use App\Services\Admissions\AdmissionExtensionService;
use App\Services\Consultation\ConsultationSessionEligibilityService;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSessionEligibilityPhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Department $department;

    private ServiceCatalog $service;

    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        $ward = Ward::create([
            'name' => 'Medical Ward',
            'code' => 'MW',
            'department_id' => $this->department->id,
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->bed = Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => 'MW-01',
            'bed_type' => BedType::STANDARD->value,
            'status' => BedStatus::OCCUPIED->value,
            'daily_rate' => 0,
        ]);

        $this->service = ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'CONS-ELIG',
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        Role::findOrCreate('Doctor', 'web');

        $role = Role::findOrCreate('Eligibility Doctor', 'web');
        foreach ([
            'visits.view',
            'consultations.view',
            'consultations.create',
            'consultations.reopen',
            'consultations.reopen_completed',
            'consultations.reopen_same_day_discharge',
            'consultations.reopen_after_window',
            'consultations.sessions.override_lock',
            'visits.reopen_locked_session',
            'admissions.extend',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->doctor = User::factory()->create(['department_id' => $this->department->id]);
        $this->doctor->assignRole($role);
    }

    public function test_outpatient_same_day_visit_can_add_consultation_item(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::OUTPATIENT, VisitStatus::CONSULTING, now());

        $this->postComplaint($visit, $route)->assertOk();

        $this->assertSame(1, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_outpatient_same_day_auto_completed_consultation_can_be_reopened(): void
    {
        [$visit, $route] = $this->completedOutpatient(now());

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Same day correction',
            ])
            ->assertOk();

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
    }

    public function test_outpatient_next_day_visit_is_blocked_from_adding_new_item(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::OUTPATIENT, VisitStatus::CONSULTING, now()->subDay());

        $this->postComplaint($visit, $route)->assertStatus(423);

        $this->assertSame(0, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_outpatient_next_day_reopen_is_blocked_without_override_permission(): void
    {
        [$visit, $route] = $this->completedOutpatient(now()->subDay());

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Late normal reopen',
            ])
            ->assertStatus(422);
    }

    public function test_outpatient_previous_day_does_not_show_normal_reopen_even_with_admin_override_permissions(): void
    {
        [$visit, $route] = $this->completedOutpatient(now()->subDay());
        $route->forceFill(['reopened_at' => now()->subHours(2)])->save();

        $this->actingAs($this->doctor)
            ->get(route('admin.visits.show', $visit))
            ->assertOk()
            ->assertSee($route->department?->name)
            ->assertDontSee(route('admin.consultations.routes.reopen', [$visit, $route]), false)
            ->assertDontSee('js-queue-consultation-route-form', false)
            ->assertDontSee(route('admin.consultations.routes.store', $visit), false);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Previous day outpatient should stay closed',
            ])
            ->assertStatus(422);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.store', $visit), [
                'department_id' => $this->department->id,
                'service_ids' => [$this->service->id],
            ])
            ->assertStatus(422);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertDontSee('bg-warning text-dark ms-1', false);
    }

    public function test_active_admitted_inpatient_can_add_item_to_active_session_even_when_visit_is_old(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::ADMITTED, now()->subDays(4));
        $this->admission($visit, AdmissionStatus::ADMITTED);

        $this->postComplaint($visit->fresh('admission'), $route)->assertOk();

        $this->assertSame(1, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_active_admitted_inpatient_is_not_blocked_by_system_completed_visit_state(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::COMPLETED, now()->subDays(3), [
            'completed_at' => now(),
            'completed_by' => $this->doctor->id,
        ]);
        $this->admission($visit, AdmissionStatus::ADMITTED);

        $this->postComplaint($visit->fresh('admission'), $route)->assertOk();

        $this->assertSame(1, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_completed_session_is_locked_but_other_active_session_remains_editable(): void
    {
        [$visit, $completed] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::ADMITTED, now()->subDays(2), [
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $this->doctor->id,
        ]);
        $this->admission($visit, AdmissionStatus::ADMITTED);

        $active = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);
        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($active, $this->doctor);

        $eligibility = app(ConsultationSessionEligibilityService::class);

        $this->assertFalse($eligibility->canAddItem($visit->fresh('admission'), $completed, $this->doctor));
        $this->assertTrue($eligibility->canAddItem($visit->fresh('admission'), $active, $this->doctor));
    }

    public function test_completed_inpatient_session_workspace_is_readonly_when_no_active_session_exists(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::ADMITTED, now()->subDays(2), [
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $this->doctor->id,
        ]);
        $this->admission($visit, AdmissionStatus::ADMITTED);
        $this->addCompletionReadyRecord($route);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertSee(__('consultations.reopen.title'))
            ->assertSee(__('consultations.reopen.active_admission_allowed'))
            ->assertDontSee(__('consultations.workspace.consultation_in_progress'))
            ->assertDontSee('addComplaintForm', false)
            ->assertDontSee('data-consultation-form="complaints"', false)
            ->assertDontSee('edit-entry-btn', false)
            ->assertDontSee('ajax-delete', false)
            ->assertDontSee(route('admin.consultations.complaints.update', $route->medicalRecord->complaints()->first()), false)
            ->assertDontSee(route('admin.consultations.complaints.destroy', $route->medicalRecord->complaints()->first()), false);
    }

    public function test_inpatient_discharged_today_can_reopen_session_and_add_item(): void
    {
        [$visit, $route] = $this->completedInpatient(now());

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Same day discharge correction',
            ])
            ->assertOk();

        $this->postComplaint($visit->fresh('admission'), $route->fresh())->assertOk();

        $this->assertSame(1, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_inpatient_discharged_before_today_is_blocked_without_override_or_extension(): void
    {
        [$visit, $route] = $this->completedInpatient(now()->subDay());

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Old discharge correction',
            ])
            ->assertStatus(422);
    }

    public function test_extend_admission_reopens_active_session_workflow_without_duplicate_active_admission(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::DISCHARGED, now()->subDays(4));
        $admission = $this->admission($visit, AdmissionStatus::DISCHARGED, now()->subDay());

        $this->assertFalse(app(ConsultationSessionEligibilityService::class)->canAddItem($visit->fresh('admission'), $route, $this->doctor));

        app(AdmissionExtensionService::class)->extend($admission, $this->doctor, 'Continue inpatient care');

        $this->assertTrue(app(ConsultationSessionEligibilityService::class)->canAddItem($visit->fresh('admission'), $route, $this->doctor));
        $this->assertSame(1, Admission::where('patient_id', $visit->patient_id)
            ->whereIn('status', [AdmissionStatus::ADMITTED->value, AdmissionStatus::ON_LEAVE->value])
            ->whereNull('actual_discharge_date')
            ->count());
    }

    public function test_extend_admission_does_not_create_or_allow_duplicate_active_admissions(): void
    {
        [$visit] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::DISCHARGED, now()->subDays(4));
        $admission = $this->admission($visit, AdmissionStatus::DISCHARGED, now()->subDay());
        $this->admission($visit, AdmissionStatus::ADMITTED);

        $this->expectException(ValidationException::class);

        app(AdmissionExtensionService::class)->extend($admission, $this->doctor, 'Duplicate prevention');
    }

    public function test_manual_complete_consultation_action_is_not_available(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::OUTPATIENT, VisitStatus::CONSULTING, now());

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertDontSee(__('consultations.workspace.complete_consultation'));
    }

    public function test_outpatient_consultation_auto_completes_when_all_required_sessions_complete(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::OUTPATIENT, VisitStatus::CONSULTING, now());
        $this->addCompletionReadyRecord($route);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.complete', [$visit, $route]), [
                'notes' => 'All done',
            ])
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $this->assertSame(VisitStatus::COMPLETED, $visit->fresh()->status);
        $this->assertNotNull($visit->fresh()->completed_at);
        $this->assertDatabaseHas('activity_log', [
            'event' => 'CONSULTATION_SYSTEM_COMPLETED',
            'subject_type' => VisitConsultationRoute::class,
            'subject_id' => $route->id,
        ]);
    }

    public function test_active_inpatient_consultation_does_not_hard_lock_while_still_admitted(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::ADMITTED, now()->subDays(2));
        $this->admission($visit, AdmissionStatus::ADMITTED);
        $this->addCompletionReadyRecord($route);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.complete', [$visit, $route]), [
                'notes' => 'Ward session done',
            ])
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $this->assertSame(VisitStatus::ADMITTED, $visit->fresh()->status);
        $this->assertNull($visit->fresh()->completed_at);
    }

    public function test_discharged_inpatient_consultation_auto_completes_after_required_session_completion(): void
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::DISCHARGED, now()->subDays(2));
        $this->admission($visit, AdmissionStatus::DISCHARGED, now());
        $this->addCompletionReadyRecord($route);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.complete', [$visit, $route]), [
                'notes' => 'Discharge day correction complete',
            ])
            ->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $this->assertSame(VisitStatus::DISCHARGED, $visit->fresh()->status);
        $this->assertNotNull($visit->fresh()->completed_at);
    }

    public function test_default_consultation_list_visibility_matches_editable_windows(): void
    {
        [$activeInpatient] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::ADMITTED, now()->subDays(5));
        $this->admission($activeInpatient, AdmissionStatus::ADMITTED);

        [$dischargedToday] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::DISCHARGED, now()->subDays(4));
        $this->admission($dischargedToday, AdmissionStatus::DISCHARGED, now());

        [$completedToday] = $this->completedOutpatient(now());
        [$oldOutpatient] = $this->completedOutpatient(now()->subDay());

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSee($activeInpatient->visit_number)
            ->assertSee($dischargedToday->visit_number)
            ->assertSee($completedToday->visit_number)
            ->assertDontSee($oldOutpatient->visit_number);
    }

    private function postComplaint(Visit $visit, VisitConsultationRoute $route)
    {
        return $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'Chest pain',
            ]);
    }

    private function completedOutpatient($day): array
    {
        return $this->visitWithRoute(VisitType::OUTPATIENT, VisitStatus::COMPLETED, $day, [
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => $day,
            'completed_by' => $this->doctor->id,
        ], [
            'completed_at' => $day,
            'completed_by' => $this->doctor->id,
        ]);
    }

    private function completedInpatient($dischargedAt): array
    {
        [$visit, $route] = $this->visitWithRoute(VisitType::INPATIENT, VisitStatus::DISCHARGED, now()->subDays(3), [
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $this->doctor->id,
        ]);
        $this->admission($visit, AdmissionStatus::DISCHARGED, $dischargedAt);

        return [$visit->fresh('admission'), $route->fresh()];
    }

    private function visitWithRoute(
        VisitType $type,
        VisitStatus $status,
        $visitDate,
        array $routeOverrides = [],
        array $visitOverrides = [],
    ): array {
        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create(array_merge([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'visit_type' => $type->value,
            'visit_date' => $visitDate,
            'status' => $status->value,
            'priority' => Priority::NORMAL->value,
            'current_department_id' => $this->department->id,
        ], $visitOverrides));

        $route = VisitConsultationRoute::create(array_merge([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ], $routeOverrides));

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        return [$visit->fresh(['consultationRoutes', 'admission']), $route->fresh('medicalRecord')];
    }

    private function admission(Visit $visit, AdmissionStatus $status, $dischargedAt = null): Admission
    {
        return Admission::create([
            'admission_number' => 'ADM-ELIG-'.$visit->id.'-'.Admission::query()->count(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->doctor->id,
            'admission_date' => now()->subDays(5),
            'actual_discharge_date' => $dischargedAt,
            'discharged_by' => $dischargedAt ? $this->doctor->id : null,
            'status' => $status->value,
        ]);
    }

    private function addCompletionReadyRecord(VisitConsultationRoute $route): void
    {
        $record = app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);
        $base = [
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
        ];

        $record->complaints()->create($base + ['description' => 'Review complaint']);
        $record->physicalExaminations()->create($base + ['findings' => 'Stable']);
        $record->diagnoses()->create($base + ['description' => 'Clinical review', 'type' => 'provisional', 'is_primary' => true]);
        $record->treatments()->create($base + ['type' => 'advice', 'description' => 'Continue plan']);
    }
}
