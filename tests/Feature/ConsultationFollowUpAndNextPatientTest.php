<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\ServiceCatalog;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ConsultationFollowUpAndNextPatientTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Department $department;

    private ServiceCatalog $service;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'General Consultation',
            'type' => DepartmentType::CONSULTATION->value,
        ]);

        $specialty = Specialty::create([
            'name' => 'General Practice',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->service = $this->makeService('General Consultation Service');

        $role = Role::findOrCreate('Consultation Workflow Tester', 'web');
        foreach ([
            'patients.view',
            'visits.view',
            'visits.preview',
            'consultations.view',
            'consultations.create',
            'consultation.followup.create',
            'consultation.followup.update',
            'consultation.followup.cancel',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $doctorRole = Role::findOrCreate('Doctor', 'web');
        $this->doctor = User::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'department_id' => $this->department->id,
        ]);
        $this->doctor->assignRole($role, $doctorRole);
        $this->doctor->specialties()->attach($specialty->id);
    }

    public function test_doctor_can_set_follow_up_appointment_from_consultation(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $response = $this->actingAs($this->doctor)->post(route('admin.consultations.routes.follow-up.store', [$visit, $route]), [
            'appointment_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:15',
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'priority' => Priority::NORMAL->value,
            'reason' => 'Review blood pressure and lab results',
            'notes' => 'Bring home BP chart',
            'notify_patient' => '1',
        ]);

        $response->assertRedirect(route('admin.consultations.routes.show', [$visit, $route]));

        $appointment = Appointment::firstOrFail();
        $this->assertSame($visit->patient_id, $appointment->patient_id);
        $this->assertSame($visit->id, $appointment->visit_id);
        $this->assertSame($route->id, $appointment->consultation_route_id);
        $this->assertNotNull($appointment->medical_record_id);
        $this->assertSame([$this->service->id], $appointment->services->pluck('id')->all());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'CONSULTATION',
            'event' => 'FOLLOW_UP_APPOINTMENT_CREATED',
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
        ]);
        $activity = Activity::where('event', 'FOLLOW_UP_APPOINTMENT_CREATED')->firstOrFail();
        $this->assertSame($appointment->id, $activity->properties->get('appointment_id'));
    }

    public function test_unauthorized_user_cannot_create_consultation_follow_up(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        $role = Role::findOrCreate('Consultation Viewer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $user->assignRole($role);

        $this->actingAs($user)->post(route('admin.consultations.routes.follow-up.store', [$visit, $route]), [
            'appointment_date' => now()->addWeek()->toDateString(),
            'department_id' => $this->department->id,
            'reason' => 'Review results',
        ])->assertForbidden();
    }

    public function test_follow_up_appears_in_patient_profile_and_visit_preview(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();

        $this->actingAs($this->doctor)->post(route('admin.consultations.routes.follow-up.store', [$visit, $route]), [
            'appointment_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'reason' => 'Review treatment response',
        ])->assertRedirect();

        $this->actingAs($this->doctor)
            ->get(route('admin.patients.show', $visit->patient))
            ->assertOk()
            ->assertSee('Upcoming Follow-up Appointments')
            ->assertSee('Review treatment response');

        $this->actingAs($this->doctor)
            ->get(route('admin.visits.preview', $visit))
            ->assertOk()
            ->assertSee('Next Appointment')
            ->assertSee('Review treatment response');
    }

    public function test_consultation_page_shows_next_patient_in_same_department_queue(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        [$nextVisit] = $this->makeQueuedVisit('Next', 'Patient', 1);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertSee('followUpAppointmentModal')
            ->assertSee('Next Appointment')
            ->assertSee('Next Patient in Line')
            ->assertSee($nextVisit->patient->full_name)
            ->assertSee($nextVisit->visit_number)
            ->assertSee('Queue #1');
    }

    public function test_next_patient_uses_waiting_outpatient_queue_entry_only(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        [$noQueueVisit] = $this->makeQueuedVisit('No', 'Queue', 1, false);
        [$inpatientVisit] = $this->makeQueuedVisit('Inpatient', 'Queue', 1, true, VisitType::INPATIENT, VisitStatus::ADMITTED);
        [$nextVisit, $nextRoute] = $this->makeQueuedVisit('Outpatient', 'Queue', 2);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertSee('Next Patient in Line')
            ->assertSee($nextVisit->patient->full_name)
            ->assertSee($nextVisit->visit_number)
            ->assertSee('Queue #2')
            ->assertDontSee($noQueueVisit->patient->full_name)
            ->assertDontSee($inpatientVisit->patient->full_name);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.next-patient.open', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$nextVisit, $nextRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $nextRoute->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $nextVisit->fresh()->status);
    }

    public function test_consultation_list_orders_waiting_queue_by_queue_number(): void
    {
        [$firstVisit] = $this->makeQueuedVisit('First', 'Patient', 1);
        [$secondVisit] = $this->makeQueuedVisit('Second', 'Patient', 2);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $firstVisit->patient->full_name,
                $secondVisit->patient->full_name,
            ]);
    }

    public function test_open_next_patient_activates_the_next_pending_route(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        [$nextVisit, $nextRoute] = $this->makeQueuedVisit('Kojo', 'Addo', 1);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.next-patient.open', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$nextVisit, $nextRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $nextRoute->fresh()->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $nextVisit->fresh()->status);
    }

    public function test_open_next_patient_can_use_active_route_that_is_still_waiting_in_queue(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        [$nextVisit, $nextRoute] = $this->makeQueuedVisit('Akua', 'Boateng', 1);
        $nextRoute->update([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertSee($nextVisit->patient->full_name)
            ->assertSee('Queue #1');

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.next-patient.open', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$nextVisit, $nextRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $nextRoute->fresh()->status);
        $this->assertSame(VisitStatus::CONSULTING, $nextVisit->fresh()->status);
    }

    public function test_complete_and_open_next_patient_completes_current_route_then_opens_next(): void
    {
        [$visit, $route] = $this->makeConsultingVisit();
        [$nextVisit, $nextRoute] = $this->makeQueuedVisit('Efua', 'Owusu', 1);

        $this->actingAs($this->doctor)
            ->post(route('admin.consultations.routes.next-patient.complete-open', [$visit, $route]))
            ->assertRedirect(route('admin.consultations.routes.show', [$nextVisit, $nextRoute]));

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $route->fresh()->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $nextRoute->fresh()->status);
    }

    private function makeConsultingVisit(): array
    {
        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);

        $route = VisitConsultationRoute::create([
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
        ]);

        VisitConsultationRouteService::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'service_id' => $this->service->id,
        ]);

        return [$visit, $route];
    }

    private function makeQueuedVisit(
        string $firstName,
        string $lastName,
        int $queueNumber,
        bool $withQueueEntry = true,
        VisitType $visitType = VisitType::OUTPATIENT,
        VisitStatus $status = VisitStatus::WAITING_CONSULTATION,
    ): array
    {
        $patient = Patient::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'registered_by' => $this->doctor->id,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'visit_type' => $visitType,
            'status' => $status,
            'current_department_id' => $this->department->id,
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => null,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $this->doctor->id,
        ]);
        VisitConsultationRouteService::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'service_id' => $this->service->id,
        ]);
        if ($withQueueEntry) {
            QueueEntry::create([
                'visit_id' => $visit->id,
                'department_id' => $this->department->id,
                'queue_number' => $queueNumber,
                'priority' => Priority::NORMAL,
                'status' => 'waiting',
            ]);
        }

        return [$visit, $route];
    }

    private function makeService(string $name): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => 'CONS'.random_int(1000, 9999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 0,
            'is_active' => true,
            'is_billable' => false,
        ]);
    }
}
