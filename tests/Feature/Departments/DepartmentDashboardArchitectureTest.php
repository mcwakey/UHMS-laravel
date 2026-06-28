<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentDashboardChartService;
use App\Services\Department\DepartmentDashboardDrilldownUrlBuilder;
use App\Services\Department\DepartmentDashboardLayoutRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Guards the Phase 8.6 cleanup: the orphan render path, the layout-family registry
 * metadata and the retired chart builders must stay removed.
 */
class DepartmentDashboardArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphan_render_paths_are_removed(): void
    {
        foreach ([
            'admin.dashboards.department.show',
            'admin.dashboards.department.partials.dashboard-shell',
            'admin.dashboards.department.partials.dashboard-body',
        ] as $view) {
            $this->assertFalse(View::exists($view), "Orphan view {$view} should be removed");
        }

        foreach ([
            'clinical_queue', 'emergency_command', 'diagnostic_workbench', 'imaging_workbench',
            'surgery_board', 'ward_board', 'dispensing_stock', 'finance_control',
            'stores_inventory', 'records_office', 'generic_department',
        ] as $family) {
            $this->assertFalse(
                View::exists('admin.dashboards.department.partials.layouts.'.$family),
                "Retired family layout {$family} should be removed",
            );
        }
    }

    public function test_every_type_file_and_its_shared_helpers_exist(): void
    {
        foreach (['accounting', 'admission', 'billing', 'blood_bank', 'claims', 'consultation',
            'emergency', 'generic', 'hr', 'investigation', 'management', 'pharmacy',
            'reception', 'stock', 'theatre'] as $key) {
            $this->assertTrue(View::exists('admin.dashboards.department.types.'.$key), "Missing type view {$key}");
        }

        foreach (['_chrome', 'identity-widget', 'priority-banner', '_list-card',
            'layouts._primary-cards', 'layouts._secondary-cards'] as $partial) {
            $this->assertTrue(View::exists('admin.dashboards.department.partials.'.$partial), "Missing partial {$partial}");
        }
    }

    public function test_layout_family_registry_metadata_is_gone(): void
    {
        $registry = app(DepartmentDashboardLayoutRegistry::class);

        $this->assertFalse(method_exists($registry, 'layoutFamilyFor'));
        $this->assertFalse(method_exists($registry, 'heroVariantFor'));
        $this->assertFalse(method_exists($registry, 'hasLayoutFamilyForEveryDepartmentType'));
        $this->assertFalse(defined(DepartmentDashboardLayoutRegistry::class.'::LAYOUT_FAMILIES'));

        // But it still resolves a card profile + name for every type.
        $this->assertTrue($registry->hasProfileForEveryDepartmentType());
        $this->assertArrayNotHasKey('layout_family', $registry->for(DepartmentType::PHARMACY));
    }

    public function test_chart_service_never_builds_retired_charts(): void
    {
        $service = app(DepartmentDashboardChartService::class);
        $resolver = app(DepartmentContextResolver::class);
        $retired = ['service_usage_trend', 'stock_usage_trend', 'department_workload_by_day', 'revenue_trend'];

        foreach ([DepartmentType::CONSULTATION, DepartmentType::PHARMACY, DepartmentType::FINANCE, DepartmentType::INVESTIGATION] as $i => $type) {
            $dept = Department::create(['name' => $type->value, 'code' => 'AR'.$i, 'type' => $type->value, 'status' => 'active']);
            $user = User::factory()->create(['department_id' => $dept->id]);
            $charts = $service->build($resolver->resolve($user));

            foreach ($retired as $key) {
                $this->assertArrayNotHasKey($key, $charts, "Chart {$key} should no longer be built");
            }
        }

        $this->assertFalse(method_exists($service, 'fallbackForType'));
    }

    public function test_drilldown_builder_has_no_dead_permission_map(): void
    {
        $this->assertFalse(method_exists(app(DepartmentDashboardDrilldownUrlBuilder::class), 'permissionFor'));
    }
}
