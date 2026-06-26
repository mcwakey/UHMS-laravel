<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Reporting\DepartmentMetricsController;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Services\Dashboard\DepartmentMetricsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DepartmentMetricsRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function department(DepartmentType $type, string $code, string $status = 'active'): Department
    {
        return Department::create([
            'name' => 'Dept '.$code,
            'code' => $code,
            'type' => $type->value,
            'status' => $status,
        ]);
    }

    private function service(Department $department, string $code): void
    {
        ServiceCatalog::create([
            'name' => 'Service '.$code,
            'code' => $code,
            'category' => 'consultation',
            'price' => 10,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_summary_rolls_up_metrics_by_department_type(): void
    {
        $opd = $this->department(DepartmentType::CONSULTATION, 'OPD');
        $this->department(DepartmentType::CONSULTATION, 'PED', 'inactive');
        $this->department(DepartmentType::EMERGENCY, 'EMR');

        $this->service($opd, 'CONS1');
        $this->service($opd, 'CONS2');

        $summary = app(DepartmentMetricsRegistry::class)->summary();
        $rows = collect($summary['rows'])->keyBy('type');

        // Consultation: 2 departments (1 active), 2 services.
        $this->assertSame(2, $rows['consultation']['departments']);
        $this->assertSame(1, $rows['consultation']['active_departments']);
        $this->assertSame(2, $rows['consultation']['services']);
        $this->assertTrue($rows['consultation']['clinical']);
        $this->assertSame(0, $rows['consultation']['consultation_routes']);
        $this->assertSame(0.0, $rows['consultation']['revenue']);

        // Emergency: 1 department, 0 services.
        $this->assertSame(1, $rows['emergency']['departments']);
        $this->assertSame(0, $rows['emergency']['services']);

        // Rows are sorted by department count desc → consultation first.
        $this->assertSame('consultation', $summary['rows'][0]['type']);

        // Totals.
        $this->assertSame(3, $summary['totals']['departments']);
        $this->assertSame(2, $summary['totals']['services']);
    }

    public function test_metrics_for_a_single_type(): void
    {
        $this->department(DepartmentType::RADIOLOGY, 'RAD');
        $this->department(DepartmentType::RADIOLOGY, 'USG');

        $metrics = app(DepartmentMetricsRegistry::class)->metricsForType(DepartmentType::RADIOLOGY);

        $this->assertSame('radiology', $metrics['type']);
        $this->assertSame(2, $metrics['departments']);
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('consultation_routes', $metrics);
    }

    public function test_unmapped_legacy_type_values_are_ignored_in_summary(): void
    {
        // A row with a type string that is not a known DepartmentType case.
        // Inserted via the query builder to bypass the model's enum cast (which
        // would reject an unknown value on write).
        \Illuminate\Support\Facades\DB::table('departments')->insert([
            'name' => 'Legacy', 'code' => 'LEG', 'type' => 'legacy_unknown', 'status' => 'active',
            'is_stock_managed' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->department(DepartmentType::PHARMACY, 'PHR');

        $rows = collect(app(DepartmentMetricsRegistry::class)->summary()['rows'])->pluck('type');

        $this->assertTrue($rows->contains('pharmacy'));
        $this->assertFalse($rows->contains('legacy_unknown'));
    }

    public function test_controller_returns_summary_json(): void
    {
        $this->department(DepartmentType::CONSULTATION, 'OPD');

        // Unit-level (bypasses the reports module/permission route middleware).
        $response = app(DepartmentMetricsController::class)->index(
            Request::create('/admin/reports/department-metrics', 'GET')
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('rows', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertSame(1, $data['totals']['departments']);
    }

    public function test_route_is_registered_under_reports(): void
    {
        $this->assertSame(
            url('/admin/reports/department-metrics'),
            route('admin.reports.department-metrics'),
        );
    }
}
