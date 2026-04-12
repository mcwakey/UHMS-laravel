<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\FinancialEntry;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    // ── Financial Entries ──

    public function listEntries(array $filters = []): LengthAwarePaginator
    {
        return FinancialEntry::with(['category', 'recordedByUser'])
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->byType($t))
            ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->byCategory($c))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['payment_method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m))
            ->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->latest('entry_date')
            ->paginate(15);
    }

    public function createEntry(array $data): FinancialEntry
    {
        return FinancialEntry::create([
            'entry_number' => FinancialEntry::generateEntryNumber(),
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,
            'description' => $data['description'],
            'entry_date' => $data['entry_date'],
            'recorded_by' => auth()->id(),
        ]);
    }

    public function approveEntry(FinancialEntry $entry): void
    {
        $entry->update(['approved_by' => auth()->id()]);
    }

    public function deleteEntry(FinancialEntry $entry): void
    {
        if ($entry->is_approved) {
            throw new \InvalidArgumentException('Cannot delete an approved entry.');
        }

        $entry->delete();
    }

    // ── Cashier Shifts ──

    public function getOpenShift(?int $userId = null): ?CashierShift
    {
        return CashierShift::open()
            ->byUser($userId ?? auth()->id())
            ->first();
    }

    public function openShift(array $data): CashierShift
    {
        $existing = $this->getOpenShift();

        if ($existing) {
            throw new \InvalidArgumentException('You already have an open shift. Close it first.');
        }

        return CashierShift::create([
            'user_id' => auth()->id(),
            'shift_date' => now()->toDateString(),
            'started_at' => now(),
            'opening_balance' => $data['opening_balance'] ?? 0,
            'status' => ShiftStatus::OPEN,
        ]);
    }

    public function closeShift(CashierShift $shift, array $data): void
    {
        if (! $shift->is_open) {
            throw new \InvalidArgumentException('This shift is already closed.');
        }

        // Calculate expected closing from payments received during shift
        $cashPayments = Payment::where('received_by', $shift->user_id)
            ->where('payment_method', 'cash')
            ->whereBetween('paid_at', [$shift->started_at, now()])
            ->sum('amount');

        $expectedClosing = $shift->opening_balance + $cashPayments;
        $actualClosing = $data['actual_closing'];
        $variance = $actualClosing - $expectedClosing;

        $shift->update([
            'ended_at' => now(),
            'expected_closing' => $expectedClosing,
            'actual_closing' => $actualClosing,
            'variance' => $variance,
            'notes' => $data['notes'] ?? null,
            'status' => ShiftStatus::CLOSED,
        ]);
    }

    public function verifyShift(CashierShift $shift): void
    {
        if ($shift->status !== ShiftStatus::CLOSED) {
            throw new \InvalidArgumentException('Only closed shifts can be verified.');
        }

        $shift->update([
            'status' => ShiftStatus::VERIFIED,
            'verified_by' => auth()->id(),
        ]);
    }

    // ── Dashboard / Stats ──

    public function getFinancialStats(?string $from = null, ?string $to = null): array
    {
        $from = $from ?? now()->startOfMonth()->toDateString();
        $to = $to ?? now()->toDateString();

        $totals = FinancialEntry::whereBetween('entry_date', [$from, $to])
            ->selectRaw("type, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $patientPayments = Payment::whereBetween('paid_at', [$from, $to . ' 23:59:59'])
            ->sum('amount');

        $todayIncome = FinancialEntry::where('type', 'income')
            ->whereDate('entry_date', today())
            ->sum('amount');

        $todayExpense = FinancialEntry::where('type', 'expense')
            ->whereDate('entry_date', today())
            ->sum('amount');

        $todayPayments = Payment::whereDate('paid_at', today())->sum('amount');

        return [
            'period_income' => $totals['income'] ?? 0,
            'period_expense' => $totals['expense'] ?? 0,
            'period_patient_revenue' => $patientPayments,
            'period_net' => ($totals['income'] ?? 0) + $patientPayments - ($totals['expense'] ?? 0),
            'today_income' => $todayIncome,
            'today_expense' => $todayExpense,
            'today_payments' => $todayPayments,
            'today_net' => $todayIncome + $todayPayments - $todayExpense,
        ];
    }

    public function getDailyCollection(?string $date = null): array
    {
        $date = $date ?? today()->toDateString();

        // Patient payments by method
        $payments = Payment::whereDate('paid_at', $date)
            ->selectRaw("payment_method, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('payment_method')
            ->get();

        // Financial income entries
        $incomeEntries = FinancialEntry::where('type', 'income')
            ->whereDate('entry_date', $date)
            ->with('category')
            ->get();

        // Financial expense entries
        $expenseEntries = FinancialEntry::where('type', 'expense')
            ->whereDate('entry_date', $date)
            ->with('category')
            ->get();

        return [
            'date' => $date,
            'payments' => $payments,
            'payments_total' => $payments->sum('total'),
            'income_entries' => $incomeEntries,
            'income_total' => $incomeEntries->sum('amount'),
            'expense_entries' => $expenseEntries,
            'expense_total' => $expenseEntries->sum('amount'),
            'grand_total' => $payments->sum('total') + $incomeEntries->sum('amount'),
        ];
    }

    public function getReconciliation(string $from, string $to): array
    {
        // Income by category
        $incomeByCategory = FinancialEntry::where('type', 'income')
            ->whereBetween('entry_date', [$from, $to])
            ->join('account_categories', 'financial_entries.category_id', '=', 'account_categories.id')
            ->selectRaw('account_categories.name as category, SUM(financial_entries.amount) as total')
            ->groupBy('account_categories.name')
            ->orderByDesc('total')
            ->get();

        // Expense by category
        $expenseByCategory = FinancialEntry::where('type', 'expense')
            ->whereBetween('entry_date', [$from, $to])
            ->join('account_categories', 'financial_entries.category_id', '=', 'account_categories.id')
            ->selectRaw('account_categories.name as category, SUM(financial_entries.amount) as total')
            ->groupBy('account_categories.name')
            ->orderByDesc('total')
            ->get();

        // Patient revenue by method
        $revenueByMethod = Payment::whereBetween('paid_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("payment_method, SUM(amount) as total")
            ->groupBy('payment_method')
            ->get();

        // Daily trend
        $dailyTrend = DB::select("
            SELECT d.date,
                   COALESCE(inc.total, 0) as income,
                   COALESCE(exp.total, 0) as expense,
                   COALESCE(pay.total, 0) as payments
            FROM (
                SELECT DATE(entry_date) as date FROM financial_entries WHERE entry_date BETWEEN ? AND ?
                UNION
                SELECT DATE(paid_at) as date FROM payments WHERE paid_at BETWEEN ? AND ?
            ) d
            LEFT JOIN (SELECT entry_date as date, SUM(amount) as total FROM financial_entries WHERE type='income' AND entry_date BETWEEN ? AND ? GROUP BY entry_date) inc ON d.date = inc.date
            LEFT JOIN (SELECT entry_date as date, SUM(amount) as total FROM financial_entries WHERE type='expense' AND entry_date BETWEEN ? AND ? GROUP BY entry_date) exp ON d.date = exp.date
            LEFT JOIN (SELECT DATE(paid_at) as date, SUM(amount) as total FROM payments WHERE paid_at BETWEEN ? AND ? GROUP BY DATE(paid_at)) pay ON d.date = pay.date
            ORDER BY d.date
        ", [$from, $to, $from, $to . ' 23:59:59', $from, $to, $from, $to, $from, $to . ' 23:59:59']);

        return [
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
            'revenue_by_method' => $revenueByMethod,
            'daily_trend' => $dailyTrend,
            'total_income' => $incomeByCategory->sum('total'),
            'total_expense' => $expenseByCategory->sum('total'),
            'total_revenue' => $revenueByMethod->sum('total'),
        ];
    }
}
