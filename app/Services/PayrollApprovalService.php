<?php

namespace App\Services;

use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollApprovalService
{
    public function __construct(private PayslipGenerationService $payslips, private ActivityLogService $log) {}

    public function review(PayrollRun $run): PayrollRun
    {
        if ($run->status !== 'draft') throw ValidationException::withMessages(['status' => 'Only draft payroll may be reviewed.']);
        $run->update(['status' => 'under_review', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        return $run->fresh();
    }

    public function approve(PayrollRun $run): PayrollRun
    {
        if ($run->status !== 'under_review') throw ValidationException::withMessages(['status' => 'Payroll must be reviewed before approval.']);
        return DB::transaction(function () use ($run) {
            $run->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
            $run->records()->update(['status' => 'approved']);
            $this->payslips->generateFor($run);
            $this->log->log('SYSTEM', 'PAYROLL_APPROVED', [
                'source_type' => 'payroll_run',
                'source_id' => $run->id,
                'metadata' => ['pay_period' => $run->pay_period, 'records' => $run->records()->count()],
            ], $run);
            return $run->fresh('payslips');
        });
    }
}
