<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\StockRequisitionStatus;
use App\Models\Department;
use App\Models\StockRequisition;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StoresWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_stores_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/stores', route('stores.dashboard'));
        $this->assertSame('http://localhost/stores/requisitions', route('stores.stock-requisitions.index'));
        $this->assertSame('http://localhost/stores/stock', route('stores.stock.balances'));
        $this->assertSame('http://localhost/stores/purchase-orders', route('stores.purchase-orders.index'));
        $this->assertSame('http://localhost/stores/handoffs', route('stores.handoffs.index'));

        $middleware = Route::getRoutes()->getByName('stores.stock-requisitions.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:stores', $middleware);
        $this->assertContains('can:store.requisition.view', $middleware);
    }

    public function test_non_stores_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['store.requisition.view', 'store.purchase.view', 'product.view']);

        $this->actingAs($user)->get(route('stores.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('stores.stock-requisitions.index'))->assertForbidden();
        $this->actingAs($user)->get(route('stores.stock.balances'))->assertForbidden();
    }

    public function test_stores_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::STORES, 'STO');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'store.requisition.view', 'store.purchase.view', 'store.return.view',
            'product.view', 'reports.stock', 'reports.inventory_valuation.view', 'notifications.view',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'stores.stock-requisitions.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('stores.dashboard', $routes);
        $this->assertContains('stores.stock-requisitions.index', $routes);
        $this->assertContains('stores.stock.balances', $routes);
        $this->assertContains('stores.stock.ledger', $routes);
        $this->assertContains('stores.stock.valuation', $routes);
        $this->assertContains('stores.purchase-orders.index', $routes);
        $this->assertContains('stores.suppliers.index', $routes);
        $this->assertContains('stores.products.index', $routes);
        $this->assertContains('stores.reports.index', $routes);
        $this->assertNotContains('pharmacy.dashboard', $routes);
        $this->assertNotContains('admin.store.stock-requisitions.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'stores.stock-requisitions.index')['active']);

        // A user without procurement/valuation permissions loses those entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['store.requisition.view']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'stores.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('stores.stock-requisitions.index', $bareRoutes);
        $this->assertNotContains('stores.purchase-orders.index', $bareRoutes);
        $this->assertNotContains('stores.stock.valuation', $bareRoutes);
    }

    public function test_requisition_worklist_and_details_render_in_workspace(): void
    {
        $department = $this->department(DepartmentType::STORES, 'STO');
        $requesting = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['store.requisition.view']);

        $requisition = $this->requisition($requesting, $user, 'REQ-STORES-1');

        $this->actingAs($user)
            ->get(route('stores.stock-requisitions.index'))
            ->assertOk()
            ->assertSee($requisition->requisition_number);

        $this->actingAs($user)
            ->get(route('stores.stock-requisitions.show', $requisition))
            ->assertOk()
            ->assertSee($requisition->requisition_number);
    }

    public function test_dashboard_renders_inventory_operations_board(): void
    {
        $department = $this->department(DepartmentType::STORES, 'STO');
        $requesting = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['store.requisition.view', 'store.purchase.view']);

        $requisition = $this->requisition($requesting, $user, 'REQ-DASH-1');

        $this->actingAs($user)
            ->get(route('stores.dashboard'))
            ->assertOk()
            ->assertSee(__('stores.dashboard.title'))
            ->assertSee(__('stores.dashboard.pending_requisitions_count', ['count' => 1]))
            ->assertSee($requisition->requisition_number)
            ->assertSee('movementChart', false);

        $this->actingAs($user)
            ->get(route('stores.dashboard.redirect'))
            ->assertRedirect(route('stores.dashboard'));
    }

    public function test_legacy_browser_routes_redirect_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::STORES, 'STO');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['store.requisition.view', 'product.view']);

        $this->actingAs($user)
            ->get(route('admin.store.stock-requisitions.index'))
            ->assertRedirect(route('stores.stock-requisitions.index'));

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertRedirect(route('stores.products.index'));

        $this->actingAs($user)
            ->getJson(route('admin.store.stock-requisitions.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_stores_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $stores = $this->department(DepartmentType::STORES, 'STO');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($stores->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $stores->id])
            ->assertRedirect(route('stores.dashboard'));

        $storesUser = User::factory()->create([
            'department_id' => $stores->id,
            'email' => 'stores-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $storesUser->email, 'password' => 'password'])
            ->assertRedirect(route('stores.dashboard'));
    }

    public function test_stores_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/stores.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/stores.php')));

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

    private function requisition(Department $requestingDepartment, User $user, string $number): StockRequisition
    {
        return StockRequisition::create([
            'requisition_number' => $number,
            'department_id' => $requestingDepartment->id,
            'requested_by' => $user->id,
            'requested_at' => now()->subHour(),
            'status' => StockRequisitionStatus::SUBMITTED->value,
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
