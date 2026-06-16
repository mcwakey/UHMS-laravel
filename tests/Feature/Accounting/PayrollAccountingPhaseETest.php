<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPostingAttempt;
use App\Models\Employee;
use App\Models\JournalEntryLine;
use App\Models\Module;
use App\Models\PayrollRecord;
use App\Models\PayrollRun;
use App\Models\PayrollTaxCalculation;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\PayrollAccountingService;
use App\Services\PayrollReconciliationService;
use App\Services\TaxLiabilityReconciliationService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollAccountingPhaseETest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'accounting.payroll_posting.view',
        'accounting.payroll_posting.post',
        'accounting.payroll_posting.settle',
        'accounting.payroll_posting.reverse',
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
        $this->actingAs($this->user);
    }

    public function test_approved_payroll_posts_salary_paye_and_pension_liabilities(): void
    {
        $run = $this->approvedPayrollRun();

        $journal = app(PayrollAccountingService::class)->postPayroll($run, $this->user);
        $run->refresh();

        $this->assertSame('posted', $run->status);
        $this->assertSame('posted', $run->accounting_status);
        $this->assertSame($journal->id, $run->journal_entry_id);
        $this->assertSame('PAYROLL', $journal->source_module);
        $this->assertEqualsWithDelta(3290.0, $journal->lines->sum('debit'), 0.001);
        $this->assertEqualsWithDelta(3290.0, $journal->lines->sum('credit'), 0.001);
        $this->assertSame('posted', AccountingPostingAttempt::where('source_module', 'PAYROLL')->firstOrFail()->status);

        $this->assertAccountAmount('5300', 3290.0, 'debit');
        $this->assertAccountAmount('2400', 2400.0, 'credit');
        $this->assertAccountAmount('2310', 250.0, 'credit');
        $this->assertAccountAmount('2320', 555.0, 'credit');
        $this->assertAccountAmount('2500', 85.0, 'credit');
    }

    public function test_salary_settlement_posts_against_salary_payable_and_marks_payroll_paid(): void
    {
        $run = $this->approvedPayrollRun();
        app(PayrollAccountingService::class)->postPayroll($run, $this->user);

        $settlement = app(PayrollAccountingService::class)->settlePayroll($run->fresh(), [
            'settlement_date' => '2026-06-30',
            'amount' => 2400,
        ], $this->user);

        $run->refresh();
        $this->assertSame('paid', $run->status);
        $this->assertSame('settled', $run->settlement_status);
        $this->assertEqualsWithDelta(2400.0, (float) $run->settled_amount, 0.001);
        $this->assertSame('posted', $settlement->status);
        $this->assertNotNull($run->records()->firstOrFail()->paid_at);
        $this->assertAccountAmount('2400', 2400.0, 'debit', $settlement->journal_entry_id);
        $this->assertAccountAmount('1120', 2400.0, 'credit', $settlement->journal_entry_id);
    }

    public function test_paye_and_pension_statutory_settlements_post_against_liability_accounts(): void
    {
        $run = $this->approvedPayrollRun();
        $service = app(PayrollAccountingService::class);
        $service->postPayroll($run, $this->user);

        $paye = $service->settleStatutoryLiability($run->fresh(), [
            'liability_type' => 'paye',
            'settlement_date' => '2026-06-30',
            'amount' => 250,
        ], $this->user);
        $pension = $service->settleStatutoryLiability($run->fresh(), [
            'liability_type' => 'pension',
            'settlement_date' => '2026-06-30',
            'amount' => 555,
        ], $this->user);

        $this->assertSame('posted', $paye->status);
        $this->assertSame('posted', $pension->status);
        $this->assertAccountAmount('2310', 250.0, 'debit', $paye->journal_entry_id);
        $this->assertAccountAmount('2320', 555.0, 'debit', $pension->journal_entry_id);
        $this->assertAccountAmount('1120', 250.0, 'credit', $paye->journal_entry_id);
        $this->assertAccountAmount('1120', 555.0, 'credit', $pension->journal_entry_id);
        $this->assertSame('posted', AccountingPostingAttempt::where('source_module', 'PAYROLL_STATUTORY_SETTLEMENT')->where('source_id', $paye->id)->firstOrFail()->status);
    }

    public function test_statutory_settlements_reduce_paye_and_pension_reconciliation_balances(): void
    {
        $run = $this->approvedPayrollRun();
        $service = app(PayrollAccountingService::class);
        $service->postPayroll($run, $this->user);
        $service->settleStatutoryLiability($run->fresh(), [
            'liability_type' => 'paye',
            'settlement_date' => '2026-06-30',
            'amount' => 250,
        ], $this->user);
        $service->settleStatutoryLiability($run->fresh(), [
            'liability_type' => 'pension',
            'settlement_date' => '2026-06-30',
            'amount' => 555,
        ], $this->user);

        $paye = app(TaxLiabilityReconciliationService::class)->calculatePaye(today()->startOfMonth(), today()->endOfMonth(), today()->endOfMonth());
        $pension = app(TaxLiabilityReconciliationService::class)->calculatePension(today()->startOfMonth(), today()->endOfMonth(), today()->endOfMonth());

        $this->assertSame('available', $paye['availability']);
        $this->assertSame('available', $pension['availability']);
        $this->assertEqualsWithDelta(0.0, $paye['subledger_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $pension['subledger_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $paye['difference_amount'], 0.001);
        $this->assertEqualsWithDelta(0.0, $pension['difference_amount'], 0.001);
    }

    public function test_posted_settlement_must_be_reversed_before_payroll_accrual_reversal(): void
    {
        $run = $this->approvedPayrollRun();
        $service = app(PayrollAccountingService::class);
        $service->postPayroll($run, $this->user);
        $settlement = $service->settlePayroll($run->fresh(), [
            'settlement_date' => '2026-06-30',
            'amount' => 500,
        ], $this->user);

        try {
            $service->reversePayroll($run->fresh(), $this->user, 'Correction');
            $this->fail('Expected reversal to be blocked while a posted settlement exists.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('settlements', $e->errors());
        }

        $service->reverseSettlement($settlement, $this->user, 'Payment rejected');
        $reversal = $service->reversePayroll($run->fresh(), $this->user, 'Accrual correction');

        $this->assertSame('REVERSAL', $reversal->source_module);
        $this->assertSame('reversed', $run->fresh()->accounting_status);
    }

    public function test_posted_statutory_settlement_must_be_reversed_before_payroll_accrual_reversal(): void
    {
        $run = $this->approvedPayrollRun();
        $service = app(PayrollAccountingService::class);
        $service->postPayroll($run, $this->user);
        $settlement = $service->settleStatutoryLiability($run->fresh(), [
            'liability_type' => 'paye',
            'settlement_date' => '2026-06-30',
            'amount' => 100,
        ], $this->user);

        try {
            $service->reversePayroll($run->fresh(), $this->user, 'Correction');
            $this->fail('Expected reversal to be blocked while a posted statutory settlement exists.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('statutory_settlements', $e->errors());
        }

        $reversal = $service->reverseStatutorySettlement($settlement, $this->user, 'Remittance rejected');
        $this->assertSame('REVERSAL', $reversal->source_module);

        $service->reversePayroll($run->fresh(), $this->user, 'Accrual correction');
        $this->assertSame('reversed', $run->fresh()->accounting_status);
    }

    public function test_payroll_posting_workbench_is_permission_and_module_protected(): void
    {
        $this->get(route('admin.accounting.payroll-posting.index'))->assertForbidden();

        $this->grant('accounting.payroll_posting.view');
        $this->get(route('admin.accounting.payroll-posting.index'))->assertOk();

        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();
        $this->get(route('admin.accounting.payroll-posting.index'))->assertForbidden();
    }

    public function test_phase_e_reconciliation_domains_use_posted_payroll_liabilities(): void
    {
        $run = $this->approvedPayrollRun();
        app(PayrollAccountingService::class)->postPayroll($run, $this->user);

        $payroll = app(PayrollReconciliationService::class)->calculate(today()->startOfMonth(), today()->endOfMonth(), today()->endOfMonth());
        $paye = app(TaxLiabilityReconciliationService::class)->calculatePaye(today()->startOfMonth(), today()->endOfMonth(), today()->endOfMonth());
        $pension = app(TaxLiabilityReconciliationService::class)->calculatePension(today()->startOfMonth(), today()->endOfMonth(), today()->endOfMonth());

        $this->assertSame('available', $payroll['availability']);
        $this->assertSame('available', $paye['availability']);
        $this->assertSame('available', $pension['availability']);
        $this->assertEqualsWithDelta(0.0, $payroll['difference_amount'], 0.001);
        $this->assertEqualsWithDelta(0.0, $paye['difference_amount'], 0.001);
        $this->assertEqualsWithDelta(0.0, $pension['difference_amount'], 0.001);
    }

    private function approvedPayrollRun(): PayrollRun
    {
        $employee = Employee::create([
            'employee_number' => 'EMP-PH-E',
            'first_name' => 'Phase',
            'last_name' => 'Employee',
            'phone' => '0240000000',
            'position' => 'Nurse',
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
            'calculation_snapshot_json' => ['source' => 'phase_e_test'],
            'calculated_at' => now(),
            'calculated_by' => $this->user->id,
        ]);

        return $run->fresh('records');
    }

    private function grant(string $permission): void
    {
        $this->user->givePermissionTo($permission);
    }

    private function assertAccountAmount(string $code, float $amount, string $side, ?int $journalId = null): void
    {
        $query = JournalEntryLine::query()
            ->whereHas('account', fn ($account) => $account->where('code', $code))
            ->when($journalId, fn ($line) => $line->where('journal_entry_id', $journalId));

        $this->assertEqualsWithDelta($amount, (float) $query->sum($side), 0.001, "Unexpected {$side} total for account {$code}.");
    }
}
