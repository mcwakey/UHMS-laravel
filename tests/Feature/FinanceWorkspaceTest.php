<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_finance_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/finance', route('finance.dashboard'));
        $this->assertSame('http://localhost/finance/invoices', route('finance.billing.invoices.index'));
        $this->assertSame('http://localhost/finance/payments', route('finance.billing.payments.index'));
        $this->assertSame('http://localhost/finance/cashier', route('finance.accounts.handover.index'));
        $this->assertSame('http://localhost/finance/claims', route('finance.claims.index'));
        $this->assertSame('http://localhost/finance/trial-balance', route('finance.accounting.trial-balance'));

        $middleware = Route::getRoutes()->getByName('finance.billing.invoices.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:finance', $middleware);
        $this->assertContains('can:invoices.view', $middleware);
    }

    public function test_non_finance_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['invoices.view', 'payments.view', 'claims.view']);

        $this->actingAs($user)->get(route('finance.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.billing.invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.claims.index'))->assertForbidden();
    }

    public function test_finance_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::FINANCE, 'FIN');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'invoices.view', 'payments.view', 'payments.create', 'credit_notes.view',
            'sponsors.view', 'reports.ar_aging.view', 'claims.view', 'accounts.cashier',
            'accounts.entries.view', 'accounting.journals.view', 'accounting.reports.general_ledger',
            'accounting.reports.trial_balance', 'accounting.accounts.view', 'patients.view',
            'reports.billing', 'notifications.view', 'invoices.create',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'finance.billing.invoices.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('finance.dashboard', $routes);
        $this->assertContains('finance.billing.invoices.index', $routes);
        $this->assertContains('finance.billing.payments.receive', $routes);
        $this->assertContains('finance.billing.credit-notes.index', $routes);
        $this->assertContains('finance.claims.index', $routes);
        $this->assertContains('finance.accounts.handover.index', $routes);
        $this->assertContains('finance.accounting.journals.index', $routes);
        $this->assertContains('finance.accounting.trial-balance', $routes);
        $this->assertContains('finance.reports.index', $routes);
        $this->assertNotContains('stores.dashboard', $routes);
        $this->assertNotContains('admin.billing.invoices.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'finance.billing.invoices.index')['active']);

        // A cashier-only user loses accounting and claims entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['payments.view', 'payments.create']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'finance.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('finance.billing.payments.receive', $bareRoutes);
        $this->assertNotContains('finance.accounting.journals.index', $bareRoutes);
        $this->assertNotContains('finance.claims.index', $bareRoutes);
    }

    public function test_invoice_worklist_and_details_render_in_workspace(): void
    {
        $department = $this->department(DepartmentType::FINANCE, 'FIN');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['invoices.view']);

        $invoice = $this->invoice($user, 'INV-FIN-1');

        $this->actingAs($user)
            ->get(route('finance.billing.invoices.index'))
            ->assertOk()
            ->assertSee($invoice->invoice_number);

        $this->actingAs($user)
            ->get(route('finance.billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_dashboard_renders_finance_operations_board(): void
    {
        $department = $this->department(DepartmentType::FINANCE, 'FIN');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['invoices.view', 'payments.view', 'payments.create']);

        $invoice = $this->invoice($user, 'INV-DASH-1');

        $this->actingAs($user)
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee(__('finance.dashboard.title'))
            ->assertSee(__('finance.dashboard.open_invoices_count', ['count' => 1]))
            ->assertSee($invoice->invoice_number)
            ->assertSee('billingChart', false);

        $this->actingAs($user)
            ->get(route('finance.dashboard.redirect'))
            ->assertRedirect(route('finance.dashboard'));
    }

    public function test_legacy_browser_routes_redirect_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::FINANCE, 'FIN');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['invoices.view', 'claims.view', 'accounting.journals.view']);

        $this->actingAs($user)
            ->get(route('admin.billing.invoices.index'))
            ->assertRedirect(route('finance.billing.invoices.index'));

        $this->actingAs($user)
            ->get(route('admin.claims.index'))
            ->assertRedirect(route('finance.claims.index'));

        $this->actingAs($user)
            ->get(route('admin.accounting.journals.index'))
            ->assertRedirect(route('finance.accounting.journals.index'));

        $this->actingAs($user)
            ->getJson(route('admin.billing.invoices.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_finance_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $finance = $this->department(DepartmentType::FINANCE, 'FIN');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($finance->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $finance->id])
            ->assertRedirect(route('finance.dashboard'));

        $financeUser = User::factory()->create([
            'department_id' => $finance->id,
            'email' => 'finance-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $financeUser->email, 'password' => 'password'])
            ->assertRedirect(route('finance.dashboard'));
    }

    public function test_finance_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/finance.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/finance.php')));

        sort($english);
        sort($french);

        $this->assertSame($english, $french);
    }

    private function department(DepartmentType $type, string $code): Department
    {
        return Department::create([
            'name' => $type->label().' '.$code,
            'code' => $code.random_int(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);
    }

    private function invoice(User $user, string $number): Invoice
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);

        return Invoice::create([
            'invoice_number' => $number,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => 'cash',
            'subtotal' => 250,
            'total_amount' => 250,
            'balance' => 250,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
    }

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }
}
