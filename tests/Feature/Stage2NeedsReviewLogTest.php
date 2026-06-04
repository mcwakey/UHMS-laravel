<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\HRService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Stage-2 prep: the 7 NEEDS_REVIEW controllers now reach a logging funnel. These
 * cover the genuinely-wired services (accounting, payroll, HR leave, purchase
 * returns) at the service level — facility/financial/HR audit events that must
 * NOT carry patient context — plus the audit-tool classification.
 */
class Stage2NeedsReviewLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function event(string $event): ?ActivityLog
    {
        return ActivityLog::query()->where('event', $event)->latest('id')->first();
    }

    private function category(string $type = 'income'): \App\Models\AccountCategory
    {
        return \App\Models\AccountCategory::create([
            'name' => ucfirst($type) . ' ' . fake()->unique()->numberBetween(1, 9999),
            'type' => $type,
            'is_active' => true,
        ]);
    }

    public function test_financial_entry_lifecycle_logs_under_billing(): void
    {
        $accounting = app(AccountingService::class);
        $entry = $accounting->createEntry([
            'category_id' => $this->category('income')->id,
            'type' => 'income',
            'amount' => 250,
            'description' => 'Cafeteria sales',
            'entry_date' => now()->toDateString(),
        ]);
        $accounting->approveEntry($entry);

        $recorded = $this->event('FINANCIAL_ENTRY_RECORDED');
        $this->assertNotNull($recorded);
        $this->assertSame('BILLING', $recorded->log_name);
        $this->assertSame(250.0, (float) $recorded->properties['metadata']['amount']);
        $this->assertNull($recorded->patient_id);

        $this->assertNotNull($this->event('FINANCIAL_ENTRY_APPROVED'));

        // Deleting an unapproved entry logs too.
        $entry2 = $accounting->createEntry([
            'category_id' => $this->category('expense')->id,
            'type' => 'expense', 'amount' => 40, 'description' => 'Stationery', 'entry_date' => now()->toDateString(),
        ]);
        $accounting->deleteEntry($entry2);
        $deleted = $this->event('FINANCIAL_ENTRY_DELETED');
        $this->assertNotNull($deleted);
        $this->assertSame('WARNING', $deleted->properties['severity']);
    }

    public function test_cashier_shift_lifecycle_logs_under_payments(): void
    {
        $accounting = app(AccountingService::class);
        $shift = $accounting->openShift(['opening_balance' => 100]);
        $accounting->closeShift($shift, ['actual_closing' => 130, 'notes' => null]);
        $accounting->verifyShift($shift->fresh());

        foreach (['CASHIER_SHIFT_OPENED', 'CASHIER_SHIFT_CLOSED', 'CASHIER_SHIFT_VERIFIED'] as $event) {
            $log = $this->event($event);
            $this->assertNotNull($log, "$event should be logged");
            $this->assertSame('PAYMENTS', $log->log_name);
            $this->assertNull($log->patient_id);
        }
    }

    public function test_payroll_batch_events_log_under_system(): void
    {
        $dept = \App\Models\Department::factory()->create();
        \App\Models\Employee::create([
            'employee_number' => 'EMP-0001', 'first_name' => 'Ama', 'last_name' => 'Mensah',
            'email' => 'ama@example.test', 'phone' => '0240000001', 'position' => 'Nurse',
            'department_id' => $dept->id,
            'basic_salary' => 2000, 'status' => \App\Enums\EmployeeStatus::ACTIVE->value,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $payroll = app(PayrollService::class);
        $period = now()->format('Y-m');
        $payroll->processPayroll($period);
        $payroll->approvePayroll($period);
        $payroll->markPaid($period);

        foreach (['PAYROLL_PROCESSED', 'PAYROLL_APPROVED', 'PAYROLL_PAID'] as $event) {
            $log = $this->event($event);
            $this->assertNotNull($log, "$event should be logged");
            $this->assertSame('SYSTEM', $log->log_name);
            $this->assertSame($period, $log->properties['metadata']['pay_period']);
            $this->assertNull($log->patient_id);
        }
        $this->assertSame('WARNING', $this->event('PAYROLL_PAID')->properties['severity']);
    }

    public function test_leave_lifecycle_logs_and_carries_no_patient_context(): void
    {
        $dept = \App\Models\Department::factory()->create();
        $employee = \App\Models\Employee::create([
            'employee_number' => 'EMP-0002', 'first_name' => 'Kofi', 'last_name' => 'Owusu',
            'email' => 'kofi@example.test', 'phone' => '0240000002', 'position' => 'Clerk',
            'department_id' => $dept->id,
            'basic_salary' => 1500, 'status' => \App\Enums\EmployeeStatus::ACTIVE->value,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $hr = app(HRService::class);
        $leave = $hr->createLeaveRequest([
            'employee_id' => $employee->id,
            'leave_type' => \App\Enums\LeaveType::ANNUAL->value,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Family',
        ]);
        $hr->approveLeave($leave->fresh());

        $requested = $this->event('LEAVE_REQUESTED');
        $this->assertNotNull($requested);
        $this->assertSame('SYSTEM', $requested->log_name);
        $this->assertSame($employee->id, (int) $requested->properties['metadata']['employee_id']);
        $this->assertNull($requested->patient_id);

        $approved = $this->event('LEAVE_APPROVED');
        $this->assertNotNull($approved);

        // Reject path (new pending request).
        $leave2 = $hr->createLeaveRequest([
            'employee_id' => $employee->id,
            'leave_type' => \App\Enums\LeaveType::ANNUAL->value,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
            'reason' => 'Personal',
        ]);
        $hr->rejectLeave($leave2->fresh(), 'Insufficient cover');
        $rejected = $this->event('LEAVE_REJECTED');
        $this->assertNotNull($rejected);
        $this->assertSame('Insufficient cover', $rejected->properties['reason']);
    }

    public function test_purchase_return_lifecycle_logs_without_patient_context(): void
    {
        $supplier = \App\Models\Supplier::create(['name' => 'Acme Pharma', 'is_active' => true]);
        $product = \App\Models\Product::create([
            'name' => 'Gauze', 'code' => 'GZ-1', 'product_type' => 'consumable',
            'unit' => 'roll', 'is_active' => true, 'created_by' => $this->user->id,
        ]);
        $location = \App\Models\StockLocation::create([
            'name' => 'Returns Test Store ' . fake()->unique()->numberBetween(1, 9999),
            'type' => 'store', 'is_main' => false, 'is_active' => true,
        ]);
        \App\Models\StockBalance::create([
            'product_id' => $product->id, 'stock_location_id' => $location->id, 'quantity_on_hand' => 100,
        ]);

        $service = app(\App\Services\PurchaseReturnService::class);
        $return = $service->create([
            'supplier_id' => $supplier->id,
            'stock_location_id' => $location->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Damaged on arrival',
            'items' => [[
                'product_id' => $product->id, 'stock_location_id' => $location->id,
                'quantity' => 5, 'unit_cost' => 10,
            ]],
        ]);
        $service->approve($return->fresh());
        $service->post($return->fresh());

        foreach (['PURCHASE_RETURN_CREATED', 'PURCHASE_RETURN_APPROVED', 'PURCHASE_RETURN_POSTED'] as $event) {
            $log = $this->event($event);
            $this->assertNotNull($log, "$event should be logged");
            $this->assertSame('PURCHASE_ORDERS', $log->log_name);
            $this->assertSame($supplier->id, (int) $log->properties['supplier_id']);
            $this->assertNull($log->patient_id);
        }
        // The auto goods-return supplier-ledger entry is NOT double-logged as a manual entry.
        $this->assertNull($this->event('SUPPLIER_PAYMENT_RECORDED'));
    }

    public function test_insurance_verification_logs_with_patient_context(): void
    {
        $patient = \App\Models\Patient::factory()->create();
        $type = \App\Models\InsuranceType::create(['name' => 'NHIS', 'code' => 'NHIS', 'is_active' => true]);
        $provider = \App\Models\InsuranceProvider::create([
            'name' => 'NHIA', 'short_name' => 'NHIA', 'code' => 'NHIA', 'type' => 'nhia',
            'insurance_type_id' => $type->id, 'is_active' => true,
        ]);
        $insurance = \App\Models\PatientInsurance::create([
            'patient_id' => $patient->id, 'insurance_provider_id' => $provider->id,
            'membership_number' => 'M-123', 'is_active' => true,
        ]);

        app(\App\Services\Insurance\Verification\InsuranceVerificationService::class)->verify($insurance);

        $log = $this->event('INSURANCE_VERIFIED');
        $this->assertNotNull($log);
        $this->assertSame('INSURANCE', $log->log_name);
        // This IS a patient event — context is attached (unlike the admin/HR ones above).
        $this->assertSame($patient->id, (int) $log->patient_id);
        $this->assertSame($provider->id, (int) $log->properties['insurance_provider_id']);
    }

    public function test_logs_audit_has_zero_needs_review_and_zero_missing(): void
    {
        Artisan::call('logs:audit', ['--json' => true]);
        $report = json_decode(file_get_contents(storage_path('reports/logs-audit-report.json')), true);

        $this->assertSame(0, $report['summary']['MISSING_LOG']);
        $this->assertSame(0, $report['summary']['NEEDS_REVIEW']);
    }
}
