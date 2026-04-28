<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class HRService
{
    public function listEmployees(array $filters = []): LengthAwarePaginator
    {
        return Employee::with('department')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->byDepartment($d))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);
    }

    public function createEmployee(array $data): Employee
    {
        $data['employee_number'] = $this->generateEmployeeNumber();
        return Employee::create($data);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function generateEmployeeNumber(): string
    {
        $last = Employee::withTrashed()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderByDesc('id')
            ->value('employee_number');

        $next = $last ? (int) substr($last, 4) + 1 : 1;
        return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // Attendance
    public function recordAttendance(array $data): EmployeeAttendance
    {
        $attendance = EmployeeAttendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );

        if ($attendance->clock_in && $attendance->clock_out) {
            $in = Carbon::parse($attendance->clock_in);
            $out = Carbon::parse($attendance->clock_out);
            $attendance->update(['hours_worked' => round($out->diffInMinutes($in) / 60, 2)]);
        }

        return $attendance;
    }

    public function getAttendance(array $filters = []): LengthAwarePaginator
    {
        return EmployeeAttendance::with('employee')
            ->when($filters['employee_id'] ?? null, fn ($q, $e) => $q->byEmployee($e))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->latest('date')
            ->paginate(20);
    }

    public function getAttendanceSummary(int $employeeId, string $month): array
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = EmployeeAttendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get();

        return [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
            'total_hours' => $records->sum('hours_worked'),
            'working_days' => $start->diffInWeekdays($end) + 1,
        ];
    }

    /**
     * Bulk attendance summary — single query instead of N queries.
     */
    public function getBulkAttendanceSummary(array $employeeIds, string $month): array
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = $start->diffInWeekdays($end) + 1;

        $records = EmployeeAttendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');

        $summaries = [];
        foreach ($employeeIds as $id) {
            $group = $records->get($id, collect());
            $summaries[$id] = [
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'late' => $group->where('status', 'late')->count(),
                'half_day' => $group->where('status', 'half_day')->count(),
                'total_hours' => $group->sum('hours_worked'),
                'working_days' => $workingDays,
            ];
        }

        return $summaries;
    }

    // Leave
    public function listLeaveRequests(array $filters = []): LengthAwarePaginator
    {
        return LeaveRequest::with(['employee', 'approvedByUser'])
            ->when($filters['employee_id'] ?? null, fn ($q, $e) => $q->byEmployee($e))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->when($filters['leave_type'] ?? null, fn ($q, $t) => $q->where('leave_type', $t))
            ->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->latest()
            ->paginate(15);
    }

    public function createLeaveRequest(array $data): LeaveRequest
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $data['days'] = $start->diffInWeekdays($end) + 1;
        $data['status'] = LeaveStatus::PENDING->value;

        return LeaveRequest::create($data);
    }

    public function approveLeave(LeaveRequest $leave): void
    {
        if ($leave->status !== LeaveStatus::PENDING) {
            throw new \InvalidArgumentException('Only pending leave requests can be approved.');
        }

        $leave->update([
            'status' => LeaveStatus::APPROVED->value,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function rejectLeave(LeaveRequest $leave, string $reason): void
    {
        if ($leave->status !== LeaveStatus::PENDING) {
            throw new \InvalidArgumentException('Only pending leave requests can be rejected.');
        }

        $leave->update([
            'status' => LeaveStatus::REJECTED->value,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function getLeaveBalance(int $employeeId, int $year = null): array
    {
        $year = $year ?? now()->year;
        $used = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', LeaveStatus::APPROVED)
            ->whereYear('start_date', $year)
            ->sum('days');

        $annualAllocation = 21; // Ghana Labour Act standard

        return [
            'annual_allocation' => $annualAllocation,
            'used' => $used,
            'remaining' => max(0, $annualAllocation - $used),
        ];
    }
}
