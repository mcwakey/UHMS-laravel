<?php

namespace App\Services\Dashboards;

use App\Enums\DepartmentType;
use App\Enums\LeaveStatus;
use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Visit;
use App\Services\Dashboards\Concerns\BuildsPressure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Metrics for the Administrative workspace dashboard — a hospital operations
 * overview built from counts only (no clinical or financial detail): the
 * specialist workspaces stay authoritative for their own data.
 */
class AdministrativeDashboardService
{
    use BuildsPressure;

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->leaveInsight(),
            'pressure' => $this->operationsPressure(),
            'kpis' => $this->kpis(),
            'visitTrend' => $this->visitTrend(),
            'departmentsByType' => $this->departmentsByType(),
            'busiestDepartments' => $this->busiestDepartmentsToday(),
            'recentActivity' => ActivityLog::with('causer')
                ->latest()
                ->take(6)
                ->get(),
        ];
    }

    /**
     * Pending leave-approval banner for management follow-up.
     *
     * @return array<string, mixed>|null
     */
    private function leaveInsight(): ?array
    {
        $pending = LeaveRequest::where('status', LeaveStatus::PENDING)->count();
        if ($pending < 1) {
            return null;
        }

        $oldest = LeaveRequest::where('status', LeaveStatus::PENDING)->oldest()->value('created_at');
        $oldestDays = $oldest ? (int) Carbon::parse($oldest)->diffInDays(now()) : 0;

        return [
            'variant' => $oldestDays >= 7 ? 'danger' : 'warning',
            'icon' => 'ti-calendar-pause',
            'title' => __('administrative.dashboard.leave_insight'),
            'badge' => __('administrative.dashboard.pending_leave_count', ['count' => $pending]),
            'cause' => ['icon' => 'ti-clock-exclamation', 'label' => __('administrative.dashboard.oldest_waiting', ['days' => $oldestDays])],
            'action' => __('administrative.dashboard.action_review_leave'),
            'link' => ['url' => route('administrative.hr.leave.index'), 'label' => __('administrative.dashboard.open_leave')],
        ];
    }

    /** @return array<string, mixed> */
    private function operationsPressure(): array
    {
        $visitsToday = Visit::whereDate('created_at', today())->count();
        $admissionsToday = Admission::whereDate('created_at', today())->count();
        $activeDepartments = Department::where('status', 'active')->count();
        $pendingLeave = LeaveRequest::where('status', LeaveStatus::PENDING)->count();

        return $this->pressure(
            __('administrative.dashboard.operations_load'),
            'ti-building-hospital',
            $visitsToday,
            [30, 80, 150],
            [
                __('administrative.dashboard.visits_today_metric', ['count' => $visitsToday]),
                __('administrative.dashboard.admissions_today_metric', ['count' => $admissionsToday]),
                __('administrative.dashboard.active_departments_metric', ['count' => $activeDepartments]),
                __('administrative.dashboard.pending_leave_metric', ['count' => $pendingLeave]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        return [
            'departments' => ['value' => Department::where('status', 'active')->count()],
            'staff' => ['value' => User::where('status', UserStatus::ACTIVE)->count()],
            'visits_today' => ['value' => Visit::whereDate('created_at', today())->count()],
            'admissions_today' => ['value' => Admission::whereDate('created_at', today())->count()],
        ];
    }

    /** Visits registered per day, last 7 days. @return array<string, mixed> */
    private function visitTrend(): array
    {
        $rows = Visit::where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['created_at']);

        $labels = [];
        $visits = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('D');
            $visits[] = $rows->filter(fn ($row) => Carbon::parse($row->created_at)->isSameDay($day))->count();
        }

        return ['labels' => $labels, 'visits' => $visits];
    }

    /** Active departments grouped by type (top 4 + others). @return array<string, int> */
    private function departmentsByType(): array
    {
        $rows = Department::where('status', 'active')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $out = [];
        foreach ($rows->take(4) as $row) {
            $type = DepartmentType::tryFrom((string) ($row->type?->value ?? $row->type));
            $out[$type?->label() ?? ucfirst((string) $row->type)] = (int) $row->total;
        }
        $others = (int) $rows->skip(4)->sum('total');
        if ($others > 0) {
            $out[__('administrative.dashboard.others')] = $others;
        }

        return $out;
    }

    /** Departments with the most visits today. @return array<int, object> */
    private function busiestDepartmentsToday(): array
    {
        return Visit::query()
            ->join('departments', 'departments.id', '=', 'visits.current_department_id')
            ->whereDate('visits.created_at', today())
            ->select('departments.name', DB::raw('COUNT(*) as visit_count'))
            ->groupBy('departments.name')
            ->orderByDesc('visit_count')
            ->take(5)
            ->get()
            ->all();
    }
}
