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
use Spatie\Permission\PermissionRegistrar;
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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);

        $doctorRole = Role::findOrCreate('Doctor', 'web');
        $workflowRole = Role::findOrCreate('Workflow Tester', 'web');

        foreach ([
            'appointments.view',
            'appointments.create',
            'appointments.checkin',
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
        $page = $this->inertiaPage($response->getContent());
        $html = $page['props']['html'];
        $scripts = $this->legacyScripts($page);

        $response->assertOk()
            ->assertSee('Legacy\\/BladePage', false);

        $this->assertStringContainsString('appointmentIndexActionFeedback', $html);
        $this->assertStringContainsString('action="'.route('admin.appointments.transition', $scheduledAppointment).'" class="js-appointment-action-form" data-follow-up="appointment"', $html);
        $this->assertStringContainsString('action="'.route('admin.appointments.check-in', $confirmedAppointment).'" class="js-appointment-action-form" data-follow-up="visit"', $html);
        $this->assertStringContainsString("const feedback = document.getElementById('appointmentIndexActionFeedback');", $scripts);
        $this->assertStringNotContainsString('window.location.assign(followUp);', $scripts);
        $this->assertStringNotContainsString(route('admin.appointments.check-in', $scheduledAppointment), $html);
        $this->assertStringNotContainsString(route('admin.appointments.transition', $confirmedAppointment), $html);
        $this->assertStringNotContainsString(route('admin.appointments.transition', $checkedInAppointment), $html);
        $this->assertStringNotContainsString(route('admin.appointments.check-in', $checkedInAppointment), $html);
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

    public function test_check_in_permission_is_independent_from_appointment_creation(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);
        $creator = User::factory()->create(['department_id' => $this->department->id]);
        $creator->givePermissionTo([
            Permission::findOrCreate('appointments.view', 'web'),
            Permission::findOrCreate('appointments.create', 'web'),
        ]);

        $creatorResponse = $this->actingAs($creator)->get(route('admin.appointments.index'));
        $creatorHtml = $this->inertiaPage($creatorResponse->getContent())['props']['html'];

        $creatorResponse->assertOk();
        $this->assertStringNotContainsString(route('admin.appointments.check-in', $appointment), $creatorHtml);
        $this->actingAs($creator)
            ->postJson(route('admin.appointments.check-in', $appointment))
            ->assertForbidden();

        $checkinUser = User::factory()->create(['department_id' => $this->department->id]);
        $checkinUser->givePermissionTo([
            Permission::findOrCreate('appointments.view', 'web'),
            Permission::findOrCreate('appointments.checkin', 'web'),
        ]);

        $checkinResponse = $this->actingAs($checkinUser)->get(route('admin.appointments.index'));
        $checkinHtml = $this->inertiaPage($checkinResponse->getContent())['props']['html'];

        $checkinResponse->assertOk();
        $this->assertStringContainsString(route('admin.appointments.check-in', $appointment), $checkinHtml);
        $this->actingAs($checkinUser)
            ->postJson(route('admin.appointments.check-in', $appointment))
            ->assertOk();
    }

    public function test_create_uses_appointment_patient_search_without_visit_view_permission(): void
    {
        $appointmentUser = User::factory()->create(['department_id' => $this->department->id]);
        $appointmentUser->givePermissionTo([
            Permission::findOrCreate('appointments.view', 'web'),
            Permission::findOrCreate('appointments.create', 'web'),
        ]);

        Patient::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone' => '0241234567',
            'email' => 'ama.mensah@example.test',
            'registered_by' => $appointmentUser->id,
        ]);

        $response = $this->actingAs($appointmentUser)->get(route('admin.appointments.create'));
        $page = $this->inertiaPage($response->getContent());
        $html = $page['props']['html'];
        $scripts = $this->legacyScripts($page);

        $response->assertOk();
        $this->assertStringContainsString(route('admin.appointments.patient-search'), $scripts);
        $this->assertStringContainsString(route('admin.appointments.patient-insurances'), $scripts);
        $this->assertStringContainsString(route('admin.appointments.department-services'), $scripts);
        $this->assertStringContainsString(route('admin.appointments.services-for-doctor'), $scripts);
        $this->assertStringContainsString(route('admin.appointments.doctors-for-services'), $scripts);
        $this->assertStringNotContainsString(route('admin.visits.patient-search'), $scripts);
        $this->assertStringNotContainsString(route('admin.visits.patient-insurances'), $scripts);
        $this->assertStringNotContainsString(route('admin.visits.department-services'), $scripts);
        $this->assertStringNotContainsString(route('admin.visits.services-for-doctor'), $scripts);
        $this->assertStringNotContainsString(route('admin.visits.doctors-for-services'), $scripts);
        $this->assertStringContainsString(__('appointments.search_patient'), $html);

        $this->actingAs($appointmentUser)
            ->getJson(route('admin.appointments.patient-search', ['q' => 'Ama']))
            ->assertOk()
            ->assertJsonPath('0.phone', '024****567')
            ->assertJsonPath('0.email', 'am****@example.test')
            ->assertDontSee('0241234567', false)
            ->assertDontSee('ama.mensah@example.test', false);

        $this->actingAs($appointmentUser)
            ->getJson(route('admin.visits.patient-search', ['q' => 'Ama']))
            ->assertForbidden();

        $this->actingAs($appointmentUser)
            ->getJson(route('admin.appointments.department-services', ['department_id' => $this->department->id]))
            ->assertOk();

        $this->actingAs($appointmentUser)
            ->getJson(route('admin.visits.department-services', ['department_id' => $this->department->id]))
            ->assertForbidden();
    }

    public function test_create_can_open_with_patient_preselected_from_patient_context(): void
    {
        $appointmentUser = User::factory()->create(['department_id' => $this->department->id]);
        $appointmentUser->givePermissionTo([
            Permission::findOrCreate('appointments.view', 'web'),
            Permission::findOrCreate('appointments.create', 'web'),
        ]);
        $patient = Patient::factory()->create([
            'first_name' => 'Akosua',
            'last_name' => 'Mensah',
            'registered_by' => $appointmentUser->id,
        ]);

        $response = $this->actingAs($appointmentUser)
            ->get(route('admin.appointments.create', ['patient_id' => $patient->id]));
        $page = $this->inertiaPage($response->getContent());
        $html = $page['props']['html'];

        $response->assertOk();
        $this->assertStringContainsString('value="'.$patient->id.'"', $html);
        $this->assertStringContainsString($patient->patient_number.' - '.$patient->full_name, $html);
        $this->assertStringNotContainsString('id="patientInfo" class="d-none"', $html);
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

    private function inertiaPage(string $content): array
    {
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $content, $matches);

        return json_decode($matches[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
    }

    private function legacyScripts(array $page): string
    {
        if (! empty($page['props']['scripts'])) {
            return $page['props']['scripts'];
        }

        return base64_decode($page['props']['scriptsEncoded'] ?? '', true) ?: '';
    }
}
