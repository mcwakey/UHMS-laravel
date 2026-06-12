<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\Accounting\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\AccountingReconciliationService;
use App\Services\FinancialReportService;
use App\Services\GeneralLedgerService;
use App\Services\TrialBalanceService;
use Illuminate\Http\Request;

class AccountingReportController extends Controller
{
    public function profitLoss(Request $request, FinancialReportService $service)
    {
        return view('accounting.reports.profit-loss', [
            'report' => $service->profitLoss($this->dateFilters($request)),
            'fiscalYears' => FiscalYear::orderByDesc('start_date')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function balanceSheet(Request $request, FinancialReportService $service, AccountingReconciliationService $recon)
    {
        return view('accounting.reports.balance-sheet', [
            'report' => $service->balanceSheet($this->dateFilters($request)),
            'reconciliation' => $recon->checks(),
        ]);
    }

    public function cashbook(Request $request, FinancialReportService $service)
    {
        return view('accounting.reports.cashbook', [
            'report' => $service->cashbook($request->only(['account_id', 'date_from', 'date_to'])),
            'accounts' => Account::query()->where(fn ($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))->orderBy('code')->get(),
        ]);
    }

    public function revenueByDepartment(Request $request, FinancialReportService $service)
    {
        return view('accounting.reports.by-department', [
            'report' => $service->byDepartment(AccountType::INCOME->value, $this->dateFilters($request)),
            'title' => __('accounting.revenue_by_department'),
            'route' => 'admin.accounting.reports.revenue-by-department',
        ]);
    }

    public function expenseByDepartment(Request $request, FinancialReportService $service)
    {
        return view('accounting.reports.by-department', [
            'report' => $service->byDepartment(AccountType::EXPENSE->value, $this->dateFilters($request)),
            'title' => __('accounting.expense_by_department'),
            'route' => 'admin.accounting.reports.expense-by-department',
        ]);
    }

    private function dateFilters(Request $request): array
    {
        return $request->only(['date_from', 'date_to', 'fiscal_year_id', 'department_id']);
    }

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
