<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentIndexActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $doctor;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);

        $doctorRole = Role::findOrCreate('Doctor', 'web');
        $workflowRole = Role::findOrCreate('Workflow Tester', 'web');

        foreach ([
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'patients.view',
            'visits.view',
            'visits.create',
        ] as $permission) {
            $workflowRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($workflowRole);

        $this->doctor = User::factory()->create(['department_id' => $this->department->id]);
        $this->doctor->assignRole($doctorRole);
    }

    public function test_index_displays_async_hooks_for_confirm_and_check_in_actions(): void
    {
        $scheduledAppointment = $this->createAppointment(AppointmentStatus::SCHEDULED);
        $confirmedAppointment = $this->createAppointment(AppointmentStatus::CONFIRMED);
        $checkedInAppointment = $this->createAppointment(AppointmentStatus::CHECKED_IN);

        $response = $this->actingAs($this->user)->get(route('admin.appointments.index'));

        $response->assertOk()
            ->assertSee('appointmentIndexActionFeedback', false)
            ->assertSee("action=\"".route('admin.appointments.transition', $scheduledAppointment)."\" class=\"js-appointment-action-form\" data-follow-up=\"appointment\"", false)
            ->assertSee("action=\"".route('admin.appointments.check-in', $confirmedAppointment)."\" class=\"js-appointment-action-form\" data-follow-up=\"visit\"", false)
            ->assertSee("const feedback = document.getElementById('appointmentIndexActionFeedback');", false)
            ->assertSee('window.location.assign(followUp);', false)
            ->assertDontSee(route('admin.appointments.check-in', $scheduledAppointment), false)
            ->assertDontSee(route('admin.appointments.transition', $confirmedAppointment), false)
            ->assertDontSee(route('admin.appointments.transition', $checkedInAppointment), false)
            ->assertDontSee(route('admin.appointments.check-in', $checkedInAppointment), false);
    }

    public function test_index_actions_return_json_payload_needed_by_async_handler(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::SCHEDULED);

        $transitionResponse = $this->actingAs($this->user)->patchJson(route('admin.appointments.transition', $appointment), [
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        $appointment->refresh();

        $transitionResponse->assertOk()
            ->assertJsonPath('appointment_id', $appointment->id)
            ->assertJsonPath('appointment_status', AppointmentStatus::CONFIRMED->value)
            ->assertJsonPath('redirect_url', route('admin.appointments.show', $appointment));

        $checkInResponse = $this->actingAs($this->user)->postJson(route('admin.appointments.check-in', $appointment));

        $appointment->refresh();
        $visit = $appointment->visit()->first();

        $this->assertNotNull($visit);

        $checkInResponse->assertOk()
            ->assertJsonPath('appointment_id', $appointment->id)
            ->assertJsonPath('appointment_status', AppointmentStatus::CHECKED_IN->value)
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('redirect_url', route('admin.appointments.show', $appointment))
            ->assertJsonPath('visit_redirect_url', route('admin.visits.show', $visit));
    }

    private function createAppointment(AppointmentStatus $status): Appointment
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        return Appointment::create([
            'appointment_number' => Appointment::generateAppointmentNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'visit_type' => VisitType::OUTPATIENT,
            'priority' => 'normal',
            'status' => $status,
            'created_by' => $this->user->id,
        ]);
    }
}