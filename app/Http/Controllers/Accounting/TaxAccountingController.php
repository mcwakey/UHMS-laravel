<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\TaxPayment;
use App\Models\TaxReturn;
use App\Models\TaxType;
use App\Services\TaxLedgerService;
use App\Services\TaxReturnService;
use Illuminate\Http\Request;

class TaxAccountingController extends Controller
{
    public function index(TaxLedgerService $ledger)
    {
        $ledger->ensureDefaults();

        return view('accounting.tax.index', [
            'summary' => $ledger->summary(),
            'taxTypes' => TaxType::orderBy('code')->get(),
            'returns' => TaxReturn::with('period.taxType')->orderByDesc('created_at')->limit(12)->get(),
            'payments' => TaxPayment::with('allocations')->orderByDesc('payment_date')->limit(12)->get(),
            'paymentAccounts' => Account::active()->where(fn ($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))->orderBy('code')->get(),
        ]);
    }

    public function prepareReturn(Request $request, TaxReturnService $returns)
    {
        $data = $request->validate([
            'tax_code' => ['required', 'string', 'exists:tax_types,code'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $return = $returns->prepare($data['tax_code'], $data['period_start'], $data['period_end'], $request->user());

        return back()->with('success', "Tax return {$return->return_number} prepared.");
    }

    public function approveReturn(TaxReturn $return, TaxReturnService $returns)
    {
        $returns->approve($return, auth()->user());

        return back()->with('success', "Tax return {$return->return_number} approved.");
    }

    public function recordPayment(Request $request, TaxReturnService $returns)
    {
        $data = $request->validate([
            'tax_code' => ['required', 'string', 'exists:tax_types,code'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $returns->recordPayment($data['tax_code'], $data, $request->user());

        return back()->with('success', "Tax payment {$payment->payment_number} posted.");
    }

    public function allocatePayment(Request $request, TaxPayment $payment, TaxReturnService $returns)
    {
        $data = $request->validate([
            'tax_return_id' => ['required', 'integer', 'exists:tax_returns,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $returns->allocatePayment($payment, TaxReturn::findOrFail($data['tax_return_id']), (float) $data['amount'], $request->user());

        return back()->with('success', 'Tax payment allocated.');
    }
}
