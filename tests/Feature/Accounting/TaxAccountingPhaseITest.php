<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\PayrollRun;
use App\Models\PayrollTaxCalculation;
use App\Models\TaxLedgerEntry;
use App\Models\TaxPayment;
use App\Models\TaxReturn;
use App\Models\User;
use App\Services\PayrollAccountingService;
use App\Services\TaxLedgerService;
use App\Services\TaxReturnService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxAccountingPhaseITest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'accounting.tax_ledgers.view',
        'accounting.tax_ledgers.manage',
        'accounting.tax_returns.prepare',
        'accounting.tax_returns.approve',
        'accounting.tax_payments.record',
        'accounting.tax_reconciliation.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([ModuleSeeder::class, AccountingChartSeeder::class]);

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(self::PERMISSIONS);
        $this->actingAs($this->user);
        app(TaxLedgerService::class)->ensureDefaults();
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    public function test_source_tax_event_creates_immutable_tax_ledger_entry(): void
    {
        $entry = app(TaxLedgerService::class)->recordSourceEvent('WHT', [
            'entry_date' => '2026-06-15',
            'source_type' => 'supplier_payment',
            'source_id' => 77,
            'source_reference' => 'SP-77',
            'direction' => 'payable',
            'tax_base_amount' => 1000,
            'tax_amount' => 75,
        ]);

        $this->assertSame('WHT', $entry->taxType->code);
        $this->assertEqualsWithDelta(75, $entry->remaining_amount, 0.001);

        $again = app(TaxLedgerService::class)->recordSourceEvent('WHT', [
            'entry_date' => '2026-06-15',
            'source_type' => 'supplier_payment',
            'source_id' => 77,
            'source_reference' => 'SP-77',
            'direction' => 'payable',
            'tax_base_amount' => 1000,
            'tax_amount' => 75,
        ]);

        $this->assertSame($entry->id, $again->id);
        $this->assertSame(1, TaxLedgerEntry::count());
    }

    public function test_tax_return_payment_and_allocation_do_not_over_allocate(): void
    {
        app(TaxLedgerService::class)->recordSourceEvent('PAYE', [
            'entry_date' => '2026-06-30',
            'source_type' => 'payroll_run',
            'source_id' => 1,
            'source_reference' => 'PAY-2026-06',
            'direction' => 'payable',
            'tax_base_amount' => 3000,
            'tax_amount' => 250,
        ]);

        $service = app(TaxReturnService::class);
        $return = $service->prepare('PAYE', '2026-06-01', '2026-06-30', $this->user);
        $return = $service->approve($return, $this->user);

        $payment = $service->recordPayment('PAYE', [
            'payment_date' => '2026-07-05',
            'amount' => 250,
            'payment_account_id' => $this->accountId('1120'),
        ], $this->user);
        $service->allocatePayment($payment, $return, 250, $this->user);

        $this->assertEqualsWithDelta(0, $payment->fresh()->unallocated_amount, 0.001);
        $this->assertEqualsWithDelta(0, $return->fresh()->balance_due, 0.001);
        $this->assertEqualsWithDelta(0, TaxLedgerEntry::first()->remaining_amount, 0.001);
        $this->assertSame(TaxLedgerEntry::STATUS_SETTLED, TaxLedgerEntry::first()->status);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->allocatePayment($payment->fresh(), $return->fresh(), 1, $this->user);
    }

    public function test_payroll_posting_syncs_paye_and_pension_to_tax_ledger(): void
    {
        $run = $this->payrollRunFixture();

        app(PayrollAccountingService::class)->postPayroll($run, $this->user);

        $this->assertDatabaseHas('tax_ledger_entries', [
            'source_type' => PayrollRun::class,
            'source_id' => $run->id,
            'direction' => 'payable',
            'tax_amount' => 250,
            'remaining_amount' => 250,
        ]);
        $this->assertDatabaseHas('tax_ledger_entries', [
            'source_type' => PayrollRun::class,
            'source_id' => $run->id,
            'direction' => 'payable',
            'tax_amount' => 555,
            'remaining_amount' => 555,
        ]);
    }

    public function test_tax_workbench_renders(): void
    {
        $this->get(route('admin.accounting.tax.index'))
            ->assertOk()
            ->assertSee('Tax Accounting')
            ->assertSee('Tax Ledger Summary');
    }

    private function payrollRunFixture(): PayrollRun
    {
        $employee = Employee::create([
            'employee_number' => 'EMP-TAX-001',
            'first_name' => 'Ama',
            'last_name' => 'Tax',
            'email' => 'ama.tax@example.test',
            'phone' => '0244000001',
            'position' => 'Accountant',
            'hire_date' => '2025-01-01',
            'basic_salary' => 3000,
            'status' => 'active',
        ]);

        $run = PayrollRun::create([
            'pay_period' => '2026-06',
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $record = PayrollRecord::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'pay_period' => '2026-06',
            'basic_salary' => 3000,
            'overtime_pay' => 0,
            'allowances' => 0,
            'taxable_allowances' => 0,
            'non_taxable_allowances' => 0,
            'gross_pay' => 3000,
            'gross_taxable_income' => 3000,
            'ssnit_employee' => 165,
            'ssnit_employer' => 390,
            'other_pre_tax_deductions' => 50,
            'tax_reliefs' => 0,
            'chargeable_income' => 2785,
            'tax' => 250,
            'other_deductions' => 35,
            'attendance_deductions' => 100,
            'total_deductions' => 600,
            'net_pay' => 2400,
            'status' => 'approved',
            'processed_by' => $this->user->id,
        ]);

        PayrollTaxCalculation::create([
            'payroll_run_id' => $run->id,
            'payroll_record_id' => $record->id,
            'employee_id' => $employee->id,
            'gross_taxable_income' => 3000,
            'pre_tax_deductions' => 215,
            'reliefs_total' => 0,
            'chargeable_income' => 2785,
            'tax_amount' => 250,
            'calculation_snapshot_json' => ['source' => 'phase_i_test'],
            'calculated_at' => now(),
            'calculated_by' => $this->user->id,
        ]);

        return $run->fresh('records');
    }
}
