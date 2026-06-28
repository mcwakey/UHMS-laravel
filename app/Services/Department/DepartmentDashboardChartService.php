<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\DB;
use Throwable;

class DepartmentDashboardChartService
{
    public function __construct(
        private DepartmentDashboardChartRegistry $registry,
        private DepartmentTrendRepository $trends,
    ) {}

    /** Build only the charts this department type's showcase actually renders. */
    public function build(DepartmentDashboardContext $context): array
    {
        $accent = $context->theme['chart_accent'] ?? '#6c757d';
        $out = [];

        foreach ($this->registry->chartServiceChartsFor($context->department_type) as $key) {
            $chart = $this->chartFor($key, $context, $accent);
            if ($chart !== null) {
                $out[$key] = $chart;
            }
        }

        return $out;
    }

    private function chartFor(string $key, DepartmentDashboardContext $context, string $accent): ?array
    {
        return match ($key) {
            'activity_trend' => $this->activityTrend($context, $accent),
            'queue_status_breakdown' => $this->statusBreakdown($context, 'visits', $this->visitsDepartmentColumn(), 'status', __('dashboards.department.charts.queue_status_breakdown')),
            'request_status_breakdown' => $this->statusBreakdown($context, 'lab_requests', 'target_department_id', 'status', __('dashboards.department.charts.request_status_breakdown')),
            default => null,
        };
    }

    private function activityTrend(DepartmentDashboardContext $context, string $accent): array
    {
        // One grouped query (was a 7-iteration loop), shared with the KPI sparklines.
        $series = $this->trends->dailySeriesWithLabels('invoice_items', 'created_at', 'department_id', $context->department_id, 7);

        return [
            'key' => 'activity_trend',
            'title' => __('dashboards.department.charts.activity_trend'),
            'type' => 'bar',
            'labels' => $series['labels'],
            'datasets' => [[
                'label' => __('dashboards.department.charts.activity_trend'),
                'data' => $series['values'],
                'backgroundColor' => $accent,
                'borderColor' => $accent,
            ]],
            'format' => 'number',
            'empty_state' => array_sum($series['values']) <= 0,
            'restricted' => false,
        ];
    }

    private function statusBreakdown(DepartmentDashboardContext $context, string $table, string $departmentColumn, string $statusColumn, string $title): array
    {
        if (! DepartmentSchemaCache::hasTable($table) || ! DepartmentSchemaCache::hasColumn($table, $statusColumn)) {
            return $this->emptyDataset($table.'_status_breakdown', $title, 'doughnut');
        }

        try {
            $rows = DB::table($table)
                ->selectRaw($statusColumn.' as status, COUNT(*) as total')
                ->when($context->department_id && DepartmentSchemaCache::hasColumn($table, $departmentColumn), fn ($q) => $q->where($departmentColumn, $context->department_id))
                ->groupBy($statusColumn)
                ->limit(8)
                ->get();
        } catch (Throwable) {
            $rows = collect();
        }

        return [
            'key' => $table.'_status_breakdown',
            'title' => $title,
            'type' => 'doughnut',
            'labels' => $rows->pluck('status')->map(fn ($status) => $this->statusLabel((string) $status))->all(),
            'datasets' => [[
                'label' => $title,
                'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
                'backgroundColor' => ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0', '#6f42c1', '#fd7e14'],
            ]],
            'format' => 'number',
            'empty_state' => $rows->isEmpty(),
            'restricted' => false,
        ];
    }

    private function emptyDataset(string $key, string $title, string $type): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'type' => $type,
            'labels' => [],
            'datasets' => [],
            'format' => 'number',
            'empty_state' => true,
            'restricted' => false,
        ];
    }

    private function visitsDepartmentColumn(): string
    {
        return DepartmentSchemaCache::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';
    }

    private function statusLabel(string $status): string
    {
        $key = "statuses.default.$status";

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : str($status)->replace(['_', '-'], ' ')->headline()->toString();
    }
}
