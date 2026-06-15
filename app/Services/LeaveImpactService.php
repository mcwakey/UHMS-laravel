<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\CarbonInterface;

class LeaveImpactService
{
    public function approvedLeaveFor(Employee $employee, CarbonInterface $date): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveStatus::APPROVED->value)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public function attendanceStatus(LeaveRequest $leave): string
    {
        return ($leave->leave_type->value ?? $leave->leave_type) === 'sick' ? 'sick_leave' : 'on_leave';
    }
}
