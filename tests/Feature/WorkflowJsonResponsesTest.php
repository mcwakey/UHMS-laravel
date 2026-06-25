<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\BillingType;
use App\Enums\DepartmentType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShiftStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\CashierShift;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkflowJsonResponsesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private function addPayableItem(Invoice $invoice, float $amount = 100.00): InvoiceItem
    {
        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $invoice->visit_id,
            'patient_id' => $invoice->patient_id,
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => $amount,
            'cash_price' => $amount,
            'selected_price' => $amount,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'payment_status' => 'unpaid',
            'total_price' => $amount,
            'payer_type' => 'cash',
            'created_by' => $this->user->id,
        ]);
    }

    private function settleRouteServices(VisitConsultationRoute $route): void
    {
        $route->loadMissing('routeServices.invoiceItem.invoice');

        foreach ($route->routeServices as $routeService) {
            $item = $routeService->invoiceItem;
            if (! $item) {
                continue;
            }

            $payable = (float) $item->patient_payable;
            $item->forceFill([
                'paid_amount' => $payable,
                'balance' => 0,
                'payment_status' => 'paid',
            ])->save();

            if ($item->invoice) {
                $item->invoice->forceFill([
                    'amount_paid' => (float) $item->invoice->items()->sum('paid_amount'),
                    'balance' => max(0, (float) $item->invoice->items()->sum('balance')),
                    'status' => InvoiceStatus::PAID,
                ])->save();
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'General Consulting',
            'type' => DepartmentType::CONSULTATION,
        ]);
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
            'invoices.create',
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
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::TRIAGE,
        ]);
        $service = $this->createService($this->department, 'General Consultation');
        app(VisitService::class)->attachServices($visit, [[
            'service_catalog_id' => $service->id,
            'quantity' => 1,
        ]]);
        $route = $visit->pendingConsultationRoutes()->firstOrFail();
        $this->settleRouteServices($route);

        $response = $this->actingAs($this->user)->postJson(route('admin.triage.store', $visit), [
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 78,
            'temperature' => 36.9,
            'respiratory_rate' => 16,
            'spo2' => 98,
            'consultation_route_id' => $route->id,
            'notes' => 'Stable vitals',
        ]);

        $visit->refresh();

        $response->assertOk()
            ->assertJsonPath('visit_id', $visit->id)
            ->assertJsonPath('status', VisitStatus::WAITING->value)
            ->assertJsonPath('redirect_url', route('admin.visits.show', $visit))
            ->assertJsonPath('queue_url', route('admin.consultations.index'));

        $this->assertSame(VisitStatus::WAITING, $visit->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
        ]);
        $this->assertSame(1, QueueEntry::where('visit_id', $visit->id)
            ->where('department_id', $this->department->id)
            ->where('status', 'waiting')
            ->count());
        $this->assertDatabaseHas('vitals', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 78,
            'spo2' => 98,
        ]);
    }

    public function test_triage_completion_requires_selected_consultation_service_bill_to_be_settled(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'current_department_id' => null,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::TRIAGE,
        ]);
        $service = $this->createService($this->department, 'Unpaid General Consultation');
        app(VisitService::class)->attachServices($visit, [[
            'service_catalog_id' => $service->id,
            'quantity' => 1,
        ]]);
        $route = $visit->pendingConsultationRoutes()->firstOrFail();

        $response = $this->actingAs($this->user)->postJson(route('admin.triage.store', $visit), [
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'heart_rate' => 78,
            'temperature' => 36.9,
            'respiratory_rate' => 16,
            'spo2' => 98,
            'consultation_route_id' => $route->id,
            'notes' => 'Stable vitals',
        ]);

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Please settle the bill before this service can be rendered.']);

        $this->assertSame(VisitStatus::TRIAGE, $visit->fresh()->status);
        $this->assertSame(VisitConsultationRoute::STATUS_PENDING, $route->fresh()->status);
        $this->assertSame(0, QueueEntry::where('visit_id', $visit->id)
            ->where('department_id', $this->department->id)
            ->where('status', 'waiting')
            ->count());
    }

    public function test_triage_assessment_only_lists_billed_consultation_departments(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $billedDepartment = $this->department;
        $unbilledDepartment = Department::factory()->create([
            'name' => 'Unbilled Consultation',
            'type' => DepartmentType::CONSULTATION,
        ]);
        $investigationDepartment = Department::factory()->create([
            'name' => 'Radiology',
            'type' => DepartmentType::RADIOLOGY,
        ]);

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::TRIAGE,
        ]);

        $service = $this->createService($billedDepartment, 'General Consultation');
        app(VisitService::class)->attachServices($visit, [[
            'service_catalog_id' => $service->id,
            'quantity' => 1,
        ]]);
        $this->createService($unbilledDepartment, 'Unbilled Consultation Service');
        $this->createService($investigationDepartment, 'X-Ray', 'lab');

        $response = $this->actingAs($this->user)->get(route('admin.triage.create', $visit));

        $response->assertOk()
            ->assertSee($billedDepartment->name)
            ->assertDontSee($unbilledDepartment->name)
            ->assertDontSee($investigationDepartment->name);
    }

    public function test_invoice_create_prefills_selected_visit_services(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::TRIAGE,
        ]);
        $service = $this->createService($this->department, 'General Consultation');

        app(VisitService::class)->attachServices($visit, [[
            'service_catalog_id' => $service->id,
            'quantity' => 2,
        ]]);

        $response = $this->actingAs($this->user)->get(route('admin.billing.invoices.create', ['visit_id' => $visit->id]));

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('value=\\"2\\"', false);
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
        $service = $this->createService($this->department, 'Appointment General Consultation');
        $appointment->services()->attach($service->id, ['quantity' => 1]);

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
        $this->assertSame(VisitStatus::QUEUED, $visit->status);
        $this->assertSame('appointment', $visit->visit_source);
        // Appointment check-in walks the full chain: null → scheduled → checked_in → queued.
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
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id,
            'from_status' => VisitStatus::CHECKED_IN->value,
            'to_status' => VisitStatus::QUEUED->value,
        ]);
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

    public function test_payment_store_returns_json_without_completing_visit_or_consultation_route_when_fully_paid(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::BILLING,
            'checked_in_at' => now(),
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->user->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_by' => $this->user->id,
            'started_at' => now(),
            'activated_at' => now(),
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
        $this->addPayableItem($invoice, 100.00);
        $invoice->load('items');

        CashierShift::create([
            'user_id' => $this->user->id,
            'shift_date' => now()->toDateString(),
            'started_at' => now(),
            'opening_balance' => 0,
            'status' => ShiftStatus::OPEN,
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
            ->assertJsonPath('visit_status', VisitStatus::BILLING->value)
            ->assertJsonPath('redirect_url', route('admin.billing.invoices.show', $invoice));

        $this->assertSame(InvoiceStatus::PAID, $invoice->status);
        $this->assertSame(VisitStatus::BILLING, $visit->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
    }

    public function test_invoice_store_accepts_service_selected_rows_without_manual_description(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::BILLING,
        ]);
        $service = $this->createService($this->department, 'Dressing Service');

        $response = $this->actingAs($this->user)->post(route('admin.billing.invoices.store'), [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => BillingType::CASH->value,
            'items' => [
                ['description' => '', 'service_catalog_id' => $service->id, 'quantity' => 2, 'unit_price' => '100.00'],
                ['description' => '', 'service_catalog_id' => '', 'quantity' => 1, 'unit_price' => '0'],
            ],
        ]);

        $invoice = Invoice::latest('id')->first();

        $response->assertRedirect(route('admin.billing.invoices.show', $invoice));
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'service_catalog_id' => $service->id,
            'description' => 'Dressing Service',
            'quantity' => 2,
        ]);
        $this->assertSame(1, $invoice->items()->count());
    }

    private function createService(Department $department, string $name, string $category = 'consultation'): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => strtoupper(substr(md5($name), 0, 8)),
            'category' => $category,
            'price' => 100.00,
            'is_active' => true,
            'department_id' => $department->id,
            'department_type' => $department->type?->value,
        ]);
    }
}
