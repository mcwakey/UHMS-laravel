<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QueueBoardAndTriageQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::findOrCreate('Queue Tester', 'web');
        foreach (['queue.view', 'queue.manage', 'vitals.view', 'vitals.create'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_queue_board_displays_triage_queue_entries(): void
    {
        [$visit] = $this->makeTriageQueueVisit('Ama', 'Mensah', 1);

        $this->actingAs($this->user)
            ->get(route('admin.queue.board'))
            ->assertOk()
            ->assertSee('Triage \/ Assessment', false)
            ->assertSee('#1')
            ->assertSee($visit->patient->full_name);
    }

    public function test_triage_list_displays_queue_number(): void
    {
        [$visit] = $this->makeTriageQueueVisit('Kojo', 'Owusu', 3);

        $this->actingAs($this->user)
            ->get(route('admin.triage.index'))
            ->assertOk()
            ->assertSee('#3')
            ->assertSee($visit->patient->full_name);
    }

    public function test_triage_list_filters_by_visit_date_range(): void
    {
        $yesterday = today()->subDay()->toDateString();
        [$yesterdayVisit] = $this->makeTriageQueueVisit('Esi', 'Amoah', 2, VisitStatus::QUEUED, $yesterday);
        [$todayVisit] = $this->makeTriageQueueVisit('Yaw', 'Asare', 3);

        $this->actingAs($this->user)
            ->get(route('admin.triage.index', ['date_range' => $yesterday.' to '.$yesterday]))
            ->assertOk()
            ->assertSee('#2')
            ->assertSee($yesterdayVisit->patient->full_name)
            ->assertDontSee($todayVisit->patient->full_name)
            ->assertSee($yesterday.' to '.$yesterday);
    }

    public function test_nursing_triage_list_displays_global_unassigned_triage_queue(): void
    {
        $department = Department::factory()->create([
            'name' => 'OPD Nursing',
            'type' => DepartmentType::NURSING->value,
        ]);
        $this->user->forceFill(['department_id' => $department->id])->save();

        [$visit] = $this->makeTriageQueueVisit('Akua', 'Boateng', 5);

        $this->actingAs($this->user)
            ->get(route('nursing.triage.index'))
            ->assertOk()
            ->assertSee('#5')
            ->assertSee($visit->patient->full_name);

        $this->actingAs($this->user)
            ->get(route('nursing.triage.create', $visit))
            ->assertOk();
    }

    public function test_triage_list_unlocked_for_unpaid_running_bill_visit(): void
    {
        Setting::setValue('payment_timing', 'enabled', true, 'boolean');
        Setting::setValue('payment_timing', 'default_policy', 'running_bill', 'string');
        Setting::setValue('payment_timing', 'outpatient_policy', 'inherit', 'string');

        [$visit] = $this->makeTriageQueueVisit('Beatrice', 'Bediako', 1, VisitStatus::TRIAGE);
        $this->addUnpaidInvoiceItem($visit);

        $response = $this->actingAs($this->user)
            ->get(route('admin.triage.index'))
            ->assertOk()
            ->assertSee($visit->patient->full_name)
            ->assertSee('admin\/triage\/'.$visit->id.'\/assess', false)
            ->assertSee(__('triage.continue_triage'));

        $response->assertDontSee('ti ti-lock me-1', false);
    }

    public function test_department_queue_uses_queue_number_before_priority(): void
    {
        $department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        [$firstVisit] = $this->makeDepartmentQueueVisit('First', 'Patient', $department, 1, Priority::NORMAL);
        [$secondVisit] = $this->makeDepartmentQueueVisit('Second', 'Patient', $department, 2, Priority::URGENT);

        $this->actingAs($this->user)
            ->get(route('admin.queue.board'))
            ->assertOk()
            ->assertSeeInOrder([
                '#1',
                $firstVisit->patient->full_name,
                '#2',
                $secondVisit->patient->full_name,
            ]);

        $entry = app(QueueService::class)->callNext($department->id);

        $this->assertSame($firstVisit->id, $entry?->visit_id);
    }

    private function makeTriageQueueVisit(
        string $firstName,
        string $lastName,
        int $queueNumber,
        VisitStatus $status = VisitStatus::QUEUED,
        ?string $visitDate = null,
    ): array
    {
        $date = $visitDate
            ? \Illuminate\Support\Carbon::parse($visitDate)
            : today();
        $patient = Patient::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'registered_by' => $this->user->id,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => $status,
            'visit_date' => $date->toDateString(),
            'checked_in_at' => $date->copy()->setTime(8, 0),
        ]);
        $entry = QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => null,
            'queue_number' => $queueNumber,
            'priority' => Priority::NORMAL,
            'status' => 'waiting',
        ]);
        $entry->forceFill([
            'created_at' => $date->copy()->setTime(8, 5),
            'updated_at' => $date->copy()->setTime(8, 5),
        ])->save();

        return [$visit, $entry];
    }

    private function addUnpaidInvoiceItem(Visit $visit): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TRIAGE-'.$visit->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::CASH,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 100,
            'cash_price' => 100,
            'selected_price' => 100,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => 100,
            'paid_amount' => 0,
            'balance' => 100,
            'payment_status' => 'unpaid',
            'total_price' => 100,
            'payer_type' => 'cash',
            'created_by' => $this->user->id,
        ]);
    }

    private function makeDepartmentQueueVisit(
        string $firstName,
        string $lastName,
        Department $department,
        int $queueNumber,
        Priority $priority,
    ): array {
        $patient = Patient::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'registered_by' => $this->user->id,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING,
            'priority' => $priority,
            'visit_date' => today(),
            'current_department_id' => $department->id,
        ]);
        $entry = QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => $department->id,
            'queue_number' => $queueNumber,
            'priority' => $priority,
            'status' => 'waiting',
        ]);

        return [$visit, $entry];
    }
}
