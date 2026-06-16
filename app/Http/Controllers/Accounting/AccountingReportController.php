<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\Accounting\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\AccountingReconciliationService;
use App\Services\CashFlowStatementService;
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

    public function cashFlow(Request $request, CashFlowStatementService $service)
    {
        return view('accounting.reports.cash-flow', [
            'report' => $service->direct($request->only(['account_id', 'date_from', 'date_to'])),
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

    public function trialBalanceExport(Request $request, TrialBalanceService $service)
    {
        $report = $service->report($request->all());

        return $this->csv('trial-balance.csv', function ($handle) use ($report) {
            fputcsv($handle, ['Account Code', 'Account Name', 'Debit', 'Credit', 'Balance']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['account']->code,
                    $row['account']->name,
                    number_format((float) $row['debit'], 2, '.', ''),
                    number_format((float) $row['credit'], 2, '.', ''),
                    number_format((float) $row['balance'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, ['Totals', '', number_format((float) $report['total_debit'], 2, '.', ''), number_format((float) $report['total_credit'], 2, '.', ''), '']);
        });
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
            'sourceModules' => [
                'MANUAL',
                'REVERSAL',
                'OPENING_BALANCE',
                'BILLING',
                'PAYMENT',
                'PAYMENTS',
                'PAYMENT_REVERSAL',
                'PROCUREMENT',
                'SUPPLIER_PAYMENT',
                'STOCK',
                'PAYROLL',
                'PAYROLL_SETTLEMENT',
                'PAYROLL_STATUTORY_SETTLEMENT',
            ],
        ]);
    }

    public function generalLedgerExport(Request $request, GeneralLedgerService $service)
    {
        $accounts = Account::orderBy('code')->get();
        $account = $request->account_id ? Account::find($request->account_id) : $accounts->first();
        $report = $account ? $service->report($account, $request->all()) : null;

        return $this->csv('general-ledger.csv', function ($handle) use ($report) {
            fputcsv($handle, ['Date', 'Journal', 'Account', 'Description', 'Reference', 'Debit', 'Credit', 'Running Balance']);

            if (! $report) {
                return;
            }

            fputcsv($handle, ['', '', $report['account']->display_name, 'Opening balance', '', '', '', number_format((float) $report['opening_balance'], 2, '.', '')]);
            foreach ($report['rows'] as $row) {
                $line = $row['line'];
                fputcsv($handle, [
                    $line->journalEntry->entry_date?->toDateString(),
                    $line->journalEntry->journal_number,
                    $report['account']->display_name,
                    $line->description ?: $line->journalEntry->description,
                    $line->journalEntry->reference_number,
                    number_format((float) $line->debit, 2, '.', ''),
                    number_format((float) $line->credit, 2, '.', ''),
                    number_format((float) $row['running_balance'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, ['', '', $report['account']->display_name, 'Closing balance', '', '', '', number_format((float) $report['closing_balance'], 2, '.', '')]);
        });
    }

    public function cashFlowExport(Request $request, CashFlowStatementService $service)
    {
        $report = $service->direct($request->only(['account_id', 'date_from', 'date_to']));

        return $this->csv('cash-flow-statement.csv', function ($handle) use ($report) {
            fputcsv($handle, ['Cash Flow Statement', $report['method'].' method']);
            fputcsv($handle, ['Date From', $report['date_from'] ?? 'All']);
            fputcsv($handle, ['Date To', $report['date_to'] ?? 'All']);
            fputcsv($handle, []);
            fputcsv($handle, ['Opening Cash and Bank Balance', number_format((float) $report['opening_balance'], 2, '.', '')]);
            fputcsv($handle, ['Net Cash Movement', number_format((float) $report['net_change'], 2, '.', '')]);
            fputcsv($handle, ['Closing Cash and Bank Balance', number_format((float) $report['closing_balance'], 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Section', 'Date', 'Journal', 'Description', 'Reference', 'Source', 'Cash Accounts', 'Counterpart Accounts', 'Inflow', 'Outflow', 'Net']);

            foreach ($report['sections'] as $section) {
                foreach ($section['rows'] as $row) {
                    fputcsv($handle, [
                        $section['label'],
                        $row['date'],
                        $row['journal'],
                        $row['description'],
                        $row['reference'],
                        $row['source'],
                        $row['cash_accounts'],
                        $row['counterpart_accounts'],
                        number_format((float) $row['inflow'], 2, '.', ''),
                        number_format((float) $row['outflow'], 2, '.', ''),
                        number_format((float) $row['net'], 2, '.', ''),
                    ]);
                }
                fputcsv($handle, [$section['label'].' total', '', '', '', '', '', '', '', number_format((float) $section['inflows'], 2, '.', ''), number_format((float) $section['outflows'], 2, '.', ''), number_format((float) $section['net'], 2, '.', '')]);
            }
        });
    }

    private function csv(string $filename, callable $writer)
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            $writer($handle);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
