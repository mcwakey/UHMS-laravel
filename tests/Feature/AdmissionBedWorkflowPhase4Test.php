<?php

namespace Tests\Feature;

use App\Enums\AdmissionLocationEvent;
use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\AdmissionStatus;
use App\Enums\BedReservationStatus;
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
use App\Services\Admissions\BedWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionBedWorkflowPhase4Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Ward $ward;
    private Bed $bed;
    private Bed $secondBed;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        $role = Role::findOrCreate('Bed Workflow Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'ward.discharge',
            'admission.requests.view', 'admission.requests.create', 'admission.requests.reserve_bed', 'admission.requests.convert',
            'beds.view', 'beds.manage', 'beds.reserve', 'beds.release', 'beds.block', 'beds.transfer',
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
        $this->bed = $this->bed('B001');
        $this->secondBed = $this->bed('B002');
    }

    public function test_request_bed_reservation_creates_reservation_record_and_marks_bed_reserved(): void
    {
        $request = $this->admissionRequest();

        $reserved = app(AdmissionRequestService::class)->reserveBed($request, $this->bed, $this->user);

        $this->assertSame(AdmissionRequestStatus::RESERVED, $reserved->status);
        $this->assertSame(BedStatus::RESERVED, $this->bed->fresh()->status);
        $this->assertDatabaseHas('bed_reservations', [
            'admission_request_id' => $request->id,
            'bed_id' => $this->bed->id,
            'status' => BedReservationStatus::ACTIVE->value,
        ]);
    }

    public function test_request_conversion_fulfils_reservation_and_records_admitted_location(): void
    {
        $this->actingAs($this->user);
        $request = app(AdmissionRequestService::class)->reserveBed($this->admissionRequest(), $this->bed, $this->user);

        $admission = app(AdmissionRequestService::class)->convertToAdmission($request, [
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
        ], $this->user);

        $this->assertSame(BedStatus::OCCUPIED, $this->bed->fresh()->status);
        $this->assertDatabaseHas('bed_reservations', [
            'admission_request_id' => $request->id,
            'admission_id' => $admission->id,
            'status' => BedReservationStatus::FULFILLED->value,
        ]);
        $this->assertDatabaseHas('admission_location_histories', [
            'admission_id' => $admission->id,
            'event_type' => AdmissionLocationEvent::ADMITTED->value,
            'to_bed_id' => $this->bed->id,
        ]);
    }

    public function test_admission_transfer_moves_beds_and_records_location_history(): void
    {
        $admission = $this->admitDirectly();

        app(BedWorkflowService::class)->transferAdmission($admission, $this->secondBed, $this->user, 'Step down');

        $this->assertSame(BedStatus::AVAILABLE, $this->bed->fresh()->status);
        $this->assertSame(BedStatus::OCCUPIED, $this->secondBed->fresh()->status);
        $this->assertSame($this->secondBed->id, $admission->fresh()->bed_id);
        $this->assertDatabaseHas('admission_location_histories', [
            'admission_id' => $admission->id,
            'event_type' => AdmissionLocationEvent::BED_TRANSFERRED->value,
            'from_bed_id' => $this->bed->id,
            'to_bed_id' => $this->secondBed->id,
        ]);
    }

    public function test_transfer_rejects_unavailable_destination_bed(): void
    {
        $admission = $this->admitDirectly();
        $this->secondBed->markOccupied($this->user->id, 'Test occupancy');

        $this->expectException(ValidationException::class);
        app(BedWorkflowService::class)->transferAdmission($admission, $this->secondBed, $this->user, 'Unsafe transfer');
    }

    public function test_discharge_releases_bed_and_records_discharge_location_history(): void
    {
        Event::fake([PatientDischarged::class]);
        $admission = $this->admitDirectly();

        app(AdmissionService::class)->discharge($admission, [
            'discharge_summary' => 'Stable',
            'discharge_instructions' => 'Review',
        ]);

        $this->assertSame(BedStatus::AVAILABLE, $this->bed->fresh()->status);
        $this->assertDatabaseHas('admission_location_histories', [
            'admission_id' => $admission->id,
            'event_type' => AdmissionLocationEvent::DISCHARGED->value,
            'from_bed_id' => $this->bed->id,
        ]);
    }

    public function test_user_without_transfer_permission_cannot_transfer_from_route(): void
    {
        $admission = $this->admitDirectly();
        $viewer = User::factory()->create();
        $role = Role::findOrCreate('Bed Workflow Viewer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('ward.view', 'web'));
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->post(route('admin.admissions.transfer-bed', $admission), [
                'bed_id' => $this->secondBed->id,
                'reason' => 'No permission',
            ])
            ->assertForbidden();
    }

    private function admitDirectly(): Admission
    {
        $this->actingAs($this->user);

        return app(AdmissionService::class)->admit([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
            'admitting_diagnosis' => 'Observation',
        ]);
    }

    private function admissionRequest(): AdmissionRequest
    {
        return AdmissionRequest::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT,
            'status' => AdmissionRequestStatus::ACCEPTED,
            'requested_by' => $this->user->id,
            'accepted_by' => $this->user->id,
            'requested_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    private function bed(string $number): Bed
    {
        return Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => $number,
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }
}
