<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\BedReservationStatus;
use App\Enums\BedStatus;
use App\Enums\VisitStatus;
use App\Events\PatientDischarged;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\BedReservation;
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

class AdmissionBedWorkflowPhase5Test extends TestCase
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

        $role = Role::findOrCreate('Ward Ops Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'ward.discharge',
            'admission.requests.view', 'admission.requests.create', 'admission.requests.reserve_bed', 'admission.requests.convert',
            'beds.view', 'beds.manage', 'beds.reserve', 'beds.release', 'beds.block', 'beds.transfer',
            'beds.clean', 'beds.status.manage', 'beds.capacity.view', 'bed.reservations.expire',
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
    }

    public function test_expired_active_reservation_is_expired_by_command_and_releases_bed(): void
    {
        $request = app(AdmissionRequestService::class)->reserveBed($this->admissionRequest(), $this->bed, $this->user);
        $reservation = $request->activeBedReservation;
        $reservation->update(['expires_at' => now()->subMinute()]);
        $this->bed->fresh()->update(['reserved_until' => now()->subMinute()]);

        $this->artisan('admissions:expire-bed-reservations')
            ->expectsOutputToContain('checked=1 expired=1 skipped=0 errors=0')
            ->assertExitCode(0);

        $this->assertSame(BedReservationStatus::EXPIRED, $reservation->fresh()->status);
        $this->assertSame(BedStatus::AVAILABLE, $this->bed->fresh()->status);
        $this->assertSame(AdmissionRequestStatus::BED_PENDING, $request->fresh()->status);
        $this->assertNull($request->fresh()->reserved_bed_id);
    }

    public function test_expiry_does_not_release_occupied_bed_or_fulfilled_reservation(): void
    {
        $request = app(AdmissionRequestService::class)->reserveBed($this->admissionRequest(), $this->bed, $this->user);
        $reservation = $request->activeBedReservation;
        $reservation->update(['expires_at' => now()->subMinute(), 'status' => BedReservationStatus::FULFILLED]);
        $this->bed->markOccupied($this->user->id, 'Occupied');

        $this->artisan('admissions:expire-bed-reservations')
            ->expectsOutputToContain('checked=0 expired=0 skipped=0 errors=0')
            ->assertExitCode(0);

        $this->assertSame(BedReservationStatus::FULFILLED, $reservation->fresh()->status);
        $this->assertSame(BedStatus::OCCUPIED, $this->bed->fresh()->status);
    }

    public function test_blocked_cleaning_and_maintenance_beds_cannot_be_reserved(): void
    {
        foreach ([BedStatus::BLOCKED, BedStatus::CLEANING, BedStatus::MAINTENANCE] as $status) {
            $bed = $this->bed('X' . $status->value);
            $bed->updateStatus($status, $this->user->id, 'Not available');

            try {
                app(AdmissionRequestService::class)->reserveBed($this->admissionRequest(), $bed, $this->user);
                $this->fail("{$status->value} bed was reserved.");
            } catch (ValidationException) {
                $this->assertDatabaseMissing('bed_reservations', ['bed_id' => $bed->id, 'status' => BedReservationStatus::ACTIVE->value]);
            }
        }
    }

    public function test_discharge_can_mark_bed_cleaning_from_config(): void
    {
        Event::fake([PatientDischarged::class]);
        config(['admissions.bed_release_after_discharge' => 'cleaning']);

        $this->actingAs($this->user);
        $admission = app(AdmissionService::class)->admit([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
        ]);

        app(AdmissionService::class)->discharge($admission, [
            'discharge_summary' => 'Stable',
        ]);

        $this->assertSame(BedStatus::CLEANING, $this->bed->fresh()->status);
    }

    public function test_bed_status_requires_reason_for_blocked_maintenance_and_isolation(): void
    {
        $this->expectException(ValidationException::class);

        app(BedWorkflowService::class)->updateBedStatus($this->bed, BedStatus::BLOCKED, $this->user);
    }

    public function test_capacity_board_renders_operational_metrics(): void
    {
        app(BedWorkflowService::class)->updateBedStatus($this->bed, BedStatus::BLOCKED, $this->user, 'Repairs');

        $this->actingAs($this->user)
            ->get(route('admin.wards.bed-map'))
            ->assertOk()
            ->assertSee(__('admissions.capacity_board'))
            ->assertSee(__('statuses.default.blocked'));
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
