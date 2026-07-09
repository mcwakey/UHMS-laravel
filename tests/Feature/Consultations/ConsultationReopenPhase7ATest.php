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
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\Ward;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationReopenPhase7ATest extends TestCase
{
    use RefreshDatabase;

    private User $authorised;

    private User $unauthorised;

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
            'code' => 'CONS-P7A',
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->authorised = $this->userWithPermissions('Phase 7A Doctor', [
            'visits.view',
            'consultations.view',
            'consultations.create',
            'consultations.reopen',
            'consultations.reopen_completed',
            'consultations.reopen_same_day_discharge',
        ]);

        $this->unauthorised = $this->userWithPermissions('Phase 7A Viewer', [
            'visits.view',
            'consultations.view',
            'consultations.create',
        ]);
    }

    public function test_visit_list_includes_patient_still_on_admission(): void
    {
        [$visit] = $this->inpatientVisitWithAdmission(
            visitDate: now()->subDays(4),
            admissionStatus: AdmissionStatus::ADMITTED,
        );

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.index'))
            ->assertOk()
            ->assertSee($visit->visit_number)
            ->assertSee(__('visits.badges.on_admission'));
    }

    public function test_visit_list_includes_patient_discharged_today(): void
    {
        [$visit] = $this->inpatientVisitWithAdmission(
            visitDate: now()->subDays(3),
            admissionStatus: AdmissionStatus::DISCHARGED,
            dischargedAt: now(),
        );

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.index'))
            ->assertOk()
            ->assertSee($visit->visit_number)
            ->assertSee(__('visits.badges.discharged_today'));
    }

    public function test_visit_list_excludes_patient_discharged_before_today_unless_history_filter_is_used(): void
    {
        [$visit] = $this->inpatientVisitWithAdmission(
            visitDate: now()->subDays(5),
            admissionStatus: AdmissionStatus::DISCHARGED,
            dischargedAt: now()->subDay(),
        );

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.index'))
            ->assertOk()
            ->assertDontSee($visit->visit_number);

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.index', [
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($visit->visit_number);
    }

    public function test_completed_outpatient_visit_shows_reopen_action_for_authorised_user(): void
    {
        [$visit] = $this->completedOutpatientVisit();

        $this->actingAs($this->authorised);
        $html = $this->renderVisitIndex();

        $this->assertStringContainsString($visit->visit_number, $html);
        $this->assertStringContainsString(__('visits.actions.reopen_consultation'), $html);
    }

    public function test_completed_outpatient_visit_does_not_show_reopen_action_for_unauthorised_user(): void
    {
        [$visit] = $this->completedOutpatientVisit();

        $this->actingAs($this->unauthorised);
        $html = $this->renderVisitIndex();

        $this->assertStringContainsString($visit->visit_number, $html);
        $this->assertStringNotContainsString(__('visits.actions.reopen_consultation'), $html);
    }

    public function test_same_day_discharged_inpatient_shows_reopen_action_for_authorised_user(): void
    {
        [$visit] = $this->completedInpatientRouteWithDischarge(now());

        $this->actingAs($this->authorised);
        $html = $this->renderVisitIndex();

        $this->assertStringContainsString($visit->visit_number, $html);
        $this->assertStringContainsString(__('visits.actions.reopen_consultation'), $html);
    }

    public function test_inpatient_discharged_before_today_cannot_be_reopened_by_normal_permission(): void
    {
        [$visit, $route] = $this->completedInpatientRouteWithDischarge(now()->subDay());

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Late correction attempt',
            ])
            ->assertStatus(422);

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $route->fresh()->status);
    }

    public function test_reopen_requires_reason(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit();

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_reopen_changes_route_to_editable_without_duplicate_route_or_medical_record(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit();
        $routeCount = VisitConsultationRoute::where('visit_id', $visit->id)->count();
        $recordCount = MedicalRecord::where('visit_id', $visit->id)->count();

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Same day clinical correction',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $route->refresh();
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->status);
        $this->assertNotNull($route->reopened_at);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertNull($visit->fresh()->completed_at);
        $this->assertNull($visit->fresh()->completed_by);
        $this->assertSame($routeCount, VisitConsultationRoute::where('visit_id', $visit->id)->count());
        $this->assertSame($recordCount, MedicalRecord::where('visit_id', $visit->id)->count());
    }

    public function test_visit_details_reopens_completed_session_and_returns_to_visit_journey(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit();

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.show', $visit))
            ->assertOk()
            ->assertSee(__('visits.actions.reopen_consultation'));

        $this->actingAs($this->authorised)
            ->post(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'return_to_visit' => '1',
                'reason' => __('consultations.reopen.visit_details_reason'),
            ])
            ->assertRedirect(route('admin.visits.show', $visit));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => VisitStatus::COMPLETED->value,
            'to_status' => VisitStatus::CONSULTING->value,
        ]);

        $this->actingAs($this->authorised)
            ->get(route('admin.visits.show', $visit))
            ->assertOk()
            ->assertSee('visitRouteServiceSelect');
    }

    public function test_visit_details_can_activate_pending_session_after_completed_visit_is_reopened(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit([
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'completed_at' => null,
            'completed_by' => null,
            'started_at' => null,
            'started_by' => null,
            'activated_at' => null,
        ]);

        $this->actingAs($this->authorised)
            ->post(route('admin.consultations.routes.activate', [$visit, $route]), [
                'return_to_visit' => '1',
                'reason' => __('consultations.reopen.visit_details_reason'),
            ])
            ->assertRedirect(route('admin.visits.show', $visit));

        $this->assertSame(VisitStatus::CONSULTING, $visit->fresh()->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertDatabaseHas('activity_log', [
            'event' => 'CONSULTATION_VISIT_REOPENED_FOR_ROUTE_ACTIVATION',
            'subject_type' => VisitConsultationRoute::class,
            'subject_id' => $route->id,
        ]);
    }

    public function test_reopened_route_allows_guarded_clinical_mutation(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit();

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Need to add omitted complaint',
            ])
            ->assertOk();

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'Chest pain',
            ])
            ->assertOk();

        $this->assertSame(1, Complaint::where('visit_id', $visit->id)->count());
    }

    public function test_completed_route_without_reopen_still_blocks_mutation(): void
    {
        [$visit, $route] = $this->completedOutpatientVisit();

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.complaints.store', $visit), [
                'consultation_route_id' => $route->id,
                'description' => 'Chest pain',
            ])
            ->assertStatus(423);
    }

    public function test_cancelled_and_locked_routes_still_block_reopen(): void
    {
        [$visit, $cancelled] = $this->completedOutpatientVisit([
            'status' => VisitConsultationRoute::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $this->authorised->id,
        ]);
        [$lockedVisit, $locked] = $this->completedOutpatientVisit(['locked_at' => now()]);

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $cancelled]), [
                'reason' => 'Cancelled should stay closed',
            ])
            ->assertStatus(422);

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$lockedVisit, $locked]), [
                'reason' => 'Locked should stay closed',
            ])
            ->assertStatus(423);
    }

    public function test_reopen_action_and_same_day_discharge_action_are_audited(): void
    {
        [$visit, $route] = $this->completedInpatientRouteWithDischarge(now());

        $this->actingAs($this->authorised)
            ->postJson(route('admin.consultations.routes.reopen', [$visit, $route]), [
                'reason' => 'Same day discharge clarification',
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'CONSULTATION_REOPEN_ACCEPTED',
            'subject_type' => VisitConsultationRoute::class,
            'subject_id' => $route->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'event' => 'CONSULTATION_REOPEN_AFTER_SAME_DAY_DISCHARGE',
            'subject_type' => VisitConsultationRoute::class,
            'subject_id' => $route->id,
        ]);
    }

    private function userWithPermissions(string $roleName, array $permissions): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user = User::factory()->create(['department_id' => $this->department?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function renderVisitIndex(array $filters = []): string
    {
        $filters = array_merge([
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
            'date_range' => today()->toDateString().' to '.today()->toDateString(),
        ], $filters);

        return view('visits.index', [
            'visits' => app(\App\Services\VisitService::class)->list($filters),
            'stats' => app(\App\Services\VisitService::class)->todayStats($filters),
            'filters' => $filters,
            'insuranceProviderOptions' => collect([[
                'value' => 'cash',
                'label' => __('visits.cash_and_carry'),
            ]]),
        ])->render();
    }

    private function completedOutpatientVisit(array $routeOverrides = []): array
    {
        return $this->visitWithRoute(
            VisitType::OUTPATIENT,
            VisitStatus::COMPLETED,
            now(),
            array_merge([
                'status' => VisitConsultationRoute::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $this->authorised->id,
            ], $routeOverrides),
        );
    }

    private function completedInpatientRouteWithDischarge($dischargedAt): array
    {
        [$visit, $route] = $this->visitWithRoute(
            VisitType::INPATIENT,
            VisitStatus::DISCHARGED,
            now()->subDays(2),
            [
                'status' => VisitConsultationRoute::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $this->authorised->id,
            ],
        );

        $this->createAdmission($visit, AdmissionStatus::DISCHARGED, $dischargedAt);

        return [$visit->fresh('admission'), $route->fresh()];
    }

    private function inpatientVisitWithAdmission($visitDate, AdmissionStatus $admissionStatus, $dischargedAt = null): array
    {
        [$visit, $route] = $this->visitWithRoute(
            VisitType::INPATIENT,
            $admissionStatus === AdmissionStatus::DISCHARGED ? VisitStatus::DISCHARGED : VisitStatus::ADMITTED,
            $visitDate,
        );
        $this->createAdmission($visit, $admissionStatus, $dischargedAt);

        return [$visit->fresh('admission'), $route->fresh()];
    }

    private function visitWithRoute(VisitType $type, VisitStatus $status, $visitDate, array $routeOverrides = []): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->authorised->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->authorised->id,
            'visit_type' => $type->value,
            'visit_date' => $visitDate,
            'status' => $status->value,
            'priority' => Priority::NORMAL->value,
            'current_department_id' => $this->department->id,
            'completed_at' => $status === VisitStatus::COMPLETED ? now() : null,
            'completed_by' => $status === VisitStatus::COMPLETED ? $this->authorised->id : null,
        ]);

        $route = VisitConsultationRoute::create(array_merge([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->authorised->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->authorised->id,
            'started_by' => $this->authorised->id,
            'started_at' => now(),
            'activated_at' => now(),
        ], $routeOverrides));

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->authorised);

        return [$visit->fresh(['consultationRoutes', 'admission']), $route->fresh('medicalRecord')];
    }

    private function createAdmission(Visit $visit, AdmissionStatus $status, $dischargedAt = null): Admission
    {
        return Admission::create([
            'admission_number' => 'ADM-P7A-'.$visit->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->authorised->id,
            'admission_date' => now()->subDays(3),
            'actual_discharge_date' => $dischargedAt,
            'discharged_by' => $dischargedAt ? $this->authorised->id : null,
            'status' => $status->value,
        ]);
    }
}
