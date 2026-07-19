<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdministrativeWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_administrative_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/administrative', route('administrative.dashboard'));
        $this->assertSame('http://localhost/administrative/departments', route('administrative.departments.index'));
        $this->assertSame('http://localhost/administrative/users', route('administrative.users.index'));
        $this->assertSame('http://localhost/administrative/audit', route('administrative.logs.index'));
        $this->assertSame('http://localhost/administrative/reports', route('administrative.reports.index'));
        $this->assertSame('http://localhost/administrative/handoffs', route('administrative.handoffs.index'));

        $middleware = Route::getRoutes()->getByName('administrative.departments.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:administrative', $middleware);
        $this->assertContains('can:departments.view', $middleware);
    }

    public function test_non_administrative_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['departments.view', 'users.view', 'logs.view']);

        $this->actingAs($user)->get(route('administrative.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('administrative.departments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('administrative.logs.index'))->assertForbidden();
    }

    public function test_administrative_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::ADMINISTRATIVE, 'ADM');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'departments.view', 'users.view', 'roles.manage', 'hr.employees.view',
            'hr.attendance.view', 'hr.leave.view', 'logs.view', 'notifications.broadcast',
            'reports.view', 'reports.department_comparison.view', 'notifications.view',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'administrative.departments.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('administrative.dashboard', $routes);
        $this->assertContains('administrative.departments.index', $routes);
        $this->assertContains('administrative.users.index', $routes);
        $this->assertContains('administrative.roles.index', $routes);
        $this->assertContains('administrative.hr.employees.index', $routes);
        $this->assertContains('administrative.hr.leave.index', $routes);
        $this->assertContains('administrative.logs.index', $routes);
        $this->assertContains('administrative.notifications.broadcast.create', $routes);
        $this->assertContains('administrative.reports.index', $routes);
        $this->assertContains('administrative.reports.department-metrics', $routes);
        $this->assertNotContains('finance.dashboard', $routes);
        $this->assertNotContains('admin.departments.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'administrative.departments.index')['active']);

        // A coordinator without user-management or audit permissions loses those entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['departments.view']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'administrative.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('administrative.departments.index', $bareRoutes);
        $this->assertNotContains('administrative.users.index', $bareRoutes);
        $this->assertNotContains('administrative.logs.index', $bareRoutes);
    }

    public function test_oversight_pages_render_in_workspace(): void
    {
        $department = $this->department(DepartmentType::ADMINISTRATIVE, 'ADM');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['departments.view', 'users.view', 'logs.view']);

        $this->actingAs($user)
            ->get(route('administrative.departments.index'))
            ->assertOk()
            ->assertSee($department->name);

        $this->actingAs($user)->get(route('administrative.users.index'))->assertOk();
        $this->actingAs($user)->get(route('administrative.logs.index'))->assertOk();
    }

    public function test_dashboard_renders_hospital_operations_board(): void
    {
        $department = $this->department(DepartmentType::ADMINISTRATIVE, 'ADM');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['departments.view', 'reports.view']);

        $this->actingAs($user)
            ->get(route('administrative.dashboard'))
            ->assertOk()
            ->assertSee(__('administrative.dashboard.title'))
            ->assertSee(__('administrative.dashboard.active_departments'))
            ->assertSee('visitTrendChart', false);

        $this->actingAs($user)
            ->get(route('administrative.dashboard.redirect'))
            ->assertRedirect(route('administrative.dashboard'));
    }

    public function test_legacy_browser_routes_redirect_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::ADMINISTRATIVE, 'ADM');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['departments.view', 'users.view', 'logs.view']);

        $this->actingAs($user)
            ->get(route('admin.departments.index'))
            ->assertRedirect(route('administrative.departments.index'));

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('administrative.users.index'));

        $this->actingAs($user)
            ->get(route('admin.logs.index'))
            ->assertRedirect(route('administrative.logs.index'));

        $this->actingAs($user)
            ->getJson(route('admin.departments.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_administrative_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $administrative = $this->department(DepartmentType::ADMINISTRATIVE, 'ADM');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($administrative->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $administrative->id])
            ->assertRedirect(route('administrative.dashboard'));

        $adminUser = User::factory()->create([
            'department_id' => $administrative->id,
            'email' => 'administrative-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $adminUser->email, 'password' => 'password'])
            ->assertRedirect(route('administrative.dashboard'));
    }

    public function test_administrative_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/administrative.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/administrative.php')));

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

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }
}
