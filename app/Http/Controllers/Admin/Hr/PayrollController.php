<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPayrollRequest;
use App\Models\Department;
use App\Models\PayrollRecord;
use App\Models\PayrollRun;
use App\Services\PayrollApprovalService;
use App\Services\PayrollDraftService;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService, private PayrollDraftService $drafts, private PayrollApprovalService $approvals) {}

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
        $run = $this->drafts->generate($request->pay_period);

        return redirect()->route('admin.hr.payroll.index', ['pay_period' => $request->pay_period])
            ->with('success', __('messages.payroll.processed', ['count' => $run->records->count()]));
    }

    public function review(Request $request)
    {
        $request->validate(['pay_period' => ['required', 'regex:/^\d{4}-\d{2}$/']]);
        $run = PayrollRun::where('pay_period', $request->pay_period)->firstOrFail();
        $this->approvals->review($run);
        return back()->with('success', __('payroll.reviewed'));
    }

    public function approve(Request $request)
    {
        $request->validate(['pay_period' => 'required|string']);
        $run = PayrollRun::where('pay_period', $request->pay_period)->firstOrFail();
        $this->approvals->approve($run);
        return redirect()->back()->with('success', __('messages.payroll.approved', ['count' => $run->records()->count()]));
    }

    public function markPaid(Request $request)
    {
        $request->validate(['pay_period' => 'required|string']);
        $count = $this->payrollService->markPaid($request->pay_period);
        return redirect()->back()->with('success', __('messages.payroll.marked_paid', ['count' => $count]));
    }

    public function payslip(PayrollRecord $record)
    {
        abort_unless(in_array($record->status->value, ['approved', 'paid'], true), 404);
        $record->load(['employee.department', 'processedByUser']);
        return view('hr.payroll.payslip', compact('record'));
    }
}
