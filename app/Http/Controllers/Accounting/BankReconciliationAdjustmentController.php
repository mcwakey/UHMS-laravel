<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAdjustment;
use App\Services\BankReconciliationAdjustmentPostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankReconciliationAdjustmentController extends Controller
{
    public function __construct(protected BankReconciliationAdjustmentPostingService $service) {}

    public function store(Request $request, BankReconciliation $reconciliation)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(BankReconciliationAdjustment::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'side' => ['nullable', Rule::in(BankReconciliationAdjustment::SIDES)],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->propose($reconciliation, $data, $request->user());

        return back()->with('success', __('messages.accounting.adjustment_proposed'));
    }

    public function approve(Request $request, BankReconciliation $reconciliation, BankReconciliationAdjustment $adjustment)
    {
        abort_unless($adjustment->bank_reconciliation_id === $reconciliation->id, 404);
        $this->service->approve($adjustment, $request->user());

        return back()->with('success', __('messages.accounting.adjustment_approved'));
    }

    public function post(Request $request, BankReconciliation $reconciliation, BankReconciliationAdjustment $adjustment)
    {
        abort_unless($adjustment->bank_reconciliation_id === $reconciliation->id, 404);
        $this->service->post($adjustment, $request->user());

        return back()->with('success', __('messages.accounting.adjustment_posted'));
    }

    public function reject(Request $request, BankReconciliation $reconciliation, BankReconciliationAdjustment $adjustment)
    {
        abort_unless($adjustment->bank_reconciliation_id === $reconciliation->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->service->reject($adjustment, $data['reason'], $request->user());

        return back()->with('success', __('messages.accounting.adjustment_rejected'));
    }
}
