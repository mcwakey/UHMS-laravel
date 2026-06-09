<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\Accounting\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\GeneralLedgerService;
use App\Services\TrialBalanceService;
use Illuminate\Http\Request;

class AccountingReportController extends Controller
{
    public function trialBalance(Request $request, TrialBalanceService $service)
    {
        $report = $service->report($request->all());

        return view('accounting.reports.trial-balance', [
            'report' => $report,
            'fiscalYears' => FiscalYear::orderByDesc('start_date')->get(),
            'departments' => Department::orderBy('name')->get(),
            'types' => AccountType::cases(),
        ]);
    }

    public function generalLedger(Request $request, GeneralLedgerService $service)
    {
        $accounts = Account::orderBy('code')->get();
        $account = $request->account_id ? Account::find($request->account_id) : $accounts->first();
        $report = $account ? $service->report($account, $request->all()) : null;

        return view('accounting.reports.general-ledger', [
            'report' => $report,
            'accounts' => $accounts,
            'departments' => Department::orderBy('name')->get(),
            'sourceModules' => ['MANUAL', 'REVERSAL', 'OPENING_BALANCE', 'BILLING', 'PAYMENTS', 'STOCK', 'PROCUREMENT'],
        ]);
    }
}
