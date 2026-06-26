<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DepartmentDashboardChartService
{
    public function build(DepartmentDashboardContext $context): array
    {
        $accent = $context->theme['chart_accent'] ?? '#6c757d';

        return [
            'activity_trend' => $this->activityTrend($context, $accent),
            'queue_status_breakdown' => $this->statusBreakdown($context, 'visits', $this->visitsDepartmentColumn(), 'status', __('dashboards.department.charts.queue_status_breakdown')),
            'request_status_breakdown' => $this->statusBreakdown($context, 'lab_requests', 'target_department_id', 'status', __('dashboards.department.charts.request_status_breakdown')),
            'service_usage_trend' => $this->serviceUsageTrend($context, $accent),
            'stock_usage_trend' => $this->stockUsageTrend($context, $accent),
            'department_workload_by_day' => $this->workloadByDay($context, $accent),
            'revenue_trend' => $this->revenueTrend($context, $accent),
        ];
    }

    public function fallbackForType(DepartmentType $type): array
    {
        return [
            'type' => $type->value,
            'label' => $type->translatedLabel(),
            'charts' => [
                'activity_trend',
                'queue_status_breakdown',
                match ($type) {
                    DepartmentType::PHARMACY, DepartmentType::STORES => 'stock_usage_trend',
                    DepartmentType::FINANCE => 'revenue_trend',
                    DepartmentType::INVESTIGATION, DepartmentType::RADIOLOGY => 'request_status_breakdown',
                    default => 'service_usage_trend',
                },
            ],
        ];
    }

    private function activityTrend(DepartmentDashboardContext $context, string $accent): array
    {
        return $this->dailyCountDataset(
            key: 'activity_trend',
            title: __('dashboards.department.charts.activity_trend'),
            table: 'invoice_items',
            dateColumn: 'created_at',
            departmentColumn: 'department_id',
            context: $context,
            accent: $accent,
        );
    }

    private function serviceUsageTrend(DepartmentDashboardContext $context, string $accent): array
    {
        return $this->dailyCountDataset(
            key: 'service_usage_trend',
            title: __('dashboards.department.charts.service_usage_trend'),
            table: 'invoice_items',
            dateColumn: 'created_at',
            departmentColumn: 'department_id',
            context: $context,
            accent: $accent,
        );
    }

    private function workloadByDay(DepartmentDashboardContext $context, string $accent): array
    {
        return $this->dailyCountDataset(
            key: 'department_workload_by_day',
            title: __('dashboards.department.charts.workload_by_day'),
            table: 'visits',
            dateColumn: 'created_at',
            departmentColumn: $this->visitsDepartmentColumn(),
            context: $context,
            accent: $accent,
        );
    }

    private function stockUsageTrend(DepartmentDashboardContext $context, string $accent): array
    {
        if (! ($context->user->can('store.purchase.view') || $context->user->can('pharmacy.stock.manage'))) {
            return $this->restrictedDataset('stock_usage_trend', __('dashboards.department.charts.stock_usage_trend'));
        }

        return $this->dailyCountDataset(
            key: 'stock_usage_trend',
            title: __('dashboards.department.charts.stock_usage_trend'),
            table: 'stock_movements',
            dateColumn: Schema::hasColumn('stock_movements', 'movement_date') ? 'movement_date' : 'created_at',
            departmentColumn: 'department_id',
            context: $context,
            accent: $accent,
        );
    }

    private function revenueTrend(DepartmentDashboardContext $context, string $accent): array
    {
        if (! ($context->user->can('reports.financial_values.view') || $context->user->can('invoices.view'))) {
            return $this->restrictedDataset('revenue_trend', __('dashboards.department.charts.revenue_trend'));
        }

        return $this->dailySumDataset(
            key: 'revenue_trend',
            title: __('dashboards.department.charts.revenue_trend'),
            table: 'invoice_items',
            sumColumn: 'total_price',
            dateColumn: 'created_at',
            departmentColumn: 'department_id',
            context: $context,
            accent: $accent,
            format: 'currency',
        );
    }

    private function dailyCountDataset(string $key, string $title, string $table, string $dateColumn, string $departmentColumn, DepartmentDashboardContext $context, string $accent): array
    {
        return $this->dailyDataset($key, $title, $table, $dateColumn, $departmentColumn, $context, $accent, 'count');
    }

    private function dailySumDataset(string $key, string $title, string $table, string $sumColumn, string $dateColumn, string $departmentColumn, DepartmentDashboardContext $context, string $accent, string $format): array
    {
        return $this->dailyDataset($key, $title, $table, $dateColumn, $departmentColumn, $context, $accent, 'sum', $sumColumn, $format);
    }

    private function dailyDataset(string $key, string $title, string $table, string $dateColumn, string $departmentColumn, DepartmentDashboardContext $context, string $accent, string $mode, ?string $sumColumn = null, string $format = 'number'): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $labels[] = $date->format('d M');
            $values[] = $this->tableValue($table, $dateColumn, $departmentColumn, $context, $date->toDateString(), $mode, $sumColumn);
        }

        return [
            'key' => $key,
            'title' => $title,
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [[
                'label' => $title,
                'data' => $values,
                'backgroundColor' => $accent,
                'borderColor' => $accent,
            ]],
            'format' => $format,
            'empty_state' => array_sum($values) <= 0,
            'restricted' => false,
        ];
    }

    private function statusBreakdown(DepartmentDashboardContext $context, string $table, string $departmentColumn, string $statusColumn, string $title): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $statusColumn)) {
            return $this->emptyDataset($table.'_status_breakdown', $title, 'doughnut');
        }

        try {
            $rows = DB::table($table)
                ->selectRaw($statusColumn.' as status, COUNT(*) as total')
                ->when($context->department_id && Schema::hasColumn($table, $departmentColumn), fn ($q) => $q->where($departmentColumn, $context->department_id))
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

    private function tableValue(string $table, string $dateColumn, string $departmentColumn, DepartmentDashboardContext $context, string $date, string $mode, ?string $sumColumn = null): float|int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return 0;
        }
        if ($mode === 'sum' && (! $sumColumn || ! Schema::hasColumn($table, $sumColumn))) {
            return 0;
        }

        try {
            $query = DB::table($table)->whereDate($dateColumn, $date);
            if ($context->department_id && Schema::hasColumn($table, $departmentColumn)) {
                $query->where($departmentColumn, $context->department_id);
            }

            return $mode === 'sum' ? round((float) $query->sum($sumColumn), 2) : (int) $query->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function restrictedDataset(string $key, string $title): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'type' => 'bar',
            'labels' => [],
            'datasets' => [],
            'format' => 'number',
            'empty_state' => true,
            'restricted' => true,
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
        return Schema::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';
    }

    private function statusLabel(string $status): string
    {
        $key = "statuses.default.$status";

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : str($status)->replace(['_', '-'], ' ')->headline()->toString();
    }
}
