<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PayrollRun;
use App\Models\PayrollSettlement;
use App\Models\PayrollStatutorySettlement;
use App\Services\PayrollAccountingService;
use Illuminate\Http\Request;

class PayrollPostingController extends Controller
{
    public function __construct(protected PayrollAccountingService $payrollAccounting) {}

    public function index(Request $request)
    {
        $payPeriod = $request->get('pay_period');
        $runs = PayrollRun::with(['journalEntry', 'settlements.journalEntry', 'settlements.paymentAccount', 'statutorySettlements.journalEntry', 'statutorySettlements.paymentAccount'])
            ->withCount('records')
            ->when($payPeriod, fn ($query) => $query->where('pay_period', $payPeriod))
            ->orderByDesc('period_end')
            ->paginate(12);

        $previews = $runs->getCollection()
            ->mapWithKeys(fn (PayrollRun $run) => [$run->id => $this->payrollAccounting->preview($run)]);
        $paymentAccounts = Account::active()
            ->where(fn ($query) => $query->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->orderBy('code')
            ->get();

        return view('accounting.payroll-posting.index', compact('runs', 'previews', 'paymentAccounts', 'payPeriod'));
    }

    public function post(PayrollRun $payrollRun)
    {
        $journal = $this->payrollAccounting->postPayroll($payrollRun, auth()->user());

        return back()->with('success', "Payroll posted to {$journal->journal_number}.");
    }

    public function reverse(Request $request, PayrollRun $payrollRun)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $journal = $this->payrollAccounting->reversePayroll($payrollRun, auth()->user(), $data['reason']);

        return back()->with('success', "Payroll accrual reversed with {$journal->journal_number}.");
    }

    public function settle(Request $request, PayrollRun $payrollRun)
    {
        $data = $request->validate([
            'settlement_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $settlement = $this->payrollAccounting->settlePayroll($payrollRun, $data, auth()->user());

        return back()->with('success', "Salary settlement posted to {$settlement->journalEntry?->journal_number}.");
    }

    public function reverseSettlement(Request $request, PayrollSettlement $settlement)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $journal = $this->payrollAccounting->reverseSettlement($settlement, auth()->user(), $data['reason']);

        return back()->with('success', "Salary settlement reversed with {$journal->journal_number}.");
    }

    public function settleStatutory(Request $request, PayrollRun $payrollRun)
    {
        $data = $request->validate([
            'liability_type' => ['required', 'string', 'in:paye,pension'],
            'settlement_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $settlement = $this->payrollAccounting->settleStatutoryLiability($payrollRun, $data, auth()->user());

        return back()->with('success', strtoupper($settlement->liability_type) . " settlement posted to {$settlement->journalEntry?->journal_number}.");
    }

    public function reverseStatutory(Request $request, PayrollStatutorySettlement $settlement)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $journal = $this->payrollAccounting->reverseStatutorySettlement($settlement, auth()->user(), $data['reason']);

        return back()->with('success', "Statutory settlement reversed with {$journal->journal_number}.");
    }
}
