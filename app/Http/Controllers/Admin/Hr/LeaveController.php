<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\HRService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private HRService $hrService) {}

    public function index(Request $request)
    {
        $leaves = $this->hrService->listLeaveRequests(
            $request->only('employee_id', 'status', 'leave_type', 'date_from', 'date_to')
        );
        $employees = Employee::active()->orderBy('first_name')->get();
        $leaveTypes = LeaveType::cases();
        $statuses = LeaveStatus::cases();

        return view('hr.leave.index', compact('leaves', 'employees', 'leaveTypes', 'statuses'));
    }

    public function create()
    {
        $employees = Employee::active()->orderBy('first_name')->get();
        $leaveTypes = LeaveType::cases();

        return view('hr.leave.create', compact('employees', 'leaveTypes'));
    }

    public function store(StoreLeaveRequest $request)
    {
        $this->hrService->createLeaveRequest($request->validated());
        return redirect()->route('admin.hr.leave.index')->with('success', __('messages.leave.submitted'));
    }

    public function approve(LeaveRequest $leave)
    {
        try {
            $this->hrService->approveLeave($leave);
            return redirect()->back()->with('success', __('messages.leave.approved'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        try {
            $this->hrService->rejectLeave($leave, $request->rejection_reason);
            return redirect()->back()->with('success', __('messages.leave.rejected'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
