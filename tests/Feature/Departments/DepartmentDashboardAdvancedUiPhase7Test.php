<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Services\Department\DepartmentComparisonService;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\Department\DepartmentDashboardChartService;
use App\Services\Department\DepartmentDashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDashboardAdvancedUiPhase7Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_with_one_department_does_not_get_switcher(): void
    {
        $department = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $department->id]);

        $context = app(DepartmentContextResolver::class)->resolve($user);

        $this->assertFalse($context->can_switch_department);
        $this->assertSame($department->id, $context->current_department_id);
    }

    public function test_user_with_multiple_departments_can_switch_only_to_assigned_department(): void
    {
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $xray = $this->department('X-Ray', DepartmentType::RADIOLOGY);
        $pharmacy = $this->department('Pharmacy', DepartmentType::PHARMACY);
        $user = User::factory()->create(['department_id' => $lab->id]);
        Permission::findOrCreate('departments.context.switch', 'web');
        $user->givePermissionTo('departments.context.switch');
        $user->departments()->attach($lab->id, ['is_primary' => true]);
        $user->departments()->attach($xray->id);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $xray->id])
            ->assertRedirect();

        $this->assertSame($xray->id, session(DepartmentContextSwitcherService::SESSION_KEY));
        $this->assertSame($xray->id, app(DepartmentContextResolver::class)->resolve($user, request())->current_department_id);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $pharmacy->id])
            ->assertRedirect();

        $this->assertNull(session(DepartmentContextSwitcherService::SESSION_KEY));
    }

    public function test_invalid_session_department_is_cleared_safely(): void
    {
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $lab->id]);
        session([DepartmentContextSwitcherService::SESSION_KEY => 99999]);

        $context = app(DepartmentContextResolver::class)->resolve($user, request());

        $this->assertSame($lab->id, $context->current_department_id);
        $this->assertNull(session(DepartmentContextSwitcherService::SESSION_KEY));
    }

    public function test_dashboard_data_scope_changes_after_department_switch(): void
    {
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $xray = $this->department('X-Ray', DepartmentType::RADIOLOGY);
        $user = User::factory()->create(['department_id' => $lab->id]);
        $user->departments()->attach($lab->id, ['is_primary' => true]);
        $user->departments()->attach($xray->id);

        $this->service('Full Blood Count', $lab);
        $this->service('Chest X-Ray', $xray);

        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user));
        $this->assertSame(['Full Blood Count'], array_column($payload['services']['rows'], 'name'));

        session([DepartmentContextSwitcherService::SESSION_KEY => $xray->id]);
        $payload = app(DepartmentDashboardDataService::class)->build(app(DepartmentContextResolver::class)->resolve($user, request()));
        $this->assertSame(['Chest X-Ray'], array_column($payload['services']['rows'], 'name'));
    }

    public function test_chart_service_builds_only_the_charts_a_type_uses(): void
    {
        // Phase 8.2: the chart service builds only the charts the showcase renders,
        // not all seven. Finance uses the activity trend only.
        $department = $this->department('Finance', DepartmentType::FINANCE);
        $user = User::factory()->create(['department_id' => $department->id]);
        $context = app(DepartmentContextResolver::class)->resolve($user);

        $charts = app(DepartmentDashboardChartService::class)->build($context);

        $this->assertArrayHasKey('activity_trend', $charts);
        // Previously-built-but-never-rendered charts are now skipped.
        $this->assertArrayNotHasKey('revenue_trend', $charts);
        $this->assertArrayNotHasKey('service_usage_trend', $charts);
        $this->assertArrayNotHasKey('stock_usage_trend', $charts);
        $this->assertArrayNotHasKey('department_workload_by_day', $charts);
    }

    public function test_comparison_route_is_permission_protected_and_lists_only_allowed_departments(): void
    {
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $xray = $this->department('X-Ray', DepartmentType::RADIOLOGY);
        $pharmacy = $this->department('Pharmacy', DepartmentType::PHARMACY);
        $user = User::factory()->create(['department_id' => $lab->id]);
        $user->departments()->attach($lab->id, ['is_primary' => true]);
        $user->departments()->attach($xray->id);

        $this->actingAs($user)->get(route('admin.reports.department-comparison.index'))->assertForbidden();

        Permission::findOrCreate('reports.department_comparison.view', 'web');
        $user->givePermissionTo('reports.department_comparison.view');

        $payload = app(DepartmentComparisonService::class)->build($user->fresh(), []);
        $this->assertEqualsCanonicalizing([$lab->id, $xray->id], array_column($payload['rows'], 'department_id'));
        $this->assertNotContains($pharmacy->id, array_column($payload['rows'], 'department_id'));
    }

    public function test_comparison_export_hides_restricted_financial_and_stock_columns(): void
    {
        $department = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $department->id]);
        Permission::findOrCreate('reports.department_comparison.view', 'web');
        Permission::findOrCreate('reports.department_comparison.export', 'web');
        Permission::findOrCreate('reports.view', 'web');
        $user->givePermissionTo(['reports.view', 'reports.department_comparison.view', 'reports.department_comparison.export']);

        $response = $this->actingAs($user)->get(route('admin.reports.department-comparison.export'));

        $response->assertOk();
        $response->assertDontSee(__('reports.department_comparison.revenue'), false);
        $response->assertDontSee(__('reports.department_comparison.stock_alerts'), false);
    }

    private function department(string $name, DepartmentType $type): Department
    {
        return Department::create([
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)).random_int(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);
    }

    private function service(string $name, Department $department): void
    {
        DB::table('service_catalog')->insert([
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)).random_int(100, 999),
            'category' => 'general',
            'price' => 20,
            'is_active' => 1,
            'is_billable' => 1,
            'requires_rendering_tracking' => 0,
            'department_id' => $department->id,
            'department_type' => $department->type?->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
