<?php

namespace Tests\Feature\Accounting;

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingModuleSplitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ModuleSeeder::class);

        $permissions = [
            'invoices.view', 'invoices.create', 'payments.view', 'payments.create',
            'credit_notes.view', 'sponsors.view', 'reports.ar_aging.view',
            'billing.discount.report', 'accounts.entries.view', 'accounts.cashier',
            'accounts.manage', 'accounting.dashboard.view', 'accounting.accounts.view',
            'accounting.journals.view', 'accounting.reports.general_ledger',
            'accounting.reports.trial_balance', 'accounting.reports.cashbook',
            'accounting.reports.profit_loss', 'accounting.reports.balance_sheet',
            'accounting.reports.revenue_by_department', 'accounting.reports.expense_by_department',
            'accounts_payable.view', 'reports.ap_aging.view', 'accounting.fiscal_years.view',
            'accounting.periods.view', 'accounting.settings.view',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);
    }

    public function test_module_seeder_creates_basic_and_advanced_accounting_tiers(): void
    {
        $this->assertDatabaseHas('modules', ['slug' => 'accounting_basic', 'is_enabled' => true]);
        $this->assertDatabaseHas('modules', ['slug' => 'accounting_advanced', 'depends_on' => 'accounting_basic']);
    }

    public function test_sidebar_sorts_finance_links_into_three_sections(): void
    {
        app(ModuleService::class)->flush();
        $sections = app(SidebarMenuBuilder::class)->build($this->user, 'admin.accounting.dashboard');
        $titles = collect($sections)->pluck('title');

        $this->assertTrue($titles->contains('Billing & Collections'));
        $this->assertTrue($titles->contains('Basic Accounting'));
        $this->assertTrue($titles->contains('Advanced Accounting'));
        $this->assertFalse($titles->contains('Accounts & Finance'));

        $basic = collect($sections)->firstWhere('title', 'Basic Accounting');
        $this->assertSame(
            ['Income', 'Expenses', 'Daily Collection', 'Cashier Handover', 'Reconciliation', 'Account Categories'],
            collect($basic['items'])->pluck('label')->all()
        );
    }

    public function test_disabling_basic_accounting_blocks_direct_basic_routes(): void
    {
        Module::where('slug', 'accounting_basic')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->actingAs($this->user)
            ->get(route('admin.accounts.income.index'))
            ->assertForbidden();
    }

    public function test_disabling_advanced_accounting_blocks_direct_ledger_routes(): void
    {
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->actingAs($this->user)
            ->get(route('admin.accounting.dashboard'))
            ->assertForbidden();
    }
}
