<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Employee;
use App\Services\HRService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private HRService $hrService) {}

    public function index(Request $request)
    {
        $attendance = $this->hrService->getAttendance($request->only('employee_id', 'status', 'date_from', 'date_to'));
        $employees = Employee::active()->orderBy('first_name')->get();

        return view('hr.attendance.index', compact('attendance', 'employees'));
    }

    public function store(StoreAttendanceRequest $request)
    {
        $this->hrService->recordAttendance($request->validated());
        return redirect()->route('admin.hr.attendance.index')->with('success', 'Attendance recorded successfully.');
    }

    public function summary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $employees = Employee::active()->with('department')->orderBy('first_name')->get();

        $summaries = $this->hrService->getBulkAttendanceSummary($employees->pluck('id')->toArray(), $month);

        return view('hr.attendance.summary', compact('employees', 'summaries', 'month'));
    }
}
