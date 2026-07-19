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

class MaternityWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_maternity_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/maternity', route('maternity.dashboard'));
        $this->assertSame('http://localhost/maternity/pregnancies', route('maternity.pregnancies.index'));
        $this->assertSame('http://localhost/maternity/labor', route('maternity.labor.index'));
        $this->assertSame('http://localhost/maternity/postnatal', route('maternity.postnatal.index'));
        $this->assertSame('http://localhost/maternity/reports', route('maternity.reports.index'));
        $this->assertSame('http://localhost/maternity/handoffs', route('maternity.handoffs.index'));

        // The workspace mounts the same module definitions as /admin/maternity.
        $this->assertTrue(Route::has('admin.maternity.pregnancies.index'));

        $middleware = Route::getRoutes()->getByName('maternity.pregnancies.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:maternity', $middleware);
        $this->assertContains('can:maternity.view', $middleware);
        $this->assertContains('can:maternity.pregnancy.view', $middleware);
    }

    public function test_non_maternity_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['maternity.view', 'maternity.pregnancy.view', 'maternity.labor.view', 'maternity.dashboard.view']);

        $this->actingAs($user)->get(route('maternity.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('maternity.pregnancies.index'))->assertForbidden();
        $this->actingAs($user)->get(route('maternity.labor.index'))->assertForbidden();
    }

    public function test_maternity_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::MATERNITY, 'MAT');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'maternity.view', 'maternity.dashboard.view', 'maternity.pregnancy.view',
            'maternity.labor.view', 'maternity.postnatal.view', 'maternity.reports.view',
            'maternity.billing_readiness.view', 'patients.view', 'notifications.view',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'maternity.labor.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('maternity.dashboard', $routes);
        $this->assertContains('maternity.pregnancies.index', $routes);
        $this->assertContains('maternity.labor.index', $routes);
        $this->assertContains('maternity.postnatal.index', $routes);
        $this->assertContains('maternity.billing-readiness.show', $routes);
        $this->assertContains('maternity.reports.index', $routes);
        $this->assertContains('maternity.patients.index', $routes);
        $this->assertNotContains('inpatient.dashboard', $routes);
        $this->assertNotContains('admin.maternity.dashboard', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'maternity.labor.index')['active']);

        // A midwife without reporting/billing permissions loses those entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['maternity.view', 'maternity.labor.view']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'maternity.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('maternity.labor.index', $bareRoutes);
        $this->assertNotContains('maternity.reports.index', $bareRoutes);
        $this->assertNotContains('maternity.billing-readiness.show', $bareRoutes);
    }

    public function test_worklists_render_in_workspace(): void
    {
        $department = $this->department(DepartmentType::MATERNITY, 'MAT');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'maternity.view', 'maternity.dashboard.view', 'maternity.pregnancy.view',
            'maternity.labor.view', 'maternity.postnatal.view',
        ]);

        $this->actingAs($user)->get(route('maternity.pregnancies.index'))->assertOk();
        $this->actingAs($user)->get(route('maternity.labor.index'))->assertOk();
        $this->actingAs($user)->get(route('maternity.postnatal.index'))->assertOk();
    }

    public function test_dashboard_renders_and_secondary_url_redirects(): void
    {
        $department = $this->department(DepartmentType::MATERNITY, 'MAT');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['maternity.view', 'maternity.dashboard.view']);

        $this->actingAs($user)
            ->get(route('maternity.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('maternity.dashboard.redirect'))
            ->assertRedirect(route('maternity.dashboard'));
    }

    public function test_legacy_browser_routes_redirect_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::MATERNITY, 'MAT');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['maternity.view', 'maternity.dashboard.view', 'maternity.pregnancy.view']);

        $this->actingAs($user)
            ->get(route('admin.maternity.pregnancies.index'))
            ->assertRedirect(route('maternity.pregnancies.index'));

        $this->actingAs($user)
            ->get(route('admin.maternity.dashboard'))
            ->assertRedirect(route('maternity.dashboard'));

        $this->actingAs($user)
            ->getJson(route('admin.maternity.pregnancies.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_maternity_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $maternity = $this->department(DepartmentType::MATERNITY, 'MAT');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($maternity->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $maternity->id])
            ->assertRedirect(route('maternity.dashboard'));

        $maternityUser = User::factory()->create([
            'department_id' => $maternity->id,
            'email' => 'maternity-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $maternityUser->email, 'password' => 'password'])
            ->assertRedirect(route('maternity.dashboard'));
    }

    public function test_maternity_workspace_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(Arr::get(require lang_path('en/maternity.php'), 'workspace')));
        $french = array_keys(Arr::dot(Arr::get(require lang_path('fr/maternity.php'), 'workspace')));

        sort($english);
        sort($french);

        $this->assertNotEmpty($english);
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
