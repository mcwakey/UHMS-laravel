<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\CashFlowStatementService;
use App\Services\JournalEntryService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CashFlowAndExportsPhaseFTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'accounting.reports.cash_flow',
        'accounting.reports.trial_balance',
        'accounting.reports.general_ledger',
        'accounting.exports',
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
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function postJournal(array $lines, string $description, string $sourceModule = 'MANUAL'): JournalEntry
    {
        $entry = app(JournalEntryService::class)->createDraft([
            'entry_date' => '2026-01-15',
            'description' => $description,
            'source_module' => $sourceModule,
            'lines' => $lines,
        ]);

        return app(JournalEntryService::class)->post($entry, $this->user);
    }

    public function test_direct_cash_flow_statement_classifies_cash_movements(): void
    {
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 1000, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 1000],
        ], 'Cash receipt from patient', 'PAYMENT');

        $this->postJournal([
            ['account_id' => $this->accountId('5300'), 'debit' => 300, 'credit' => 0],
            ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 300],
        ], 'Salary cash payment', 'PAYROLL_SETTLEMENT');

        $this->postJournal([
            ['account_id' => $this->accountId('1410'), 'debit' => 500, 'credit' => 0],
            ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 500],
        ], 'Equipment purchase');

        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 2000, 'credit' => 0],
            ['account_id' => $this->accountId('3100'), 'debit' => 0, 'credit' => 2000],
        ], 'Owner capital injection');

        $this->postJournal([
            ['account_id' => $this->accountId('1120'), 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 100],
        ], 'Cash to bank transfer');

        $report = app(CashFlowStatementService::class)->direct([
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);

        $this->assertEqualsWithDelta(700, $report['sections']['operating']['net'], 0.001);
        $this->assertEqualsWithDelta(-500, $report['sections']['investing']['net'], 0.001);
        $this->assertEqualsWithDelta(2000, $report['sections']['financing']['net'], 0.001);
        $this->assertEqualsWithDelta(2200, $report['net_change'], 0.001);
        $this->assertEqualsWithDelta(2200, $report['closing_balance'], 0.001);
        $this->assertCount(0, $report['sections']['other']['rows']);
    }

    public function test_cash_flow_page_and_csv_exports_are_available(): void
    {
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 750, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 750],
        ], 'Cash receipt from patient', 'PAYMENT');

        $this->get(route('admin.accounting.reports.cash-flow', ['date_from' => '2026-01-01', 'date_to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('Cash Flow Statement')
            ->assertSee('Operating activities');

        $cashFlowCsv = $this->get(route('admin.accounting.reports.cash-flow.export', ['date_from' => '2026-01-01', 'date_to' => '2026-01-31']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Cash Flow Statement', $cashFlowCsv->streamedContent());

        $trialBalanceCsv = $this->get(route('admin.accounting.trial-balance.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Account Code', $trialBalanceCsv->streamedContent());

        $ledgerCsv = $this->get(route('admin.accounting.general-ledger.export', ['account_id' => $this->accountId('1110')]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Running Balance', $ledgerCsv->streamedContent());
    }
}
