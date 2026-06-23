<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
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

class AsyncPageBehaviorTest extends TestCase
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
        $role = Role::findOrCreate('Async Page Tester', 'web');

        foreach ([
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'invoices.view',
            'payments.create',
            'patients.view',
            'visits.view',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);

        $this->doctor = User::factory()->create(['department_id' => $this->department->id]);
        $this->doctor->assignRole($doctorRole);
    }

    public function test_appointment_index_uses_async_handler_without_forced_navigation(): void
    {
        $this->createAppointment(AppointmentStatus::SCHEDULED);
        $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->actingAs($this->user)->get(route('admin.appointments.index'));

        $response->assertOk()
            ->assertSee('appointmentIndexActionFeedback', false)
            ->assertSee('js-appointment-status-badge', false)
            ->assertDontSee('window.location.assign', false);
    }

    public function test_appointment_show_uses_async_handler_without_forced_navigation(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::CONFIRMED);

        $response = $this->actingAs($this->user)->get(route('admin.appointments.show', $appointment));

        $response->assertOk()
            ->assertSee('appointmentActionFeedback', false)
            ->assertSee('js-appointment-status-badge', false)
            ->assertDontSee('window.location.assign', false);
    }

    public function test_invoice_show_uses_async_handler_without_forced_navigation(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::BILLING,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00002',
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 250.00,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => 250.00,
            'amount_paid' => 0,
            'balance' => 250.00,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.billing.invoices.show', $invoice));

        $response->assertOk()
            ->assertSee('invoiceStatusBadge', false)
            ->assertSee('invoiceOutstandingValue', false)
            ->assertDontSee('window.Uhms' . 'Inertia', false)
            ->assertDontSee('updateInvoiceState', false)
            ->assertDontSee('window.location.assign', false);
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
