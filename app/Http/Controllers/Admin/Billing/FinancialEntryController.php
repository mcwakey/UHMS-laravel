<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\EntryType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialEntryRequest;
use App\Models\AccountCategory;
use App\Models\FinancialEntry;
use App\Services\AccountingService;
use App\Services\BasicAccountingPostingService;
use App\Services\ModuleService;
use Illuminate\Http\Request;

class FinancialEntryController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private BasicAccountingPostingService $postingService,
        private ModuleService $moduleService,
    ) {}

    public function index(Request $request)
    {
        $type = $request->route()->getName() === 'admin.accounts.income.index' ? 'income' : 'expense';
        $filters = array_merge($request->all(), ['type' => $type]);

        $entries = $this->accountingService->listEntries($filters);
        $categories = AccountCategory::active()->byType($type)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::cases();

        $totalAmount = FinancialEntry::byType($type)
            ->dateRange($request->date_from, $request->date_to)
            ->sum('amount');

        $monthTotal = FinancialEntry::byType($type)
            ->whereMonth('entry_date', now()->month)
            ->whereYear('entry_date', now()->year)
            ->sum('amount');

        $advancedAccountingEnabled = $this->moduleService->enabled('accounting_advanced');

        return view("accounts.entries.index", compact(
            'entries', 'categories', 'paymentMethods', 'type', 'totalAmount', 'monthTotal', 'advancedAccountingEnabled'
        ));
    }

    public function create(Request $request)
    {
        $type = $request->route()->getName() === 'admin.accounts.income.create' ? 'income' : 'expense';
        $categories = AccountCategory::active()->byType($type)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::cases();

        return view('accounts.entries.create', compact('type', 'categories', 'paymentMethods'));
    }

    public function store(StoreFinancialEntryRequest $request)
    {
        $entry = $this->accountingService->createEntry($request->validated());

        $route = $entry->type === EntryType::INCOME
            ? 'admin.accounts.income.index'
            : 'admin.accounts.expenses.index';

        return redirect()
            ->route($route)
            ->with('success', __('messages.financial_entries.recorded', ['type' => ucfirst($entry->type->value)]));
    }

    public function approve(FinancialEntry $entry)
    {
        try {
            $this->accountingService->approveEntry($entry);
        } catch (\InvalidArgumentException $error) {
            return back()->with('error', $error->getMessage());
        }

        return back()->with('success', __('messages.financial_entries.approved'));
    }

    public function postToGl(FinancialEntry $entry)
    {
        $result = $this->postingService->post($entry, request()->user());

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? __('accounting.basic_entry_posted', ['journal' => $result['journal']->journal_number])
                : $result['error'],
        );
    }

    public function reverseGl(Request $request, FinancialEntry $entry)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $result = $this->postingService->reverse($entry, $data['reason'], $request->user());

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? __('accounting.basic_entry_reversed') : $result['error'],
        );
    }

    public function destroy(FinancialEntry $entry)
    {
        try {
            $this->accountingService->deleteEntry($entry);
            return back()->with('success', __('messages.financial_entries.deleted'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Reports ──

    public function dailyCollection(Request $request)
    {
        $date = $request->date ?? today()->toDateString();
        $collection = $this->accountingService->getDailyCollection($date);

        return view('accounts.daily-collection', compact('collection', 'date'));
    }

    public function reconciliation(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $data = $this->accountingService->getReconciliation($from, $to);
        $stats = $this->accountingService->getFinancialStats($from, $to);

        return view('accounts.reconciliation', compact('data', 'stats', 'from', 'to'));
    }
}
