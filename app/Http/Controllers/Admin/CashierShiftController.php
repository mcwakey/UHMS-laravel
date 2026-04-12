<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashierShiftRequest;
use App\Models\CashierShift;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class CashierShiftController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
    ) {}

    public function index(Request $request)
    {
        $shifts = CashierShift::with(['user', 'verifiedByUser'])
            ->when($request->user_id, fn ($q, $u) => $q->byUser($u))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->dateRange($request->date_from, $request->date_to)
            ->latest('shift_date')
            ->paginate(15);

        $openShift = $this->accountingService->getOpenShift();

        return view('accounts.handover', compact('shifts', 'openShift'));
    }

    public function open(StoreCashierShiftRequest $request)
    {
        try {
            $this->accountingService->openShift($request->validated());
            return back()->with('success', 'Shift opened successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, CashierShift $shift)
    {
        $request->validate([
            'actual_closing' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->accountingService->closeShift($shift, $request->only(['actual_closing', 'notes']));
            return back()->with('success', 'Shift closed. Variance calculated.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verify(CashierShift $shift)
    {
        try {
            $this->accountingService->verifyShift($shift);
            return back()->with('success', 'Shift verified.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
