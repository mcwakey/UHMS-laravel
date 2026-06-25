<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\AppointmentService;
use App\Services\VisitService;
use App\Services\VisitStatisticsService;
use App\Services\VisitStatusFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VisitStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->actingAs($this->user);
    }

    private function newPatient(): Patient
    {
        return Patient::factory()->create(['registered_by' => $this->user->id]);
    }

    private function flow(): VisitStatusFlowService
    {
        return app(VisitStatusFlowService::class);
    }

    // ── 1. Direct first-ever ─────────────────────────────────────────────

    public function test_direct_first_ever_visit_is_created(): void
    {
        $patient = $this->newPatient();

        $visit = app(VisitService::class)->create([
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'New patient',
        ]);

        $this->assertSame('direct', $visit->visit_source);
        $this->assertSame('first_ever', $visit->attendance_class);
        $this->assertSame(VisitStatus::CREATED, $visit->status);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => null,
            'to_status' => VisitStatus::CREATED->value,
        ]);
    }

    // ── 2. Direct first attendance of the year ───────────────────────────

    public function test_direct_first_attendance_of_year_is_registered(): void
    {
        $patient = $this->newPatient();
        // A prior attendance from a previous calendar year.
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_date' => today()->copy()->subYear(),
            'status' => VisitStatus::COMPLETED,
        ]);

        $class = $this->flow()->determineAttendanceClass($patient, today());

        $this->assertSame('first_attendance_of_year', $class);
        $this->assertSame(VisitStatus::REGISTERED, $this->flow()->resolveInitialStatus('direct', $class));
    }

    // ── 3. Direct subsequent attendance ──────────────────────────────────

    public function test_direct_subsequent_attendance_is_walked_in(): void
    {
        $patient = $this->newPatient();
        // A prior attendance earlier in the SAME calendar year.
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_date' => today()->copy()->startOfYear(),
            'status' => VisitStatus::COMPLETED,
        ]);

        $class = $this->flow()->determineAttendanceClass($patient, today());

        $this->assertSame('subsequent_attendance', $class);
        // Direct visits use WALKED_IN (not CHECKED_IN).
        $this->assertSame(VisitStatus::WALKED_IN, $this->flow()->resolveInitialStatus('direct', $class));
    }

    // ── 4. Appointment check-in walks scheduled → checked_in → queued ─────

    public function test_appointment_check_in_sets_source_and_walks_chain(): void
    {
        $patient = $this->newPatient();

        $appointment = Appointment::create([
            'appointment_number' => Appointment::generateAppointmentNumber(),
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'visit_type' => VisitType::OUTPATIENT,
            'priority' => 'normal',
            'status' => AppointmentStatus::CONFIRMED,
            'created_by' => $this->user->id,
        ]);

        $appointment = app(AppointmentService::class)->checkIn($appointment);
        $visit = $appointment->visit()->first();

        $this->assertNotNull($visit);
        $this->assertSame('appointment', $visit->visit_source);
        // No consultation service attached → stops at checked_in (not queued).
        $this->assertSame(VisitStatus::CHECKED_IN, $visit->status);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => null,
            'to_status' => VisitStatus::SCHEDULED->value,
        ]);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => VisitStatus::SCHEDULED->value,
            'to_status' => VisitStatus::CHECKED_IN->value,
        ]);
    }

    // ── 5. Invalid transition rejected unless privileged ─────────────────

    public function test_invalid_transition_is_rejected_without_override_permission(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->newPatient()->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::CREATED,
        ]);

        // CREATED → COMPLETED is not an allowed edge.
        $this->expectException(\InvalidArgumentException::class);
        $this->flow()->transition($visit, VisitStatus::COMPLETED, force: true);
    }

    public function test_invalid_transition_allowed_with_override_permission(): void
    {
        $role = Role::findOrCreate('Admin', 'web');
        $perm = Permission::findOrCreate('visits.override_transition', 'web');
        $role->givePermissionTo($perm);

        $privileged = User::factory()->create(['department_id' => $this->department->id]);
        $privileged->assignRole($role);
        $this->actingAs($privileged);

        $visit = Visit::factory()->create([
            'patient_id' => $this->newPatient()->id,
            'created_by' => $privileged->id,
            'status' => VisitStatus::CREATED,
        ]);

        $visit = $this->flow()->transition($visit, VisitStatus::COMPLETED, force: true);

        $this->assertSame(VisitStatus::COMPLETED, $visit->status);
    }

    // ── 6. Statistics by source / attendance_class / status ──────────────

    public function test_attendance_report_counts_by_source_class_and_status(): void
    {
        $p1 = $this->newPatient();
        $p2 = $this->newPatient();

        Visit::factory()->create([
            'patient_id' => $p1->id,
            'created_by' => $this->user->id,
            'visit_date' => today(),
            'visit_source' => 'direct',
            'attendance_class' => 'first_ever',
            'status' => VisitStatus::CREATED,
        ]);
        Visit::factory()->create([
            'patient_id' => $p2->id,
            'created_by' => $this->user->id,
            'visit_date' => today(),
            'visit_source' => 'appointment',
            'attendance_class' => 'subsequent_attendance',
            'status' => VisitStatus::CHECKED_IN,
        ]);

        $report = app(VisitStatisticsService::class)->attendanceReport([
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $this->assertSame(2, $report['total']);
        $this->assertSame(1, $report['direct']);
        $this->assertSame(1, $report['appointment']);
        $this->assertSame(1, $report['first_ever']);
        $this->assertSame(1, $report['subsequent_attendance']);
        $this->assertSame(1, $report['by_status'][VisitStatus::CREATED->value] ?? 0);
        $this->assertSame(1, $report['by_source']['appointment'] ?? 0);
    }
}
