<?php

namespace Tests\Feature\Accounting;

use App\Enums\PurchaseOrderStatus;
use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BudgetApprovalService;
use App\Services\BudgetAvailabilityService;
use App\Services\BudgetRevisionService;
use App\Services\CommitmentService;
use App\Services\JournalEntryService;
use App\Services\ProcurementService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BudgetCommitmentPhaseGTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private FiscalYear $fiscalYear;

    private const PERMISSIONS = [
        'accounting.budgets.view',
        'accounting.budgets.manage',
        'accounting.budgets.submit',
        'accounting.budgets.approve',
        'accounting.commitments.view',
        'accounting.commitments.manage',
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
        $this->department = Department::factory()->create(['name' => 'Stores']);
        $this->fiscalYear = FiscalYear::where('name', 'FY '.now()->year)->firstOrFail();
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function approvedBudget(float $amount = 1000): Budget
    {
        $budget = Budget::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'name' => 'Stores Budget',
            'enforcement_mode' => 'warning',
            'created_by' => $this->user->id,
        ]);
        $budget->lines()->create([
            'department_id' => $this->department->id,
            'account_id' => $this->accountId('5300'),
            'amount' => $amount,
        ]);

        app(BudgetApprovalService::class)->submit($budget, $this->user);

        return app(BudgetApprovalService::class)->approve($budget->fresh(), $this->user);
    }

    private function postActual(float $amount): JournalEntry
    {
        $entry = app(JournalEntryService::class)->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Budget actual',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => $amount, 'credit' => 0, 'department_id' => $this->department->id],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => $amount],
            ],
        ]);

        return app(JournalEntryService::class)->post($entry, $this->user);
    }

    public function test_approved_budget_lines_are_immutable(): void
    {
        $budget = $this->approvedBudget(1200);
        $line = $budget->lines()->firstOrFail();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $line->update(['amount' => 1500]);
    }

    public function test_available_budget_subtracts_actuals_and_open_commitments(): void
    {
        $budget = $this->approvedBudget(1000);
        app(BudgetRevisionService::class)->createApprovedRevision($budget, [[
            'department_id' => $this->department->id,
            'account_id' => $this->accountId('5300'),
            'amount_delta' => 250,
        ]], 'Approved supplement', $this->user);
        app(BudgetRevisionService::class)->createApprovedTransfer($budget, [
            'from_department_id' => null,
            'from_account_id' => $this->accountId('5400'),
            'to_department_id' => $this->department->id,
            'to_account_id' => $this->accountId('5300'),
            'amount' => 100,
            'reason' => 'Move funds to salaries',
        ], $this->user);

        $this->postActual(300);
        app(CommitmentService::class)->create([
            'budget_id' => $budget->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'department_id' => $this->department->id,
            'account_id' => $this->accountId('5300'),
            'amount' => 200,
            'source_reference' => 'MANUAL-COMMIT',
        ], $this->user);

        $available = app(BudgetAvailabilityService::class)->available($this->fiscalYear->id, $this->department->id, $this->accountId('5300'));

        $this->assertEqualsWithDelta(1350, $available['adjusted_budget'], 0.001);
        $this->assertEqualsWithDelta(300, $available['actual'], 0.001);
        $this->assertEqualsWithDelta(200, $available['open_commitments'], 0.001);
        $this->assertEqualsWithDelta(850, $available['available'], 0.001);
    }

    public function test_purchase_order_approval_creates_budget_commitment_when_dimensions_exist(): void
    {
        $budget = $this->approvedBudget(1000);
        $supplier = Supplier::create(['name' => 'Budget Supplier', 'is_active' => true]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-BUD-001',
            'supplier_id' => $supplier->id,
            'budget_department_id' => $this->department->id,
            'budget_account_id' => $this->accountId('5300'),
            'order_date' => today()->toDateString(),
            'total_amount' => 450,
            'status' => PurchaseOrderStatus::SUBMITTED,
            'created_by' => $this->user->id,
        ]);

        app(ProcurementService::class)->approve($po);

        $this->assertNotNull($po->fresh()->budget_commitment_id);
        $this->assertDatabaseHas('budget_commitments', [
            'budget_id' => $budget->id,
            'source_reference' => 'PO-BUD-001',
            'original_amount' => 450,
            'remaining_amount' => 450,
            'status' => 'active',
        ]);
    }

    public function test_budget_pages_are_available_when_module_and_permissions_exist(): void
    {
        $this->approvedBudget(1000);

        $this->get(route('admin.accounting.budgets.index'))
            ->assertOk()
            ->assertSee('Budget Availability');

        $this->get(route('admin.accounting.commitments.index'))
            ->assertOk()
            ->assertSee('Budget Commitments');
    }
}
