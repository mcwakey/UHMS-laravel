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
    /** Emergency-case statuses that count as "still in the ER". */
    private const EMERGENCY_ACTIVE = ['ARRIVED', 'TRIAGED', 'IN_TREATMENT', 'OBSERVATION'];

    private DepartmentDashboardContext $context;
    private User $user;

    public function __construct(
        private DepartmentDashboardLayoutRegistry $layouts,
        private DepartmentDashboardChartService $charts,
        private DepartmentDashboardDrilldownUrlBuilder $drilldowns,
        private DepartmentDashboardChartRegistry $chartRegistry,
        private DepartmentTrendRepository $trendRepository,
        private DepartmentStatusPresenter $statusPresenter,
        private DepartmentQuickActionRegistry $quickActionRegistry,
        private DepartmentIdentityWidgetBuilder $identityWidgets,
        private DepartmentDashboardCapabilityService $capabilities,
        private DepartmentMetricDefinitionService $metricDefinitions,
        private DepartmentAlertService $alerts,
    ) {}

    /** A short, localized relative/clock time for queue & activity rows. */
    private function friendlyTime($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($value);
        } catch (Throwable) {
            return (string) $value;
        }

        $now = now();
        if ($dt->greaterThan($now)) {
            return $dt->isoFormat('D MMM, HH:mm');
        }
        if ($dt->diffInMinutes($now) < 60) {
            return $dt->diffForHumans();
        }
        if ($dt->isToday()) {
            return __('dashboards.department.time.today', ['time' => $dt->format('H:i')]);
        }
        if ($dt->isYesterday()) {
            return __('dashboards.department.time.yesterday', ['time' => $dt->format('H:i')]);
        }
        if ($dt->diffInDays($now) < 7) {
            return $dt->isoFormat('ddd HH:mm');
        }

        return $dt->isoFormat('D MMM');
    }

    public function build(DepartmentDashboardContext $context): array
    {
        $this->context = $context;
        $this->user = $context->user;

        $layout = $this->layouts->for($context->department_type);
        $services = $this->departmentServices();
        $stockUsage = $this->stockUsage();

        $charts = $this->charts->build($context);
        // Stock-bearing dashboards (pharmacy/stores/blood bank) get the In-stock vs
        // Low-stock donut; other types skip it (saves the stockCount queries).
        if ($this->chartRegistry->needsStockStatus($context->department_type)) {
            $charts['stock_status_breakdown'] = $this->stockStatusBreakdown();
        }

        $primaryCards = $this->cards($layout['primary_cards'] ?? []);
        $secondaryCards = $this->cards($layout['secondary_cards'] ?? [], true);

        // Operational intelligence (widget + alerts + status) all reuse the card
        // values already computed above — no extra queries.
        $cardValue = function (string $key) use ($primaryCards, $secondaryCards) {
            foreach (array_merge($primaryCards, $secondaryCards) as $card) {
                if (($card['key'] ?? null) === $key) {
                    return $card['value'];
                }
            }

            return null;
        };

        $alerts = $this->alerts->alertsFor($context->department_type, $cardValue);

        return [
            'layout_profile' => $layout,
            'primary_cards' => $primaryCards,
            'secondary_cards' => $secondaryCards,
            'identity_widget' => $this->identityWidgets->build($context->department_type, $cardValue),
            'alerts' => $alerts,
            'operational_status' => $this->alerts->statusFor($context->department_type, $cardValue, $alerts),
            'work_queue' => $this->workQueue((string) ($layout['main_queue'] ?? 'department_activity')),
            'quick_actions' => $this->quickActions(),
            'services' => $services,
            'stock_usage' => $stockUsage,
            // Reuse the activity-trend chart instead of recomputing a 7-day loop.
            'trends' => $this->trendsFromChart($charts['activity_trend'] ?? null),
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

        if (! $this->capabilities->can($this->user, 'stock_access')) {
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
        $format = 'number';
        $variant = $compact ? 'secondary' : $this->context->theme['accent_class'];
        $icon = $compact ? 'ti-circle-dot' : ($this->context->theme['icon'] ?? 'ti-layout-dashboard');

        // Unified visibility: the card value AND its drilldown share one capability.
        $visible = $this->capabilities->can($this->user, $this->metricDefinitions->capabilityFor($key));
        $route = $visible ? $this->drilldowns->build($this->context, $key) : null;

        $value = ! $visible
            ? ['restricted' => true, 'value' => null]
            : match ($key) {
            'services_count' => $this->countTable('service_catalog', fn ($q) => $this->scopeDepartment($q, 'department_id')->where('is_active', true)),
            'staff_count' => $this->countTable('users', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereNull('deleted_at')),
            'visits_today' => $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->whereDate('created_at', today())),
            'activity_today' => $this->countTable('invoice_items', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('created_at', today())),
            'appointments_today' => $this->countTable('appointments', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('appointment_date', today())),
            'waiting_queue' => $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->whereIn('status', ['queued', 'waiting', 'checked_in'])),
            'completed_today', 'completed_results_today', 'completed_imaging_today' => $this->completedToday(),
            'department_revenue_today', 'revenue_today' => $this->sumTable('invoice_items', 'total_price', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereDate('created_at', today())),
            'pending_requests', 'pending_imaging' => $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->whereIn('status', ['pending', 'requested'])),
            'samples_awaiting_acceptance' => $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->whereIn('status', ['sample_collected', 'received', 'accepted'])),
            'scheduled_imaging', 'scheduled' => $this->countTable('appointments', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['scheduled', 'confirmed'])),
            'pending_prescriptions' => $this->countTable('prescriptions', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['pending', 'billed', 'partially_billed'])),
            'dispensed_today' => $this->countTable('prescriptions', fn ($q) => $this->scopeDepartment($q, 'department_id')->where('status', 'dispensed')->whereDate('updated_at', today())),
            'low_stock' => $this->stockCount('low'),
            'stock_items' => $this->stockCount('items'),
            'stock_requests' => $this->countTable('stock_requisitions', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['submitted', 'pending'])),
            'stock_issues' => $this->countTable('stock_movements', fn ($q) => $this->scopeStockMovements($q)->where('stock_movements.direction', 'out')->whereDate('stock_movements.movement_date', today())),
            // Emergency cases have no department_id → scope through the linked visit; "active"
            // = still in the ER; "critical" = active AND emergency-triaged (distinct numbers).
            'active_cases' => $this->countTable('emergency_cases', fn ($q) => $this->scopeEmergencyCases($q)->whereIn('emergency_cases.emergency_status', self::EMERGENCY_ACTIVE)),
            'critical_cases' => $this->countTable('emergency_cases', fn ($q) => $this->scopeEmergencyCases($q)->whereIn('emergency_cases.emergency_status', self::EMERGENCY_ACTIVE)->where(fn ($w) => $w->where('emergency_cases.final_triage_category', 'emergency')->orWhere('emergency_cases.triage_category', 'emergency'))),
            // Admissions/beds scope through bed → ward → department.
            'active_admissions' => $this->countTable('admissions', fn ($q) => $this->scopeAdmissions($q)->where('admissions.status', 'admitted')),
            'beds_occupied' => $this->countTable('beds', fn ($q) => $this->scopeBeds($q)->where('beds.status', 'occupied')),
            'discharges_pending' => $this->countTable('admissions', fn ($q) => $this->scopeAdmissions($q)->where('admissions.status', 'admitted')->whereNotNull('admissions.expected_discharge_date')->whereDate('admissions.expected_discharge_date', '<=', today())),
            'vitals_due' => $this->vitalsDue(),
            // Invoices/payments have no department column → hospital-wide (correct for finance).
            'invoices_today' => $this->countTable('invoices', fn ($q) => $q->whereDate('created_at', today())),
            'payments_today' => $this->sumTable('payments', 'amount', fn ($q) => $q->whereDate('created_at', today())),
            'receivables' => $this->sumTable('invoices', 'balance', fn ($q) => $q->whereIn('status', ['pending', 'partially_paid'])),
            'in_theatre' => $this->countTable('procedure_requests', fn ($q) => $this->scopeDepartment($q, 'department_id')->whereIn('status', ['pre_op', 'anaesthesia', 'in_surgery', 'surgery_done', 'post_op'])),
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
        // [table, departmentColumn|null, dateColumn, agg, sumColumn|null, [statusColumn, values]|null]
        $spec = match ($key) {
            'visits_today' => ['visits', $this->visitsDepartmentColumn(), 'created_at', 'count', null, null],
            'activity_today' => ['invoice_items', 'department_id', 'created_at', 'count', null, null],
            'appointments_today' => ['appointments', 'department_id', 'appointment_date', 'count', null, null],
            'department_revenue_today', 'revenue_today' => ['invoice_items', 'department_id', 'created_at', 'sum', 'total_price', null],
            'pending_requests', 'pending_imaging' => ['lab_requests', 'target_department_id', 'created_at', 'count', null, ['status', ['pending', 'requested']]],
            'completed_results_today', 'completed_imaging_today' => ['lab_requests', 'target_department_id', 'updated_at', 'count', null, ['status', ['completed']]],
            'dispensed_today' => ['prescriptions', 'department_id', 'updated_at', 'count', null, ['status', ['dispensed']]],
            'invoices_today' => ['invoices', null, 'created_at', 'count', null, null],
            'stock_issues' => ['stock_movements', null, 'movement_date', 'count', null, ['direction', ['out']]],
            default => null,
        };

        if ($spec === null || ! DepartmentSchemaCache::hasTable($spec[0]) || ! DepartmentSchemaCache::hasColumn($spec[0], $spec[2])) {
            return null;
        }

        [$table, $column, $dateColumn, $agg, $aggColumn, $filter] = $spec;

        // One grouped query (was a 7-iteration loop).
        return $this->trendRepository->dailySeries(
            $table,
            $dateColumn,
            $column,
            $column ? $this->context->department_id : null,
            7,
            $agg,
            $aggColumn,
            $filter,
        );
    }

    private function workQueue(string $key): array
    {
        $rows = match ($key) {
            'lab_requests', 'radiology_requests' => $this->tableRows('lab_requests', function ($q) {
                return $this->scopeDepartment($q, 'target_department_id')
                    ->whereIn('status', ['pending', 'requested', 'processing', 'sample_collected'])
                    ->latest('id');
            }, 'request_number', 'status', 'admin.lab.requests.show', 'lab'),
            'visit_queue', 'department_activity', 'emergency_queue', 'procedure_queue', 'financial_queue' => $this->tableRows('visits', function ($q) {
                return $this->scopeDepartment($q, $this->visitsDepartmentColumn())->latest('id');
            }, 'visit_number', 'status', 'admin.visits.show', 'visit'),
            'pharmacy_queue' => $this->tableRows('prescriptions', function ($q) {
                return $this->scopeDepartment($q, 'department_id')
                    ->whereIn('status', ['pending', 'billed', 'partially_billed'])
                    ->latest('id');
            }, 'prescription_number', 'status', null, 'default'),
            'admissions_queue' => $this->tableRows('admissions', fn ($q) => $q->where('status', 'admitted')->latest('id'), 'admission_number', 'status', 'admin.admissions.show', 'visit'),
            'stock_queue' => $this->tableRows('stock_requisitions', fn ($q) => $this->scopeDepartment($q, 'department_id')->latest('id'), 'requisition_number', 'status', 'admin.store.stock-requisitions.show', 'requisition'),
            default => $this->tableRows('visits', function ($q) {
                return $this->scopeDepartment($q, $this->visitsDepartmentColumn())->latest('id');
            }, 'visit_number', 'status', 'admin.visits.show', 'visit'),
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
        $canViewPrices = $this->capabilities->can($this->user, 'financial_access');
        $rows = [];

        if (DepartmentSchemaCache::hasTable('service_catalog')) {
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
        $canViewStock = $this->capabilities->can($this->user, 'stock_access');
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
        if (DepartmentSchemaCache::hasTable('stock_balances') && DepartmentSchemaCache::hasTable('stock_locations')) {
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
        $defs = $this->quickActionRegistry->for($this->context->department_type);

        return array_values(array_filter(array_map(function (array $def) {
            [$labelKey, $icon, $route, $capability] = $def;

            if (! $this->capabilities->can($this->user, $capability)) {
                return null;
            }
            if (! Route::has($route)) {
                return null;
            }

            return [
                'label' => __('dashboards.department.actions.'.$labelKey),
                'icon' => $icon,
                'route' => route($route, $this->departmentFilterParams()),
            ];
        }, $defs)));
    }

    /**
     * Trend points for the chart-card's no-JS fallback, derived from the already-built
     * activity-trend chart so no extra queries run (was a duplicate 7-day loop).
     *
     * @param  array<string, mixed>|null  $chart
     */
    private function trendsFromChart(?array $chart): array
    {
        $labels = $chart['labels'] ?? [];
        $values = $chart['datasets'][0]['data'] ?? [];

        $points = [];
        foreach ($labels as $i => $label) {
            $points[] = ['label' => $label, 'value' => $values[$i] ?? 0];
        }

        return [
            'title' => __('dashboards.department.seven_day_activity'),
            'points' => $points,
        ];
    }

    private function activities(): array
    {
        return $this->tableRows('invoice_items', fn ($q) => $this->scopeDepartment($q, 'department_id')->latest('id'), 'description', 'payment_status', null, 'default');
    }

    private function restrictedCards(): array
    {
        $cards = [];
        if (! $this->capabilities->can($this->user, 'financial_access')) {
            $cards[] = ['title' => __('dashboards.department.department_prices'), 'message' => __('dashboards.department.restricted_metric')];
        }
        if (! $this->capabilities->can($this->user, 'stock_access')) {
            $cards[] = ['title' => __('dashboards.department.department_usage'), 'message' => __('dashboards.department.restricted_metric')];
        }

        return $cards;
    }

    private function completedToday(): int
    {
        if (DepartmentSchemaCache::hasTable('lab_requests')) {
            return $this->countTable('lab_requests', fn ($q) => $this->scopeDepartment($q, 'target_department_id')->where('status', 'completed')->whereDate('updated_at', today()));
        }

        return $this->countTable('visits', fn ($q) => $this->scopeDepartment($q, $this->visitsDepartmentColumn())->where('status', 'completed')->whereDate('updated_at', today()));
    }

    private function countTable(string $table, callable $scope): int
    {
        if (! DepartmentSchemaCache::hasTable($table)) {
            return 0;
        }

        try {
            return (int) $scope(DB::table($table))->count();
        } catch (Throwable) {
            return 0;
        }
    }

    // Plain aggregate; visibility is decided by the capability gate in metricCard().
    private function sumTable(string $table, string $column, callable $scope): float
    {
        if (! DepartmentSchemaCache::hasTable($table) || ! DepartmentSchemaCache::hasColumn($table, $column)) {
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
        if (! DepartmentSchemaCache::hasTable('stock_balances') || ! DepartmentSchemaCache::hasTable('stock_locations')) {
            return 0;
        }

        return $this->countTable('stock_balances', function ($query) use ($mode) {
            $query->join('stock_locations', 'stock_locations.id', '=', 'stock_balances.stock_location_id')
                ->when($this->context->department_id, fn ($q, $id) => $q->where('stock_locations.department_id', $id));

            if ($mode === 'low' && DepartmentSchemaCache::hasTable('products')) {
                $query->join('products', 'products.id', '=', 'stock_balances.product_id')
                    ->whereColumn('stock_balances.quantity_on_hand', '<=', 'products.reorder_level')
                    ->where('products.reorder_level', '>', 0);
            }

            return $query;
        });
    }

    private function tableRows(string $table, callable $scope, string $labelColumn, string $badgeColumn, ?string $routeName, string $domain = 'default'): array
    {
        if (! DepartmentSchemaCache::hasTable($table)) {
            return [];
        }

        return $this->safeRows(function () use ($table, $scope, $labelColumn, $badgeColumn, $routeName, $domain) {
            return $scope(DB::table($table))->limit(8)->get()->map(function ($row) use ($labelColumn, $badgeColumn, $routeName, $domain) {
                $label = $row->{$labelColumn} ?? ('#'.$row->id);
                $badge = $this->statusPresenter->present($row->{$badgeColumn} ?? null, $domain);

                return [
                    'label' => (string) $label,
                    'meta' => $this->friendlyTime($row->created_at ?? null),
                    'badge' => $badge['label'],
                    'badge_variant' => $badge['variant'],
                    'badge_icon' => $badge['icon'],
                    'priority' => $badge['priority'],
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

    /** emergency_cases → visits.current_department_id (no direct department column). */
    private function scopeEmergencyCases($query)
    {
        return $query->when(
            $this->context->department_id && DepartmentSchemaCache::hasTable('visits'),
            fn ($q, $id) => $q->join('visits', 'visits.id', '=', 'emergency_cases.visit_id')
                ->where('visits.'.$this->visitsDepartmentColumn(), $this->context->department_id)
        );
    }

    /** beds → wards.department_id. */
    private function scopeBeds($query)
    {
        return $query->when(
            $this->context->department_id && DepartmentSchemaCache::hasTable('wards'),
            fn ($q) => $q->join('wards', 'wards.id', '=', 'beds.ward_id')
                ->where('wards.department_id', $this->context->department_id)
        );
    }

    /** admissions → beds → wards.department_id. */
    private function scopeAdmissions($query)
    {
        return $query->when(
            $this->context->department_id && DepartmentSchemaCache::hasTable('beds') && DepartmentSchemaCache::hasTable('wards'),
            fn ($q) => $q->join('beds', 'beds.id', '=', 'admissions.bed_id')
                ->join('wards', 'wards.id', '=', 'beds.ward_id')
                ->where('wards.department_id', $this->context->department_id)
        );
    }

    /** stock_movements → stock_locations.department_id. */
    private function scopeStockMovements($query)
    {
        return $query->when(
            $this->context->department_id && DepartmentSchemaCache::hasTable('stock_locations'),
            fn ($q) => $q->join('stock_locations', 'stock_locations.id', '=', 'stock_movements.stock_location_id')
                ->where('stock_locations.department_id', $this->context->department_id)
        );
    }

    /** Active admissions in this ward with no vitals recorded in the last 8 hours. */
    private function vitalsDue(): int
    {
        if (! DepartmentSchemaCache::hasTable('admissions') || ! DepartmentSchemaCache::hasTable('vitals')) {
            return 0;
        }

        return $this->countTable('admissions', function ($q) {
            return $this->scopeAdmissions($q)
                ->where('admissions.status', 'admitted')
                ->whereNotExists(function ($sub) {
                    $sub->from('vitals')
                        ->whereColumn('vitals.admission_id', 'admissions.id')
                        ->where('vitals.recorded_at', '>=', now()->subHours(8));
                });
        });
    }

    private function hasColumnForQuery($query, string $column): bool
    {
        $from = $query instanceof Builder ? $query->getModel()->getTable() : ($query->from ?? null);

        return is_string($from) && DepartmentSchemaCache::hasColumn($from, $column);
    }

    private function visitsDepartmentColumn(): string
    {
        return DepartmentSchemaCache::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';
    }

    private function departmentFilterParams(): array
    {
        return $this->context->department_id ? ['department_id' => $this->context->department_id] : [];
    }
}
