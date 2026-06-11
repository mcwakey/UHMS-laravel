<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPayrollRequest;
use App\Models\Department;
use App\Models\PayrollRecord;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(Request $request)
    {
        $payPeriod = $request->get('pay_period', now()->format('Y-m'));
        $payroll = $this->payrollService->listPayroll(array_merge(
            $request->only('status', 'department_id'),
            ['pay_period' => $payPeriod]
        ));
        $summary = $this->payrollService->getPayrollSummary($payPeriod);
        $departments = Department::active()->orderBy('name')->get();
        $statuses = PayrollStatus::cases();

        return view('hr.payroll.index', compact('payroll', 'summary', 'payPeriod', 'departments', 'statuses'));
    }

    public function process(ProcessPayrollRequest $request)
    {
        $records = $this->payrollService->processPayroll(
            $request->pay_period,
            null,
            (float) ($request->allowances ?? 0),
            (float) ($request->other_deductions ?? 0)
        );

        return redirect()->route('admin.hr.payroll.index', ['pay_period' => $request->pay_period])
            ->with('success', __('messages.payroll.processed', ['count' => $records->count()]));
    }

    public function approve(Request $request)
    {
        $request->validate(['pay_period' => 'required|string']);
        $count = $this->payrollService->approvePayroll($request->pay_period);
        return redirect()->back()->with('success', __('messages.payroll.approved', ['count' => $count]));
    }

    public function markPaid(Request $request)
    {
        $request->validate(['pay_period' => 'required|string']);
        $count = $this->payrollService->markPaid($request->pay_period);
        return redirect()->back()->with('success', __('messages.payroll.marked_paid', ['count' => $count]));
    }

    public function payslip(PayrollRecord $record)
    {
        $record->load(['employee.department', 'processedByUser']);
        return view('hr.payroll.payslip', compact('record'));
    }
}
