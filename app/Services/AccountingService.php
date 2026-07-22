<?php

namespace App\Services;

use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\ShiftStatus;
use App\Enums\PaymentMethod;
use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\CashierShift;
use App\Models\FinancialEntry;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Dual-write a facility-level accounting event to the central activity log.
     * Never carries patient context; never breaks the accounting action.
     */
    private function logAccounting(LogModule $module, string $event, Model $subject, string $sourceType, string $description, array $metadata, LogSeverity $severity): void
    {
        try {
            app(ActivityLogService::class)->log($module, $event, [
                'severity' => $severity,
                'metadata' => $metadata,
                'source_type' => $sourceType,
                'source_id' => $subject->getKey(),
            ], $subject, $description);
        } catch (\Throwable $e) {
            // Logging must never break an accounting action.
        }
    }

    // ── Financial Entries ──

    public function listEntries(array $filters = []): LengthAwarePaginator
    {
        return FinancialEntry::with(['category', 'recordedByUser', 'journalEntry', 'reversalJournalEntry'])
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
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        $entry = FinancialEntry::create([
            'entry_number' => FinancialEntry::generateEntryNumber(),
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,
            'description' => $data['description'],
            'entry_date' => $data['entry_date'],
            'recorded_by' => Auth::id(),
            'approval_status' => 'pending',
            'accounting_status' => 'pending',
            'posting_version' => 1,
        ]);

        $type = $entry->type instanceof \BackedEnum ? $entry->type->value : (string) $entry->type;
        $this->logAccounting(LogModule::BILLING, 'FINANCIAL_ENTRY_RECORDED', $entry, 'financial_entry',
            ucfirst($type) . ' entry recorded: ' . $entry->entry_number,
            ['entry_number' => $entry->entry_number, 'type' => $type, 'amount' => (float) $entry->amount, 'category_id' => $entry->category_id],
            LogSeverity::INFO);

        return $entry;
    }

    public function approveEntry(FinancialEntry $entry): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if (in_array($entry->accounting_status, ['posted', 'reversed'], true)) {
            throw new \InvalidArgumentException('A posted or reversed entry cannot be re-approved.');
        }

        $entry->update([
            'approved_by' => Auth::id(),
            'approval_status' => 'approved',
            'accounting_status' => 'eligible',
            'accounting_error' => null,
        ]);

        $this->logAccounting(LogModule::BILLING, 'FINANCIAL_ENTRY_APPROVED', $entry, 'financial_entry',
            'Financial entry approved: ' . $entry->entry_number,
            ['entry_number' => $entry->entry_number, 'amount' => (float) $entry->amount],
            LogSeverity::WARNING);
    }

    public function deleteEntry(FinancialEntry $entry): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if ($entry->is_approved || in_array($entry->accounting_status, ['posted', 'reversed'], true)) {
            throw new \InvalidArgumentException('Cannot delete an approved entry.');
        }

        $snapshot = ['entry_number' => $entry->entry_number, 'amount' => (float) $entry->amount];
        $entry->delete();

        $this->logAccounting(LogModule::BILLING, 'FINANCIAL_ENTRY_DELETED', $entry, 'financial_entry',
            'Financial entry deleted: ' . $snapshot['entry_number'], $snapshot, LogSeverity::WARNING);
    }

    // ── Cashier Shifts ──

    public function getOpenShift(?int $userId = null): ?CashierShift
    {
        return CashierShift::open()
            ->byUser($userId ?? Auth::id())
            ->first();
    }

    public function openShift(array $data): CashierShift
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        $existing = $this->getOpenShift();

        if ($existing) {
            throw new \InvalidArgumentException('You already have an open shift. Close it first.');
        }

        $shift = CashierShift::create([
            'user_id' => Auth::id(),
            'shift_date' => now()->toDateString(),
            'started_at' => now(),
            'opening_balance' => $data['opening_balance'] ?? 0,
            'status' => ShiftStatus::OPEN,
        ]);

        $this->logAccounting(LogModule::PAYMENTS, 'CASHIER_SHIFT_OPENED', $shift, 'cashier_shift',
            'Cashier shift opened',
            ['opening_balance' => (float) $shift->opening_balance],
            LogSeverity::INFO);

        return $shift;
    }

    public function closeShift(CashierShift $shift, array $data): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if (! $shift->is_open) {
            throw new \InvalidArgumentException('This shift is already closed.');
        }

        // Calculate expected closing from payments received during shift
        $cashPayments = Payment::where('received_by', $shift->user_id)
            ->where('payment_method', PaymentMethod::CASH->value)
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

        $this->logAccounting(LogModule::PAYMENTS, 'CASHIER_SHIFT_CLOSED', $shift, 'cashier_shift',
            'Cashier shift closed' . ($variance != 0.0 ? ' with variance ' . number_format($variance, 2) : ''),
            ['expected_closing' => (float) $expectedClosing, 'actual_closing' => (float) $actualClosing, 'variance' => (float) $variance],
            $variance != 0.0 ? LogSeverity::WARNING : LogSeverity::NOTICE);
    }

    public function verifyShift(CashierShift $shift): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if ($shift->status !== ShiftStatus::CLOSED) {
            throw new \InvalidArgumentException('Only closed shifts can be verified.');
        }

        $shift->update([
            'status' => ShiftStatus::VERIFIED,
            'verified_by' => Auth::id(),
        ]);

        $this->logAccounting(LogModule::PAYMENTS, 'CASHIER_SHIFT_VERIFIED', $shift, 'cashier_shift',
            'Cashier shift verified',
            ['variance' => (float) $shift->variance],
            LogSeverity::NOTICE);
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
            'period_revenue' => $patientPayments,
            'period_patient_revenue' => $patientPayments,
            'period_net' => ($totals['income'] ?? 0) + $patientPayments - ($totals['expense'] ?? 0),
            'today_income' => $todayIncome,
            'today_expense' => $todayExpense,
            'today_payments' => $todayPayments,
            'today_net' => $todayIncome + $todayPayments - $todayExpense,
        ];
    }

    public function getAccountingDashboard(?string $from = null, ?string $to = null): array
    {
        $from = $from ?? now()->startOfMonth()->toDateString();
        $to = $to ?? now()->toDateString();

        $postedStatuses = [JournalEntryStatus::POSTED->value, JournalEntryStatus::REVERSED->value];

        $debits = JournalEntryLine::query()
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', $postedStatuses)
                ->whereBetween('entry_date', [$from, $to]))
            ->sum('debit');

        $credits = JournalEntryLine::query()
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', $postedStatuses)
                ->whereBetween('entry_date', [$from, $to]))
            ->sum('credit');

        return [
            'from' => $from,
            'to' => $to,
            'accounts' => Account::count(),
            'active_accounts' => Account::where('is_active', true)->count(),
            'open_periods' => AccountingPeriod::where('status', PeriodStatus::OPEN->value)->count(),
            'draft_journals' => JournalEntry::where('status', JournalEntryStatus::DRAFT->value)->count(),
            'posted_journals' => JournalEntry::whereIn('status', $postedStatuses)->count(),
            'period_debits' => $debits,
            'period_credits' => $credits,
            'period_balanced' => abs((float) $debits - (float) $credits) < 0.005,
            'recent_journals' => JournalEntry::with(['createdBy', 'lines'])
                ->latest('entry_date')
                ->latest('id')
                ->limit(8)
                ->get(),
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
        $incomeByCategory = FinancialEntry::query()
            ->byType('income')
            ->dateRange($from, $to)
            ->join('account_categories', 'financial_entries.category_id', '=', 'account_categories.id')
            ->selectRaw('account_categories.name as category_name, COUNT(*) as count, SUM(financial_entries.amount) as total')
            ->groupBy('account_categories.name')
            ->orderByDesc('total')
            ->get();

        // Expense by category
        $expenseByCategory = FinancialEntry::query()
            ->byType('expense')
            ->dateRange($from, $to)
            ->join('account_categories', 'financial_entries.category_id', '=', 'account_categories.id')
            ->selectRaw('account_categories.name as category_name, COUNT(*) as count, SUM(financial_entries.amount) as total')
            ->groupBy('account_categories.name')
            ->orderByDesc('total')
            ->get();

        // Patient revenue by method
        $revenueByMethod = Payment::whereBetween('paid_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("payment_method, COUNT(*) as count, SUM(amount) as total")
            ->groupBy('payment_method')
            ->get();

        // Daily trend
        $dailyTrend = DB::select("
            SELECT d.date,
                   COALESCE(inc.total, 0) as income,
                   COALESCE(exp.total, 0) as expense,
                     COALESCE(pay.total, 0) as revenue
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
            'totals' => [
                'income' => $incomeByCategory->sum('total'),
                'expense' => $expenseByCategory->sum('total'),
                'revenue' => $revenueByMethod->sum('total'),
            ],
            'total_income' => $incomeByCategory->sum('total'),
            'total_expense' => $expenseByCategory->sum('total'),
            'total_revenue' => $revenueByMethod->sum('total'),
        ];
    }
}
