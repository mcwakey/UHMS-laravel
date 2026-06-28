<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Department\DepartmentDashboardCacheService;
use App\Services\Department\DepartmentDashboardChartRegistry;
use App\Services\Department\DepartmentTrendRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function department(DepartmentType $type, string $name, string $code): Department
    {
        return Department::create(['name' => $name, 'code' => $code, 'type' => $type->value, 'status' => 'active']);
    }

    private function payloadFor(User $user): array
    {
        $request = Request::create('/admin/my-dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        return app(DepartmentDashboardController::class)->index($request)->getData();
    }

    public function test_dashboard_load_stays_well_under_the_pre_optimization_baseline(): void
    {
        $dept = $this->department(DepartmentType::CONSULTATION, 'OPD', 'OPD');
        $user = User::factory()->create(['department_id' => $dept->id]);

        // Warm the (process-static) schema cache so we measure steady-state.
        $this->payloadFor($user);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->payloadFor($user);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Baseline was ~165 queries; Phase 8.2 target is 20–30.
        $this->assertLessThan(40, $count, "Dashboard issued {$count} queries (target < 40)");
    }

    public function test_chart_registry_builds_only_required_charts(): void
    {
        $registry = app(DepartmentDashboardChartRegistry::class);

        $this->assertSame(['activity_trend'], $registry->chartServiceChartsFor(DepartmentType::FINANCE));
        $this->assertContains('request_status_breakdown', $registry->chartServiceChartsFor(DepartmentType::INVESTIGATION));
        $this->assertContains('queue_status_breakdown', $registry->chartServiceChartsFor(DepartmentType::CONSULTATION));

        // The four charts no showcase rendered are never requested.
        foreach ([DepartmentType::CONSULTATION, DepartmentType::FINANCE, DepartmentType::INVESTIGATION] as $type) {
            $charts = $registry->chartServiceChartsFor($type);
            $this->assertNotContains('revenue_trend', $charts);
            $this->assertNotContains('service_usage_trend', $charts);
            $this->assertNotContains('stock_usage_trend', $charts);
            $this->assertNotContains('department_workload_by_day', $charts);
        }

        $this->assertTrue($registry->needsStockStatus(DepartmentType::PHARMACY));
        $this->assertFalse($registry->needsStockStatus(DepartmentType::CONSULTATION));
    }

    public function test_trend_repository_returns_seven_day_series_in_a_single_query(): void
    {
        $dept = $this->department(DepartmentType::CONSULTATION, 'OPD', 'OPD');
        $user = User::factory()->create(['department_id' => $dept->id]);
        Visit::factory()->count(2)->create(['current_department_id' => $dept->id, 'created_by' => $user->id, 'created_at' => now()]);

        $repo = app(DepartmentTrendRepository::class);
        // Warm schema cache (hasColumn checks) so we count only the data query.
        $repo->dailySeries('visits', 'created_at', 'current_department_id', $dept->id, 7);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $series = $repo->dailySeries('visits', 'created_at', 'current_department_id', $dept->id, 7);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(7, $series);
        $this->assertSame(2, end($series), 'today should reflect the two visits');
        $this->assertSame(1, $queries, 'one grouped GROUP BY DATE query, not seven');
    }

    public function test_cache_serves_the_second_load_with_fewer_queries(): void
    {
        DepartmentDashboardCacheService::enable();
        Cache::flush();

        try {
            $dept = $this->department(DepartmentType::CONSULTATION, 'OPD', 'OPD');
            $user = User::factory()->create(['department_id' => $dept->id]);

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->payloadFor($user);
            $first = count(DB::getQueryLog());

            DB::flushQueryLog();
            $this->payloadFor($user);
            $second = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThan($first, $second, "cached load ({$second}) should issue fewer queries than the first ({$first})");
        } finally {
            DepartmentDashboardCacheService::reset();
            Cache::flush();
        }
    }
}
