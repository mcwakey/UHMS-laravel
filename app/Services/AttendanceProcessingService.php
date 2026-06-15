<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\LogModule;
use App\Models\AttendanceException;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrPolicySetting;
use App\Models\HrShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceProcessingService
{
    public function __construct(private LeaveImpactService $leaveImpact, private ActivityLogService $activityLog) {}

    public function process(CarbonInterface $date): Collection
    {
        $records = collect();
        Employee::where('status', EmployeeStatus::ACTIVE->value)->with('department')->each(function (Employee $employee) use ($date, $records) {
            $shift = $this->shiftFor($employee, $date);
            $attendance = EmployeeAttendance::where('employee_id', $employee->id)->whereDate('date', $date)->first()
                ?? new EmployeeAttendance(['employee_id' => $employee->id, 'date' => $date->toDateString()]);
            $leave = $this->leaveImpact->approvedLeaveFor($employee, $date);

            if ($leave) {
                $attendance->fill(['shift_id' => $shift?->id, 'status' => $this->leaveImpact->attendanceStatus($leave), 'source' => $attendance->source ?: 'shift_roster', 'late_minutes' => 0, 'early_exit_minutes' => 0, 'overtime_minutes' => 0]);
            } elseif (! $attendance->clock_in && ! $attendance->clock_out) {
                $offDay = (bool) HrPolicySetting::value('attendance.weekends_are_off_days', true) && $date->isWeekend();
                $attendance->fill(['shift_id' => $shift?->id, 'status' => $offDay ? 'weekend' : 'absent', 'source' => $attendance->source ?: 'shift_roster']);
            } elseif ($shift) {
                $this->calculateTimes($attendance, $shift, $date);
            }

            $attendance->review_status = $attendance->review_status ?: 'pending';
            $attendance->save();
            $this->syncExceptions($attendance);
            $records->push($attendance);
        });

        return $records;
    }

    public function approve(EmployeeAttendance $attendance, int $userId): EmployeeAttendance
    {
        $attendance->update(['review_status' => 'approved', 'reviewed_by' => $userId, 'reviewed_at' => now()]);
        $this->activityLog->log(LogModule::SYSTEM, 'ATTENDANCE_APPROVED', ['source_type' => 'employee_attendance', 'source_id' => $attendance->id, 'metadata' => ['employee_id' => $attendance->employee_id, 'date' => $attendance->date->toDateString()]], $attendance);
        return $attendance->fresh();
    }

    public function calculateTimes(EmployeeAttendance $attendance, HrShift $shift, CarbonInterface $date): void
    {
        $start = Carbon::parse($date->toDateString().' '.$shift->start_time);
        $end = Carbon::parse($date->toDateString().' '.$shift->end_time);
        if ($shift->is_night_shift || $end->lte($start)) {
            $end->addDay();
        }
        $in = Carbon::parse($date->toDateString().' '.$attendance->clock_in);
        $out = Carbon::parse($date->toDateString().' '.$attendance->clock_out);
        if ($out->lt($in)) {
            $out->addDay();
        }
        $grace = $shift->grace_minutes ?: (int) HrPolicySetting::value('attendance.grace_minutes', 0);
        $attendance->shift_id = $shift->id;
        $attendance->late_minutes = max(0, $start->diffInMinutes($in, false) - $grace);
        $attendance->early_exit_minutes = max(0, $out->diffInMinutes($end, false));
        $attendance->overtime_minutes = max(0, $end->diffInMinutes($out, false) - (int) HrPolicySetting::value('attendance.overtime_after_minutes', 0));
        $attendance->hours_worked = round(max(0, $in->diffInMinutes($out, false) - $shift->break_minutes) / 60, 2);
        $attendance->status = $attendance->late_minutes > 0 ? 'late' : 'present';
    }

    private function shiftFor(Employee $employee, CarbonInterface $date): ?HrShift
    {
        $assignment = EmployeeShiftAssignment::with('shift')
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->where(fn ($q) => $q->where('employee_id', $employee->id)->orWhere(fn ($d) => $d->whereNull('employee_id')->where('department_id', $employee->department_id)))
            ->orderByRaw('CASE WHEN employee_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('priority')
            ->first();
        return $assignment?->shift ?? HrShift::where('is_active', true)->orderBy('id')->first();
    }

    private function syncExceptions(EmployeeAttendance $attendance): void
    {
        $exceptions = [];
        if ($attendance->status === 'absent') $exceptions['absence'] = 'Employee has no attendance or approved leave.';
        if ($attendance->late_minutes > 0) $exceptions['late_arrival'] = "Late by {$attendance->late_minutes} minute(s).";
        if ($attendance->early_exit_minutes > 0) $exceptions['early_exit'] = "Left {$attendance->early_exit_minutes} minute(s) early.";
        foreach ($exceptions as $type => $message) {
            AttendanceException::updateOrCreate(['employee_attendance_id' => $attendance->id, 'exception_type' => $type], ['message' => $message, 'status' => 'open']);
        }
    }
}
