<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowJsonResponsesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);

        Role::findOrCreate('Accountant', 'web');
        $role = Role::findOrCreate('Workflow Tester', 'web');

        foreach ([
            'patients.view',
            'visits.view',
            'visits.create',
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'vitals.view',
            'vitals.create',
            'payments.view',
            'payments.create',
            'invoices.view',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);
    }

    public function test_visit_store_returns_json_payload_for_ajax_request(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson(route('admin.visits.store'), [
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => 'normal',
            'visit_date' => now()->toDateString(),
            'chief_complaint' => 'Headache and fever',
        ]);

        $visit = Visit::latest('id')->first();

        $response->assertCreated()
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('visit_number', $visit->visit_number)
            ->assertJsonPath('redirect_url', route('admin.visits.show', $visit));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'patient_id' => $patient->id,
        ]);
    }

    public function test_triage_store_returns_json_payload_and_updates_visit_status(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'current_department_id' => null,
            'status' => VisitStatus::TRIAGE,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('admin.triage.store', $visit), [
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 78,
            'temperature' => 36.9,
            'respiratory_rate' => 16,
            'spo2' => 98,
            'department_id' => $this->department->id,
            'notes' => 'Stable vitals',
        ]);

        $visit->refresh();

        $response->assertOk()
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('status', VisitStatus::WAITING_CONSULTATION->value)
            ->assertJsonPath('redirect_url', route('admin.visits.show', $visit));

        $this->assertSame(VisitStatus::WAITING_CONSULTATION, $visit->status);
    }

    public function test_appointment_check_in_returns_json_and_creates_visit(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $doctor = User::factory()->create(['department_id' => $this->department->id]);

        $appointment = Appointment::create([
            'appointment_number' => Appointment::generateAppointmentNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'visit_type' => VisitType::OUTPATIENT,
            'priority' => 'normal',
            'status' => AppointmentStatus::CONFIRMED,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('admin.appointments.check-in', $appointment));

        $appointment->refresh();
        $visit = $appointment->visit()->first();

        $response->assertOk()
            ->assertJsonPath('appointment_id', $appointment->id)
            ->assertJsonPath('appointment_status', AppointmentStatus::CHECKED_IN->value)
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('visit_redirect_url', route('admin.visits.show', $visit));

        $this->assertSame(AppointmentStatus::CHECKED_IN, $appointment->status);
        $this->assertNotNull($visit);
    }

    public function test_appointment_transition_returns_json_and_updates_status(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $appointment = Appointment::create([
            'appointment_number' => Appointment::generateAppointmentNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $this->user->id,
            'department_id' => $this->department->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
            'visit_type' => VisitType::OUTPATIENT,
            'priority' => 'normal',
            'status' => AppointmentStatus::SCHEDULED,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->patchJson(route('admin.appointments.transition', $appointment), [
            'status' => AppointmentStatus::CONFIRMED->value,
        ]);

        $appointment->refresh();

        $response->assertOk()
            ->assertJsonPath('appointment_id', $appointment->id)
            ->assertJsonPath('appointment_status', AppointmentStatus::CONFIRMED->value)
            ->assertJsonPath('redirect_url', route('admin.appointments.show', $appointment));

        $this->assertSame(AppointmentStatus::CONFIRMED, $appointment->status);
    }

    public function test_payment_store_returns_json_and_completes_billed_visit_when_fully_paid(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::BILLING,
            'checked_in_at' => now(),
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00001',
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 100.00,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => 100.00,
            'amount_paid' => 0,
            'balance' => 100.00,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('admin.billing.payments.store', $invoice), [
            'amount' => 100.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $invoice->refresh();
        $visit->refresh();

        $response->assertCreated()
            ->assertJsonPath('invoice_id', $invoice->id)
            ->assertJsonPath('invoice_status', InvoiceStatus::PAID->value)
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('visit_status', VisitStatus::COMPLETED->value)
            ->assertJsonPath('redirect_url', route('admin.billing.invoices.show', $invoice));

        $this->assertSame(InvoiceStatus::PAID, $invoice->status);
        $this->assertSame(VisitStatus::COMPLETED, $visit->status);
    }
}