<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DepartmentDashboardDataService
{
    private DepartmentDashboardContext $context;
    private User $user;

    public function __construct(
        private DepartmentDashboardLayoutRegistry $layouts,
        private DepartmentDashboardChartService $charts,
        private DepartmentDashboardDrilldownUrlBuilder $drilldowns,
    ) {}

    public function build(DepartmentDashboardContext $context): array
    {
        $this->context = $context;
        $this->user = $context->user;

        $layout = $this->layouts->for($context->department_type);
        $services = $this->departmentServices();
        $stockUsage = $this->stockUsage();

        $charts = $this->charts->build($context);
        // Stock-bearing dashboards (pharmacy/stores) get an In-stock vs Low-stock donut.
        $charts['stock_status_breakdown'] = $this->stockStatusBreakdown();

        return [
            'layout_profile' => $layout,
            'primary_cards' => $this->cards($layout['primary_cards'] ?? []),
            'secondary_cards' => $this->cards($layout['secondary_cards'] ?? [], true),
            'work_queue' => $this->workQueue((string) ($layout['main_queue'] ?? 'department_activity')),
            'quick_actions' => $this->quickActions(),
            'services' => $services,
            'stock_usage' => $stockUsage,
            'trends' => $this->trends(),
            'charts' => $charts,
            'activities' => $this->activities(),
            'restricted' => $this->restrictedCards(),
            'empty_states' => [],
        ];
    }

    /**
     * In-stock vs low-stock donut for stock-bearing departments. Reuses stockCount();
     * restricted when the user can't view stock, empty when there are no items.
     *
     * @return array<string, mixed>
     */
    private function stockStatusBreakdown(): array
    {
        $title = __('dashboards.department.charts.stock_status');

        if (! ($this->user->can('store.purchase.view') || $this->user->can('pharmacy.stock.manage'))) {
            return ['key' => 'stock_status_breakdown', 'title' => $title, 'type' => 'doughnut', 'restricted' => true, 'labels' => [], 'datasets' => []];
        }

        $items = $this->stockCount('items');
        $low = min($this->stockCount('low'), $items);
        $ok = max($items - $low, 0);

        return [
            'key' => 'stock_status_breakdown',
            'title' => $title,
            'type' => 'doughnut',
            'total_label' => __('dashboards.department.charts.stock_items'),
            'labels' => [__('dashboards.department.charts.in_stock'), __('dashboards.department.charts.low_stock')],
            'datasets' => [[
                'label' => $title,
                'data' => [$ok, $low],
                'backgroundColor' => ['#198754', '#dc3545'],
            ]],
            'empty_state' => $items <= 0,
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    private function cards(array $keys, bool $compact = false): array
    {
        $cards = [];
        foreach ($keys as $key) {
            $cards[] = $this->metricCard($key, $compact);
        }

        return array_values(array_filter($cards));
    }

    private function metricCard(string $key, bool $compact = false): ?array
    {
        $route = $this->drilldowns->build($this->context, $key);
        $format = 'number';
        $variant = $compact ? 'secondary' : $this->context->theme['accent_class'];
        $icon = $compact ? 'ti-circle-dot' : ($this->context->theme['icon'] ?? 'ti-layout-dashboard');

        $value = match ($key) {
            'services_count' => $this->countTable('service_catalog', fn ($q) => $this->scopeDepartment($q, 'department_id')->where('is_active', true)),
            'staff_count' => $this->countTable('users', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereNull('deleted_at')),
            'visits_today' => $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->whereDate('created_at', today())),
            'activity_today' => $this->countTable('invoice_items', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('created_at', today())),
            'appointments_today' => $this->countTable('appointments', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('appointment_date', today())),
            'waiting_queue' => $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->whereIn('status', ['queued', 'waiting', 'checked_in'])),
            'completed_today', 'completed_results_today', 'completed_imaging_today' => $this->completedToday(),
            'department_revenue_today', 'revenue_today' => $this->restrictedSum('invoice_items', 'total_price', 'invoices.view', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('created_at', today())),
            'pending_requests', 'pending_imaging' => $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->whereIn('status', ['pending', 'requested'])),
            'samples_awaiting_acceptance' => $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->whereIn('status', ['sample_collected', 'received', 'accepted'])),
            'scheduled_imaging', 'scheduled' => $this->countTable('appointments', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['scheduled', 'confirmed'])),
            'pending_prescriptions' => $this->countTable('prescriptions', fn ($q) => $q->whereIn('status', ['pending', 'billed', 'partially_billed'])),
            'dispensed_today' => $this->countTable('prescriptions', fn ($q) => $q->where('status', 'dispensed')->whereDate('updated_at', today())),
            'low_stock' => $this->stockCount('low'),
            'stock_items' => $this->stockCount('items'),
            'stock_requests' => $this->countTable('stock_requisitions', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['submitted', 'pending'])),
            'stock_issues' => $this->countTable('stock_movements', fn ($q) => $q->where('direction', 'out')->whereDate('created_at', today())),
            'active_cases', 'critical_cases' => $this->countTable('emergency_cases', fn ($q) => $q->whereIn('status', ['active', 'in_progress'])),
            'active_admissions' => $this->countTable('admissions', fn ($q) => $q->where('status', 'admitted')->when($this->context->department_id, fn ($query) => $this->scopeDepartmentIfColumnExists($query, 'department_id'))),
            'beds_occupied' => $this->countTable('beds', fn ($q) => $q->where('status', 'occupied')),
            'vitals_due', 'discharges_pending' => 0,
            'invoices_today' => $this->restrictedCount('invoices', 'invoices.view', fn ($q) => $q->whereDate('created_at', today())),
            'payments_today' => $this->restrictedSum('payments', 'amount', 'payments.view', fn ($q) => $q->whereDate('created_at', today())),
            'receivables' => $this->restrictedSum('invoices', 'balance', 'invoices.view', fn ($q) => $q->whereIn('status', ['pending', 'partially_paid'])),
            'in_theatre' => $this->countTable('procedure_requests', fn ($q) => $q->whereIn('status', ['pre_op', 'anaesthesia', 'in_surgery', 'surgery_done', 'post_op'])),
            default => 0,
        };

        if ($key === 'department_revenue_today' || $key === 'payments_today' || $key === 'receivables') {
            $format = 'currency';
            $icon = 'ti-cash-banknote';
            $variant = 'success';
        }

        $card = [
            'key' => $key,
            'title' => __("dashboards.department.metrics.$key"),
            'value' => $value,
            'icon' => $icon,
            'variant' => $variant,
            'route' => $route,
            'format' => $format,
            'restricted' => is_array($value) && ($value['restricted'] ?? false),
        ];

        // Primary cards get an inline 7-day sparkline + delta (admin-template style).
        if (! $compact && ! $card['restricted']) {
            $spark = $this->metricSpark($key);
            if ($spark !== null && array_sum($spark) > 0) {
                $first = (float) ($spark[0] ?? 0);
                $last = (float) (end($spark) ?: 0);
                $card['spark'] = $spark;
                $card['delta'] = $first > 0 ? (int) round((($last - $first) / $first) * 100) : ($last > 0 ? 100 : 0);
                $card['delta_dir'] = $last >= $first ? 'up' : 'down';
            }
        }

        return $card;
    }

    /**
     * A 7-day daily series for a time-based metric (for KPI sparklines); null for
     * static metrics (staff/services counts, bed occupancy, …).
     *
     * @return array<int, int>|null
     */
    private function metricSpark(string $key): ?array
    {
        $spec = match ($key) {
            'visits_today' => ['visits', $this->visitsDepartmentColumn(), 'created_at', 'count', null],
            'activity_today' => ['invoice_items', 'department_id', 'created_at', 'count', null],
            'appointments_today' => ['appointments', 'department_id', 'appointment_date', 'count', null],
            'department_revenue_today', 'revenue_today' => ['invoice_items', 'department_id', 'created_at', 'sum', 'total_price'],
            'pending_requests', 'pending_imaging', 'completed_results_today', 'completed_imaging_today' => ['lab_requests', 'target_department_id', 'created_at', 'count', null],
            'dispensed_today' => ['prescriptions', null, 'updated_at', 'count', null],
            'invoices_today' => ['invoices', null, 'created_at', 'count', null],
            'stock_issues' => ['stock_movements', null, 'created_at', 'count', null],
            default => null,
        };

        if ($spec === null || ! Schema::hasTable($spec[0]) || ! Schema::hasColumn($spec[0], $spec[2])) {
            return null;
        }

        [$table, $column, $dateColumn, $agg, $aggColumn] = $spec;
        $series = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            try {
                $q = DB::table($table);
                if ($column) {
                    $q = $this->scopeDepartment($q, $column);
                }
                $q->whereDate($dateColumn, $date);
                $series[] = (int) round($agg === 'sum' ? (float) $q->sum($aggColumn) : (float) $q->count());
            } catch (Throwable) {
                $series[] = 0;
            }
        }

        return $series;
    }

    private function workQueue(string $key): array
    {
        $rows = match ($key) {
            'lab_requests', 'radiology_requests' => $this->tableRows('lab_requests', function ($q) {
                return $this->scopeDepartment($q, 'target_department_id')
                    ->whereIn('status', ['pending', 'requested', 'processing', 'sample_collected'])
                    ->latest('id');
            }, 'request_number', 'status', 'admin.lab.requests.show'),
            'visit_queue', 'department_activity' => $this->tableRows('visits', function ($q) {
                return $this->scopeDepartment($q, $this->visitsDepartmentColumn())->latest('id');
            }, 'visit_number', 'status', 'admin.visits.show'),
            'admissions_queue' => $this->tableRows('admissions', fn ($q) => $q->where('status', 'admitted')->latest('id'), 'admission_number', 'status', 'admin.admissions.show'),
            'stock_queue' => $this->tableRows('stock_requisitions', fn ($q) => $this->scopeDepartment($q, 'department_id')->latest('id'), 'requisition_number', 'status', 'admin.store.stock-requisitions.show'),
            default => [],
        };

        return [
            'title' => __("dashboards.department.queues.$key"),
            'icon' => $this->context->theme['icon'] ?? 'ti-list',
            'rows' => $rows,
            'empty' => __('dashboards.department.no_queue_items'),
        ];
    }

    private function departmentServices(): array
    {
        $canViewPrices = $this->user->can('invoices.view') || $this->user->can('billing.view');
        $rows = [];

        if (Schema::hasTable('service_catalog')) {
            $rows = $this->safeRows(function () use ($canViewPrices) {
                return $this->scopeDepartment(DB::table('service_catalog'), 'department_id')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(8)
                    ->get()
                    ->map(fn ($service) => [
                        'name' => $service->name,
                        'code' => $service->code ?? null,
                        'price' => $canViewPrices ? (float) ($service->price ?? 0) : null,
                        'billable' => (bool) ($service->is_billable ?? false),
                    ])
                    ->all();
            });
        }

        return [
            'title' => __('dashboards.department.department_services'),
            'can_view_prices' => $canViewPrices,
            'rows' => $rows,
            'empty' => __('dashboards.department.no_department_services'),
        ];
    }

    private function stockUsage(): array
    {
        $canViewStock = $this->user->can('store.purchase.view') || $this->user->can('pharmacy.stock.manage');
        $canViewCost = $this->user->can('inventory.cost.view') || $this->user->can('reports.stock_cost.view');

        if (! $canViewStock) {
            return [
                'restricted' => true,
                'title' => __('dashboards.department.department_stock'),
                'message' => __('dashboards.department.restricted_metric'),
                'rows' => [],
            ];
        }

        $rows = [];
        if (Schema::hasTable('stock_balances') && Schema::hasTable('stock_locations')) {
            $rows = $this->safeRows(function () use ($canViewCost) {
                $query = DB::table('stock_balances')
                    ->join('stock_locations', 'stock_locations.id', '=', 'stock_balances.stock_location_id')
                    ->leftJoin('products', 'products.id', '=', 'stock_balances.product_id')
                    ->when($this->context->department_id, fn ($q, $id) => $q->where('stock_locations.department_id', $id))
                    ->select([
                        DB::raw('COALESCE(products.name, CONCAT("Item #", stock_balances.product_id)) as name'),
                        'stock_balances.quantity_on_hand',
                        'stock_balances.total_value',
                    ])
                    ->orderBy('stock_balances.quantity_on_hand')
                    ->limit(8);

                return $query->get()->map(fn ($row) => [
                    'name' => $row->name,
                    'quantity' => (float) $row->quantity_on_hand,
                    'value' => $canViewCost ? (float) ($row->total_value ?? 0) : null,
                ])->all();
            });
        }

        return [
            'restricted' => false,
            'title' => __('dashboards.department.department_stock'),
            'can_view_cost' => $canViewCost,
            'rows' => $rows,
            'empty' => __('dashboards.department.no_stock_usage'),
        ];
    }

    private function quickActions(): array
    {
        $defs = [
            ['label' => __('dashboards.department.view_services'), 'icon' => 'ti-list-details', 'route' => 'admin.services.index', 'permission' => null],
            ['label' => __('dashboards.department.view_requests'), 'icon' => 'ti-clipboard-list', 'route' => 'admin.lab.requests.index', 'permission' => 'lab.requests.view'],
            ['label' => __('dashboards.department.view_results'), 'icon' => 'ti-file-text', 'route' => 'admin.lab.results.index', 'permission' => 'lab.results.view'],
            ['label' => __('dashboards.department.view_stock'), 'icon' => 'ti-packages', 'route' => 'admin.store.stock.balances', 'permission' => 'store.purchase.view'],
            ['label' => __('dashboards.department.view_prices'), 'icon' => 'ti-cash-banknote', 'route' => 'admin.billing.invoices.index', 'permission' => 'invoices.view'],
        ];

        return array_values(array_filter(array_map(function ($def) {
            if ($def['permission'] && ! $this->user->can($def['permission'])) {
                return null;
            }
            if (! Route::has($def['route'])) {
                return null;
            }

            return [
                'label' => $def['label'],
                'icon' => $def['icon'],
                'route' => route($def['route'], $this->departmentFilterParams()),
            ];
        }, $defs)));
    }

    private function trends(): array
    {
        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $points[] = [
                'label' => $date->format('d M'),
                'value' => $this->countTable('invoice_items', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('created_at', $date)),
            ];
        }

        return [
            'title' => __('dashboards.department.seven_day_activity'),
            'points' => $points,
        ];
    }

    private function activities(): array
    {
        return $this->tableRows('invoice_items', fn ($q) => $this->scopeDepartment($q, 'department_id')->latest('id'), 'description', 'created_at', null);
    }

    private function restrictedCards(): array
    {
        $cards = [];
        if (! ($this->user->can('invoices.view') || $this->user->can('billing.view'))) {
            $cards[] = ['title' => __('dashboards.department.department_prices'), 'message' => __('dashboards.department.restricted_metric')];
        }
        if (! ($this->user->can('store.purchase.view') || $this->user->can('pharmacy.stock.manage'))) {
            $cards[] = ['title' => __('dashboards.department.department_usage'), 'message' => __('dashboards.department.restricted_metric')];
        }

        return $cards;
    }

    private function completedToday(): int
    {
        if (Schema::hasTable('lab_requests')) {
            return $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->where('status', 'completed')->whereDate('updated_at', today()));
        }

        return $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->where('status', 'completed')->whereDate('updated_at', today()));
    }

    private function countTable(string $table, callable $scope): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        try {
            return (int) $scope(DB::table($table))->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function restrictedCount(string $table, string $permission, callable $scope): mixed
    {
        if (! $this->user->can($permission)) {
            return ['restricted' => true, 'value' => null];
        }

        return $this->countTable($table, $scope);
    }

    private function restrictedSum(string $table, string $column, string $permission, callable $scope): mixed
    {
        if (! $this->user->can($permission)) {
            return ['restricted' => true, 'value' => null];
        }
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        try {
            return (float) $scope(DB::table($table))->sum($column);
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function stockCount(string $mode): int
    {
        if (! ($this->user->can('store.purchase.view') || $this->user->can('pharmacy.stock.manage'))) {
            return 0;
        }
        if (! Schema::hasTable('stock_balances') || ! Schema::hasTable('stock_locations')) {
            return 0;
        }

        return $this->countTable('stock_balances', function ($query) use ($mode) {
            $query->join('stock_locations', 'stock_locations.id', '=', 'stock_balances.stock_location_id')
                ->when($this->context->department_id, fn ($q, $id) => $q->where('stock_locations.department_id', $id));

            if ($mode === 'low' && Schema::hasTable('products')) {
                $query->join('products', 'products.id', '=', 'stock_balances.product_id')
                    ->whereColumn('stock_balances.quantity_on_hand', '<=', 'products.reorder_level')
                    ->where('products.reorder_level', '>', 0);
            }

            return $query;
        });
    }

    private function tableRows(string $table, callable $scope, string $labelColumn, string $badgeColumn, ?string $routeName): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return $this->safeRows(function () use ($table, $scope, $labelColumn, $badgeColumn, $routeName) {
            return $scope(DB::table($table))->limit(8)->get()->map(function ($row) use ($labelColumn, $badgeColumn, $routeName) {
                $label = $row->{$labelColumn} ?? ('#'.$row->id);
                $badge = $row->{$badgeColumn} ?? null;

                return [
                    'label' => (string) $label,
                    'meta' => isset($row->created_at) ? (string) $row->created_at : '',
                    'badge' => $badge ? (string) $badge : null,
                    'badge_variant' => 'secondary',
                    'url' => $routeName && Route::has($routeName) ? route($routeName, $row->id) : null,
                ];
            })->all();
        });
    }

    private function safeRows(callable $callback): array
    {
        try {
            return $callback();
        } catch (Throwable) {
            return [];
        }
    }

    private function scopeDepartment($query, string $column)
    {
        if ($this->context->department_id && $this->hasColumnForQuery($query, $column)) {
            $query->where($column, $this->context->department_id);
        }

        return $query;
    }

    private function scopeDepartmentIfColumnExists($query, string $column)
    {
        return $this->scopeDepartment($query, $column);
    }

    private function hasColumnForQuery($query, string $column): bool
    {
        $from = $query instanceof Builder ? $query->getModel()->getTable() : ($query->from ?? null);

        return is_string($from) && Schema::hasColumn($from, $column);
    }

    private function visitsDepartmentColumn(): string
    {
        return Schema::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';
    }

    private function departmentFilterParams(): array
    {
        return $this->context->department_id ? ['department_id' => $this->context->department_id] : [];
    }
}
