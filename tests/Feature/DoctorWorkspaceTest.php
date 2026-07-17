<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\AppointmentStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\Priority;
use App\Enums\ProcedureStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
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
        $this->assertSame('http://localhost/doctor/appointments/calendar', route('doctor.appointments.calendar'));
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
        $otherDepartment = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        $otherDoctor = User::factory()->create(['department_id' => $department->id]);
        $outsideDoctor = User::factory()->create(['department_id' => $otherDepartment->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $otherDoctor->assignRole('Doctor');
        $outsideDoctor->assignRole('Doctor');
        $this->give($doctor, ['appointments.view', 'consultation.access']);

        $myTodayAppointment = $this->appointmentFor($doctor, $department, today()->toDateString(), '09:00');
        $otherTodayAppointment = $this->appointmentFor($otherDoctor, $department, today()->toDateString(), '10:00');
        $outsideDepartmentAppointment = $this->appointmentFor($outsideDoctor, $otherDepartment, today()->toDateString(), '10:30');
        $futureAppointment = $this->appointmentFor($doctor, $department, today()->addDay()->toDateString(), '11:00');

        $response = $this->actingAs($doctor)
            ->get(route('doctor.appointments.index'));

        $response->assertOk()
            ->assertSee(__('doctor.breadcrumbs.doctor'))
            ->assertSee(__('doctor.breadcrumbs.appointments'))
            ->assertSee(__('appointments.my_patients_only'))
            ->assertSee($myTodayAppointment->appointment_number)
            ->assertSee($otherTodayAppointment->appointment_number)
            ->assertDontSee($outsideDepartmentAppointment->appointment_number)
            ->assertDontSee($futureAppointment->appointment_number);

        $html = $response->getContent();
        $this->assertStringNotContainsString('name="doctor_id"', $html);
        $this->assertStringNotContainsString('name="department_id"', $html);

        $this->actingAs($doctor)
            ->get(route('doctor.appointments.index', ['my_patients_only' => 1]))
            ->assertOk()
            ->assertSee($myTodayAppointment->appointment_number)
            ->assertDontSee($otherTodayAppointment->appointment_number)
            ->assertDontSee($outsideDepartmentAppointment->appointment_number)
            ->assertDontSee($futureAppointment->appointment_number);

        $calendarResponse = $this->actingAs($doctor)
            ->get(route('doctor.appointments.calendar', ['my_patients_only' => 1]));

        $calendarResponse->assertOk()
            ->assertSee(__('appointments.my_patients_only'))
            ->assertSee($myTodayAppointment->patient->full_name)
            ->assertDontSee($otherTodayAppointment->patient->full_name)
            ->assertDontSee($outsideDepartmentAppointment->patient->full_name);

        $calendarHtml = $calendarResponse->getContent();
        $this->assertStringNotContainsString('name="doctor_id"', $calendarHtml);
        $this->assertStringNotContainsString('name="department_id"', $calendarHtml);
    }

    public function test_doctor_visit_and_consultation_lists_are_scoped_to_workspace_department(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $otherDepartment = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        $otherDoctor = User::factory()->create(['department_id' => $department->id]);
        $outsideDoctor = User::factory()->create(['department_id' => $otherDepartment->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $otherDoctor->assignRole('Doctor');
        $outsideDoctor->assignRole('Doctor');
        $this->give($doctor, ['visits.view', 'consultations.view', 'consultation.access']);

        $myVisit = $this->visitFor($doctor, $department);
        $sameDepartmentVisit = $this->visitFor($otherDoctor, $department);
        $outsideDepartmentVisit = $this->visitFor($outsideDoctor, $otherDepartment);

        $this->actingAs($doctor)
            ->get(route('doctor.visits.index'))
            ->assertOk()
            ->assertSee(__('visits.description'))
            ->assertSee(__('visits.my_patients_only'))
            ->assertSee($myVisit->visit_number)
            ->assertSee($sameDepartmentVisit->visit_number)
            ->assertDontSee($outsideDepartmentVisit->visit_number);

        $this->actingAs($doctor)
            ->get(route('doctor.visits.index', ['my_patients_only' => 1]))
            ->assertOk()
            ->assertSee($myVisit->visit_number)
            ->assertDontSee($sameDepartmentVisit->visit_number)
            ->assertDontSee($outsideDepartmentVisit->visit_number);

        $this->actingAs($doctor)
            ->get(route('doctor.consultations.index'))
            ->assertOk()
            ->assertSee(__('consultations.description'))
            ->assertSee($myVisit->visit_number)
            ->assertSee($sameDepartmentVisit->visit_number)
            ->assertDontSee($outsideDepartmentVisit->visit_number);
    }

    public function test_doctor_clinical_order_lists_are_scoped_to_logged_in_doctor(): void
    {
        $department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        $procedureDepartment = Department::factory()->create();
        $doctor = User::factory()->create(['department_id' => $department->id]);
        $otherDoctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole('Doctor');
        $otherDoctor->assignRole('Doctor');
        $this->give($doctor, [
            'prescriptions.view',
            'lab.requests.view',
            'procedure.view',
            'consultation.access',
        ]);

        $myVisit = $this->visitFor($doctor, $department);
        $otherVisit = $this->visitFor($otherDoctor, $department);

        $myPrescription = $this->prescriptionFor($doctor, $myVisit, $department);
        $otherPrescription = $this->prescriptionFor($otherDoctor, $otherVisit, $department);
        $myLabRequest = $this->labRequestFor($doctor, $myVisit, $department);
        $otherLabRequest = $this->labRequestFor($otherDoctor, $otherVisit, $department);
        $myProcedure = $this->procedureRequestFor($doctor, $myVisit, $procedureDepartment);
        $otherProcedure = $this->procedureRequestFor($otherDoctor, $otherVisit, $procedureDepartment);

        $this->actingAs($doctor)
            ->get(route('doctor.prescriptions.index'))
            ->assertOk()
            ->assertSee($myPrescription->prescription_number)
            ->assertDontSee($otherPrescription->prescription_number);

        $this->actingAs($doctor)
            ->get(route('doctor.lab.requests.index'))
            ->assertOk()
            ->assertSee($myLabRequest->request_number)
            ->assertDontSee($otherLabRequest->request_number);

        $this->actingAs($doctor)
            ->get(route('doctor.theatre.index'))
            ->assertOk()
            ->assertSee($myProcedure->request_number)
            ->assertDontSee($otherProcedure->request_number);

        $this->actingAs($doctor)
            ->get(route('doctor.prescriptions.show', $otherPrescription))
            ->assertNotFound();

        $this->actingAs($doctor)
            ->get(route('doctor.lab.requests.show', $otherLabRequest))
            ->assertNotFound();

        $this->actingAs($doctor)
            ->get(route('doctor.theatre.show', $otherProcedure))
            ->assertNotFound();
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
        ])->load('patient');
    }

    private function visitFor(User $doctor, Department $department): Visit
    {
        $visit = Visit::factory()->create([
            'patient_id' => Patient::factory(),
            'visit_type' => VisitType::OUTPATIENT,
            'visit_date' => today()->toDateString(),
            'status' => VisitStatus::WAITING,
            'current_department_id' => $department->id,
            'created_by' => $doctor->id,
        ]);

        VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'status' => VisitConsultationRoute::STATUS_PENDING,
            'routed_by' => $doctor->id,
        ]);

        return $visit;
    }

    private function prescriptionFor(User $doctor, Visit $visit, Department $department): Prescription
    {
        $record = MedicalRecord::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
        ]);

        return Prescription::create([
            'medical_record_id' => $record->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'created_by' => $doctor->id,
            'prescription_number' => Prescription::generatePrescriptionNumber(),
            'status' => PrescriptionStatus::PENDING,
        ]);
    }

    private function labRequestFor(User $doctor, Visit $visit, Department $department): LabRequest
    {
        return LabRequest::create([
            'request_number' => LabRequest::generateRequestNumber(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $doctor->id,
            'department_id' => $department->id,
            'target_department_id' => $department->id,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
    }

    private function procedureRequestFor(User $doctor, Visit $visit, Department $department): ProcedureRequest
    {
        return ProcedureRequest::create([
            'request_number' => ProcedureRequest::generateNumber(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $doctor->id,
            'department_id' => $department->id,
            'priority' => Priority::NORMAL->value,
            'indication' => 'Diagnostic procedure',
            'status' => ProcedureStatus::REQUESTED,
            'requested_at' => now(),
        ]);
    }
}
