<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\User;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentDashboardDataService;
use App\Services\Department\DepartmentDashboardLayoutRegistry;
use App\Services\Department\DepartmentDashboardThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDashboardUiPhase6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_context_resolves_department_id_type_dashboard_theme_and_menu_profile(): void
    {
        $department = Department::create([
            'name' => 'Laboratory',
            'code' => 'LAB',
            'type' => DepartmentType::INVESTIGATION->value,
            'status' => 'active',
        ]);
        $user = User::factory()->create(['department_id' => $department->id]);

        $context = app(DepartmentContextResolver::class)->resolve($user);

        $this->assertSame($department->id, $context->department_id);
        $this->assertSame(DepartmentType::INVESTIGATION, $context->department_type);
        $this->assertSame('investigation', $context->dashboard_key);
        $this->assertSame('investigation', $context->menu_profile_key);
        $this->assertSame('investigation', $context->theme['key']);
        $this->assertFalse($context->is_global_context);
    }

    public function test_department_services_are_scoped_to_department_id_only(): void
    {
        $lab = Department::create(['name' => 'Laboratory', 'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'status' => 'active']);
        $xray = Department::create(['name' => 'X-Ray', 'code' => 'XRY', 'type' => DepartmentType::RADIOLOGY->value, 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $lab->id]);

        DB::table('service_catalog')->insert([
            ['name' => 'Full Blood Count', 'code' => 'FBC', 'category' => 'lab', 'price' => 20, 'is_active' => 1, 'is_billable' => 1, 'requires_rendering_tracking' => 0, 'department_id' => $lab->id, 'department_type' => DepartmentType::INVESTIGATION->value, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chest X-Ray', 'code' => 'CXR', 'category' => 'imaging', 'price' => 40, 'is_active' => 1, 'is_billable' => 1, 'requires_rendering_tracking' => 0, 'department_id' => $xray->id, 'department_type' => DepartmentType::RADIOLOGY->value, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user));

        $this->assertSame(['Full Blood Count'], array_column($payload['services']['rows'], 'name'));
    }

    public function test_radiology_user_does_not_see_laboratory_services(): void
    {
        $lab = Department::create(['name' => 'Laboratory', 'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'status' => 'active']);
        $xray = Department::create(['name' => 'X-Ray', 'code' => 'XRY', 'type' => DepartmentType::RADIOLOGY->value, 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $xray->id]);

        DB::table('service_catalog')->insert([
            ['name' => 'Full Blood Count', 'code' => 'FBC', 'category' => 'lab', 'price' => 20, 'is_active' => 1, 'is_billable' => 1, 'requires_rendering_tracking' => 0, 'department_id' => $lab->id, 'department_type' => DepartmentType::INVESTIGATION->value, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chest X-Ray', 'code' => 'CXR', 'category' => 'imaging', 'price' => 40, 'is_active' => 1, 'is_billable' => 1, 'requires_rendering_tracking' => 0, 'department_id' => $xray->id, 'department_type' => DepartmentType::RADIOLOGY->value, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user));

        $this->assertSame(['Chest X-Ray'], array_column($payload['services']['rows'], 'name'));
    }

    public function test_prices_and_stock_are_restricted_without_permissions(): void
    {
        $department = Department::create(['name' => 'Pharmacy', 'code' => 'PHA', 'type' => DepartmentType::PHARMACY->value, 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $department->id]);

        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user));

        $this->assertFalse($payload['services']['can_view_prices']);
        $this->assertTrue($payload['stock_usage']['restricted']);
    }

    public function test_price_permission_reveals_department_service_prices(): void
    {
        $department = Department::create(['name' => 'Laboratory', 'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $department->id]);
        Permission::findOrCreate('invoices.view', 'web');
        $user->givePermissionTo('invoices.view');

        DB::table('service_catalog')->insert([
            ['name' => 'Full Blood Count', 'code' => 'FBC', 'category' => 'lab', 'price' => 20, 'is_active' => 1, 'is_billable' => 1, 'requires_rendering_tracking' => 0, 'department_id' => $department->id, 'department_type' => DepartmentType::INVESTIGATION->value, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user));

        $this->assertTrue($payload['services']['can_view_prices']);
        $this->assertSame(20.0, $payload['services']['rows'][0]['price']);
    }

    public function test_admin_can_preview_but_non_admin_preview_is_ignored(): void
    {
        $department = Department::create(['name' => 'Pharmacy', 'code' => 'PHA', 'type' => DepartmentType::PHARMACY->value, 'status' => 'active']);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $staff = User::factory()->create(['department_id' => $department->id]);
        $controller = app(DepartmentDashboardController::class);

        $adminData = $controller->index($this->requestAs($admin, DepartmentDashboardResolver::PHARMACY))->getData();
        $this->assertSame('pharmacy', $adminData['key']);
        $this->assertTrue($adminData['is_preview']);

        $staffData = $controller->index($this->requestAs($staff, DepartmentDashboardResolver::MANAGEMENT))->getData();
        $this->assertSame('pharmacy', $staffData['key']);
        $this->assertFalse($staffData['is_preview']);
    }

    public function test_login_redirects_department_user_to_my_dashboard(): void
    {
        $department = Department::create(['name' => 'Laboratory', 'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'status' => 'active']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'email' => 'lab@example.test',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.my-dashboard'));
    }

    public function test_all_department_types_have_theme_and_layout_fallback(): void
    {
        $themes = app(DepartmentDashboardThemeRegistry::class);
        $layouts = app(DepartmentDashboardLayoutRegistry::class);

        $this->assertTrue($themes->hasThemeForEveryDepartmentType());
        $this->assertTrue($layouts->hasProfileForEveryDepartmentType());
    }

    private function requestAs(User $user, string $as): Request
    {
        $request = Request::create('/admin/my-dashboard', 'GET', ['as' => $as]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
