<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DepartmentComparisonService
{
    public function __construct(
        private DepartmentContextSwitcherService $switcher,
    ) {}

    public function build(User $user, array $filters): array
    {
        $departments = $this->filteredDepartments($user, $filters);
        $canViewRevenue = $user->can('reports.financial_values.view') || $user->can('invoices.view');
        $canViewStock = $user->can('reports.stock.view') || $user->can('store.purchase.view') || $user->can('pharmacy.stock.manage');

        $rows = $departments->map(fn (Department $department) => $this->row($department, $filters, $canViewRevenue, $canViewStock))->values()->all();
        $sum = fn (string $key) => array_sum(array_column($rows, $key));

        return [
            'rows' => $rows,
            'totals' => [
                'departments' => count($rows),
                'activity' => $sum('activity'),
                'services' => $sum('services'),
                'pending_work' => $sum('pending_work'),
                'revenue' => $canViewRevenue ? round($sum('revenue'), 2) : null,
            ],
            'type_rollups' => $this->typeRollups($rows),
            'can_view_revenue' => $canViewRevenue,
            'can_view_stock' => $canViewStock,
        ];
    }

    /**
     * @return Collection<int, Department>
     */
    public function filteredDepartments(User $user, array $filters): Collection
    {
        $allowedIds = $this->switcher->availableDepartments($user)->pluck('id')->all();
        $query = Department::query()
            ->whereIn('id', $allowedIds)
            ->when(empty($filters['include_inactive']), fn ($q) => $q->active())
            ->when($filters['department_type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['department_ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', array_intersect($ids, $allowedIds)))
            ->orderBy('name');

        return $query->get();
    }

    private function row(Department $department, array $filters, bool $canViewRevenue, bool $canViewStock): array
    {
        $type = $department->type instanceof DepartmentType ? $department->type : null;

        return [
            'department_id' => $department->id,
            'department' => $department->name,
            'type' => $type?->value ?? (string) $department->type,
            'type_label' => $type?->translatedLabel() ?? __('dashboards.department.generic_type'),
            'assigned_users' => $this->assignedUsersCount($department),
            'services' => $this->count('service_catalog', 'department_id', $department->id, $filters),
            'activity' => $this->count('invoice_items', 'department_id', $department->id, $filters),
            'pending_work' => $this->pendingWork($department, $filters),
            'completed_work' => $this->completedWork($department, $filters),
            'revenue' => $canViewRevenue ? $this->sum('invoice_items', 'department_id', $department->id, 'total_price', $filters) : 0.0,
            'stock_alerts' => $canViewStock ? $this->stockAlerts($department) : 0,
        ];
    }

    private function typeRollups(array $rows): array
    {
        return collect($rows)
            ->groupBy('type')
            ->map(fn ($items) => [
                'type' => $items->first()['type'],
                'type_label' => $items->first()['type_label'],
                'departments' => $items->count(),
                'activity' => $items->sum('activity'),
                'pending_work' => $items->sum('pending_work'),
                'completed_work' => $items->sum('completed_work'),
                'services' => $items->sum('services'),
                'revenue' => round($items->sum('revenue'), 2),
            ])
            ->values()
            ->all();
    }

    private function assignedUsersCount(Department $department): int
    {
        $direct = Schema::hasColumn('users', 'department_id')
            ? DB::table('users')->where('department_id', $department->id)->whereNull('deleted_at')->count()
            : 0;

        $assigned = Schema::hasTable('department_user')
            ? DB::table('department_user')->where('department_id', $department->id)->count()
            : 0;

        return max((int) $direct, (int) $assigned);
    }

    private function pendingWork(Department $department, array $filters): int
    {
        $type = $department->type;
        if ($type instanceof DepartmentType && $type->isInvestigation()) {
            return $this->count('lab_requests', 'target_department_id', $department->id, $filters, ['pending', 'requested', 'processing'], 'status');
        }

        return $this->count('visits', $this->visitsDepartmentColumn(), $department->id, $filters, ['queued', 'waiting', 'checked_in', 'active'], 'status');
    }

    private function completedWork(Department $department, array $filters): int
    {
        $type = $department->type;
        if ($type instanceof DepartmentType && $type->isInvestigation()) {
            return $this->count('lab_requests', 'target_department_id', $department->id, $filters, ['completed', 'resulted'], 'status');
        }

        return $this->count('visits', $this->visitsDepartmentColumn(), $department->id, $filters, ['completed'], 'status');
    }

    private function stockAlerts(Department $department): int
    {
        if (! Schema::hasTable('stock_balances') || ! Schema::hasTable('stock_locations') || ! Schema::hasTable('products')) {
            return 0;
        }

        try {
            return (int) DB::table('stock_balances')
                ->join('stock_locations', 'stock_locations.id', '=', 'stock_balances.stock_location_id')
                ->join('products', 'products.id', '=', 'stock_balances.product_id')
                ->where('stock_locations.department_id', $department->id)
                ->whereColumn('stock_balances.quantity_on_hand', '<=', 'products.reorder_level')
                ->where('products.reorder_level', '>', 0)
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function count(string $table, string $departmentColumn, int $departmentId, array $filters, array $statuses = [], ?string $statusColumn = null): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $departmentColumn)) {
            return 0;
        }

        try {
            $query = DB::table($table)->where($departmentColumn, $departmentId);
            $this->applyDateFilters($query, $table, $filters);
            if ($statuses && $statusColumn && Schema::hasColumn($table, $statusColumn)) {
                $query->whereIn($statusColumn, $statuses);
            }

            return (int) $query->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function sum(string $table, string $departmentColumn, int $departmentId, string $sumColumn, array $filters): float
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $departmentColumn) || ! Schema::hasColumn($table, $sumColumn)) {
            return 0.0;
        }

        try {
            $query = DB::table($table)->where($departmentColumn, $departmentId);
            $this->applyDateFilters($query, $table, $filters);

            return round((float) $query->sum($sumColumn), 2);
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function applyDateFilters($query, string $table, array $filters): void
    {
        $dateColumn = Schema::hasColumn($table, 'created_at') ? 'created_at' : null;
        if (! $dateColumn) {
            return;
        }

        $query
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate($dateColumn, '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate($dateColumn, '<=', $date));
    }

    private function visitsDepartmentColumn(): string
    {
        return Schema::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';
    }
}
