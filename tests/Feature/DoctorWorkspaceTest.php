<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DoctorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_doctor_routes_use_the_doctor_namespace_prefix(): void
    {
        $this->assertSame('http://localhost/doctor', route('doctor.dashboard'));
        $this->assertSame('http://localhost/doctor/dashboard', route('doctor.dashboard.redirect'));
        $this->assertSame('http://localhost/doctor/consultations', route('doctor.consultations.index'));
        $this->assertSame('http://localhost/doctor/visits', route('doctor.visits.index'));
        $this->assertSame('http://localhost/doctor/appointments', route('doctor.appointments.index'));
        $this->assertSame('http://localhost/doctor/lab/requests', route('doctor.lab.requests.index'));

        $middleware = Route::getRoutes()->getByName('doctor.consultations.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('can:consultations.view', $middleware);
    }

    public function test_doctor_dashboard_renders_at_workspace_root_and_dashboard_redirects(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $this->give($doctor, ['consultation.access', 'consultations.view', 'appointments.view']);

        $this->actingAs($doctor)
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee('doctor\\/consultations', false)
            ->assertSee('doctor\\/appointments', false);

        $this->actingAs($doctor)
            ->get(route('doctor.dashboard.redirect'))
            ->assertRedirect(route('doctor.dashboard'));
    }

    public function test_consultation_sidebar_uses_doctor_workspace_routes(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $this->give($doctor, [
            'consultation.access',
            'consultation.view_patient',
            'appointments.view',
            'visits.view',
            'consultation.create',
            'consultations.view',
            'consultation.prescribe',
            'consultation.request_lab',
            'consultation.request_procedure',
            'procedure_catalogue.view',
            'investigation.catalogue.view',
            'reports.view',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($doctor, 'doctor.consultations.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('doctor.dashboard', $routes);
        $this->assertContains('doctor.patients.index', $routes);
        $this->assertContains('doctor.appointments.index', $routes);
        $this->assertContains('doctor.visits.index', $routes);
        $this->assertContains('doctor.consultations.index', $routes);
        $this->assertContains('doctor.lab.requests.index', $routes);
        $this->assertContains('doctor.theatre.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'doctor.consultations.index')['active']);
    }

    public function test_generic_admin_pages_redirect_to_doctor_workspace_for_consultation_users(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $this->give($doctor, ['appointments.view', 'consultations.view']);

        $this->actingAs($doctor)
            ->get(route('admin.appointments.index'))
            ->assertRedirect(route('doctor.appointments.index'));

        $this->actingAs($doctor)
            ->get(route('admin.consultations.index'))
            ->assertRedirect(route('doctor.consultations.index'));
    }

    public function test_doctor_appointments_are_today_scoped_and_can_filter_to_my_patients(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        $otherDoctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $otherDoctor->assignRole('Doctor');
        $this->give($doctor, ['appointments.view', 'consultation.access']);

        $myTodayAppointment = $this->appointmentFor($doctor, $department, today()->toDateString(), '09:00');
        $otherTodayAppointment = $this->appointmentFor($otherDoctor, $department, today()->toDateString(), '10:00');
        $futureAppointment = $this->appointmentFor($doctor, $department, today()->addDay()->toDateString(), '11:00');

        $response = $this->actingAs($doctor)
            ->get(route('doctor.appointments.index'));

        $response->assertOk()
            ->assertSee(__('doctor.breadcrumbs.doctor'))
            ->assertSee(__('doctor.breadcrumbs.appointments'))
            ->assertSee(__('appointments.my_patients_only'))
            ->assertSee($myTodayAppointment->appointment_number)
            ->assertSee($otherTodayAppointment->appointment_number)
            ->assertDontSee($futureAppointment->appointment_number);

        $html = $response->getContent();
        $this->assertStringNotContainsString('name="doctor_id"', $html);
        $this->assertStringNotContainsString('name="department_id"', $html);

        $this->actingAs($doctor)
            ->get(route('doctor.appointments.index', ['my_patients_only' => 1]))
            ->assertOk()
            ->assertSee($myTodayAppointment->appointment_number)
            ->assertDontSee($otherTodayAppointment->appointment_number)
            ->assertDontSee($futureAppointment->appointment_number);
    }

    /** @param list<string> $permissions */
    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('Doctor Workspace Tester', 'web');
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }

    private function appointmentFor(User $doctor, Department $department, string $date, string $startTime): Appointment
    {
        $patient = Patient::factory()->create();

        return Appointment::create([
            'appointment_number' => Appointment::generateAppointmentNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => \Carbon\Carbon::parse($startTime)->addMinutes(30)->format('H:i'),
            'visit_type' => VisitType::OUTPATIENT,
            'priority' => 'normal',
            'status' => AppointmentStatus::SCHEDULED,
            'created_by' => $doctor->id,
        ]);
    }
}
