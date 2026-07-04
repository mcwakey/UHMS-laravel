<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\VisitStatus;
use App\Events\PatientDischarged;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\Admissions\AdmissionRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionWorkflowFoundationTest extends TestCase
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

        $role = Role::findOrCreate('Admission Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'ward.discharge',
            'admission.requests.view', 'admission.requests.create', 'admission.requests.accept',
            'admission.requests.reject', 'admission.requests.cancel', 'admission.requests.convert',
            'admission.requests.bed_pending', 'admission.requests.reserve_bed',
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

    public function test_admission_request_can_be_created_and_accepted(): void
    {
        $request = app(AdmissionRequestService::class)->createForVisit(
            $this->visit,
            AdmissionRequestSource::DIRECT,
            null,
            [
                'requested_ward_id' => $this->ward->id,
                'priority' => 'high',
                'provisional_diagnosis' => 'Observation',
            ],
            $this->user
        );

        $this->assertDatabaseHas('admission_requests', [
            'id' => $request->id,
            'visit_id' => $this->visit->id,
            'status' => AdmissionRequestStatus::REQUESTED->value,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ]);

        $accepted = app(AdmissionRequestService::class)->accept($request, $this->user);

        $this->assertSame(AdmissionRequestStatus::ACCEPTED, $accepted->status);
        $this->assertSame($this->user->id, $accepted->accepted_by);
    }

    public function test_admission_request_board_loads(): void
    {
        AdmissionRequest::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT,
            'status' => AdmissionRequestStatus::REQUESTED,
            'requested_by' => $this->user->id,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.admissions.requests'))
            ->assertOk()
            ->assertSee($this->patient->first_name);
    }

    public function test_admission_request_can_be_rejected_and_cancelled_with_reason(): void
    {
        $service = app(AdmissionRequestService::class);

        $rejected = $service->reject($service->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ], $this->user), 'No bed required', $this->user);

        $cancelled = $service->cancel($service->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ], $this->user), 'Patient declined', $this->user);

        $this->assertSame(AdmissionRequestStatus::REJECTED, $rejected->status);
        $this->assertSame('No bed required', $rejected->reason);
        $this->assertSame(AdmissionRequestStatus::CANCELLED, $cancelled->status);
        $this->assertSame('Patient declined', $cancelled->reason);
    }

    public function test_rejected_and_cancelled_requests_cannot_be_converted(): void
    {
        $service = app(AdmissionRequestService::class);

        $rejected = $service->reject($service->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ], $this->user), 'Rejected', $this->user);

        $this->expectException(ValidationException::class);
        $service->convertToAdmission($rejected, ['bed_id' => $this->bed->id], $this->user);
    }

    public function test_cancelled_request_cannot_be_converted(): void
    {
        $service = app(AdmissionRequestService::class);

        $cancelled = $service->cancel($service->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ], $this->user), 'Cancelled', $this->user);

        $this->expectException(ValidationException::class);
        $service->convertToAdmission($cancelled, ['bed_id' => $this->bed->id], $this->user);
    }

    public function test_bed_reservation_rejects_occupied_bed(): void
    {
        $this->bed->markOccupied();
        $request = app(AdmissionRequestService::class)->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
        ], $this->user);

        $this->expectException(ValidationException::class);
        app(AdmissionRequestService::class)->reserveBed($request, $this->bed, $this->user);
    }

    public function test_existing_direct_admission_creation_still_works(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.admissions.store'), [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
            'admitting_diagnosis' => 'Observation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('admissions', [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'status' => AdmissionStatus::ADMITTED->value,
        ]);
    }

    public function test_user_without_accept_permission_cannot_accept_request(): void
    {
        $viewer = User::factory()->create();
        $role = Role::findOrCreate('Admission Request Viewer', 'web');
        foreach (['ward.view', 'admission.requests.view'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $viewer->assignRole($role);

        $request = AdmissionRequest::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT,
            'status' => AdmissionRequestStatus::REQUESTED,
            'requested_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->patch(route('admin.admissions.requests.accept', $request))
            ->assertForbidden();
    }

    public function test_discharge_dispatches_event_and_writes_activity_log(): void
    {
        Event::fake([PatientDischarged::class]);

        $admission = Admission::create([
            'admission_number' => Admission::generateAdmissionNumber(),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->user->id,
            'admitting_diagnosis' => 'Observation',
            'admission_date' => now(),
            'status' => AdmissionStatus::ADMITTED,
        ]);
        $this->bed->markOccupied();
        $this->visit->update(['status' => VisitStatus::ADMITTED]);

        $this->actingAs($this->user);
        app(AdmissionService::class)->discharge($admission, [
            'discharge_summary' => 'Stable',
            'discharge_instructions' => 'Review in one week',
        ]);

        Event::assertDispatched(PatientDischarged::class);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ADMISSION',
            'event' => 'DISCHARGED',
            'subject_type' => Admission::class,
            'subject_id' => $admission->id,
        ]);
    }
}
