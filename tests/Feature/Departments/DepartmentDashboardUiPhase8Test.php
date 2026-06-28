<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\Department\DepartmentDashboardDataService;
use App\Services\Department\DepartmentDashboardDrilldownUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDashboardUiPhase8Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_kpi_card_drilldown_uses_current_department_filter(): void
    {
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $lab->id]);
        Permission::findOrCreate('visits.view', 'web');
        $user->givePermissionTo('visits.view');

        $context = app(DepartmentContextResolver::class)->resolve($user);
        $url = app(DepartmentDashboardDrilldownUrlBuilder::class)->build($context, 'visits_today');

        $this->assertStringContainsString('department_id='.$lab->id, $url);
        $this->assertStringContainsString('scope=current_department', $url);
    }

    public function test_drilldown_url_builds_for_defined_metrics_only(): void
    {
        // Phase 8.4: the builder no longer re-gates by permission (visibility is
        // unified at the data-service/capability layer). It builds a scoped URL for
        // a defined metric and returns null for an unknown one.
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $lab->id]);
        $context = app(DepartmentContextResolver::class)->resolve($user);

        $this->assertNull(app(DepartmentDashboardDrilldownUrlBuilder::class)->build($context, 'unknown_metric'));

        $url = app(DepartmentDashboardDrilldownUrlBuilder::class)->build($context, 'pending_requests');
        $this->assertNotNull($url);
        $this->assertStringContainsString('department_id='.$lab->id, $url);
    }

    public function test_chart_card_renders_empty_and_restricted_states(): void
    {
        $empty = view('admin.dashboards.department.partials.chart-card', [
            'chart' => ['title' => 'Empty', 'labels' => [], 'datasets' => [], 'empty_state' => true, 'restricted' => false],
            'theme' => ['accent_class' => 'primary', 'chart_accent' => '#0d6efd'],
        ])->render();

        $restricted = view('admin.dashboards.department.partials.chart-card', [
            'chart' => ['title' => 'Restricted', 'labels' => [], 'datasets' => [], 'empty_state' => true, 'restricted' => true],
            'theme' => ['accent_class' => 'primary', 'chart_accent' => '#0d6efd'],
        ])->render();

        $this->assertStringContainsString(__('dashboards.department.metric_unavailable'), $empty);
        $this->assertStringContainsString(__('dashboards.department.restricted_metric'), $restricted);
    }

    public function test_department_assignment_ui_is_permission_protected(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.departments.index', $target))
            ->assertForbidden();
    }

    public function test_admin_can_assign_user_to_department_and_set_primary(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $lab = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $xray = $this->department('X-Ray', DepartmentType::RADIOLOGY);
        $this->give($admin, ['users.view', 'users.departments.view', 'users.departments.manage']);

        $this->actingAs($admin)
            ->post(route('admin.users.departments.store', $target), [
                'department_id' => $lab->id,
                'is_primary' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('department_user', [
            'user_id' => $target->id,
            'department_id' => $lab->id,
            'is_primary' => true,
        ]);
        $this->assertSame($lab->id, $target->fresh()->department_id);
        $this->assertDatabaseHas('activity_log', ['event' => 'USER_DEPARTMENT_ASSIGNED']);

        $target->departments()->attach($xray->id);
        $this->actingAs($admin)
            ->patch(route('admin.users.departments.primary', [$target, $xray]))
            ->assertRedirect();

        $this->assertSame($xray->id, $target->fresh()->department_id);
        $this->assertDatabaseHas('department_user', [
            'user_id' => $target->id,
            'department_id' => $xray->id,
            'is_primary' => true,
        ]);
    }

    public function test_duplicate_active_assignment_is_blocked_and_user_cannot_assign_self(): void
    {
        $admin = User::factory()->create();
        $department = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $this->give($admin, ['users.view', 'users.departments.view', 'users.departments.manage']);
        $admin->departments()->attach($department->id);

        $this->actingAs($admin)
            ->post(route('admin.users.departments.store', $admin), ['department_id' => $department->id])
            ->assertForbidden();

        $target = User::factory()->create();
        $target->departments()->attach($department->id);

        $this->actingAs($admin)
            ->post(route('admin.users.departments.store', $target), ['department_id' => $department->id])
            ->assertSessionHasErrors('department_id');
    }

    public function test_expired_and_future_assignments_are_not_available_for_switching(): void
    {
        $current = $this->department('Current', DepartmentType::CONSULTATION);
        $expired = $this->department('Expired', DepartmentType::RADIOLOGY);
        $future = $this->department('Future', DepartmentType::PHARMACY);
        $user = User::factory()->create(['department_id' => $current->id]);
        $user->departments()->attach($expired->id, ['ends_at' => now()->subDay()]);
        $user->departments()->attach($future->id, ['starts_at' => now()->addDay()]);

        $available = app(DepartmentContextSwitcherService::class)->availableDepartments($user)->pluck('id')->all();

        $this->assertContains($current->id, $available);
        $this->assertNotContains($expired->id, $available);
        $this->assertNotContains($future->id, $available);
    }

    public function test_comparison_report_date_presets_work_and_export_button_hides_without_permission(): void
    {
        $department = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['reports.view', 'reports.department_comparison.view']);

        $response = $this->actingAs($user)
            ->get(route('admin.reports.department-comparison.index', ['preset' => 'last_30_days']));

        $response->assertOk();
        $response->assertSessionHas('department_comparison_filters.date_from', today()->subDays(29)->toDateString());
        $response->assertDontSee(__('reports.department_comparison.export'));
    }

    public function test_showcase_dashboard_body_compiles(): void
    {
        $department = $this->department('Laboratory', DepartmentType::INVESTIGATION);
        $user = User::factory()->create(['department_id' => $department->id]);
        $context = app(DepartmentContextResolver::class)->resolve($user);

        $payload = app(DepartmentDashboardDataService::class)->build($context);
        // The production render path is types/{key} → _chrome → {key}_showcase.
        $html = view('admin.dashboards.department.partials.layouts.investigation_showcase', array_merge($payload, [
            'context' => $context,
            'theme' => $context->theme,
        ]))->render();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('card', $html);
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

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }
}
