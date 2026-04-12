<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialEntryRequest;
use App\Models\AccountCategory;
use App\Models\FinancialEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class FinancialEntryController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
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

        return view("accounts.entries.index", compact(
            'entries', 'categories', 'paymentMethods', 'type', 'totalAmount', 'monthTotal'
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
            ->with('success', ucfirst($entry->type->value) . ' entry recorded successfully.');
    }

    public function approve(FinancialEntry $entry)
    {
        $this->accountingService->approveEntry($entry);

        return back()->with('success', 'Entry approved.');
    }

    public function destroy(FinancialEntry $entry)
    {
        try {
            $this->accountingService->deleteEntry($entry);
            return back()->with('success', 'Entry deleted.');
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
