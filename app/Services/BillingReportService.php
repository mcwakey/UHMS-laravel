<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reporting queries for the billing module: accounts-receivable aging,
 * the billing dashboard, and supporting aggregates.
 */
class BillingReportService
{
    /**
     * Accounts-receivable aging. Buckets each open invoice's outstanding
     * balance by how overdue it is (relative to due_date, falling back to
     * created_at), into 0-30 / 31-60 / 61-90 / 90+ day buckets.
     */
    public function aging(array $filters = []): array
    {
        $query = Invoice::query()
            ->with(['patient:id,first_name,last_name,patient_number', 'sponsor:id,name'])
            ->whereIn('status', [
                InvoiceStatus::PENDING->value,
                InvoiceStatus::PARTIALLY_PAID->value,
            ])
            ->where('balance', '>', 0);

        if (! empty($filters['billing_type'])) {
            $query->where('billing_type', $filters['billing_type']);
        }
        if (! empty($filters['sponsor_id'])) {
            $query->where('sponsor_id', $filters['sponsor_id']);
        }

        $buckets = [
            'current' => ['label' => '0–30 days', 'total' => 0.0, 'count' => 0],
            'b31_60'  => ['label' => '31–60 days', 'total' => 0.0, 'count' => 0],
            'b61_90'  => ['label' => '61–90 days', 'total' => 0.0, 'count' => 0],
            'b90_plus' => ['label' => '90+ days', 'total' => 0.0, 'count' => 0],
        ];

        $rows = [];
        $today = Carbon::today();

        $query->orderBy('due_date')->chunk(500, function ($invoices) use (&$buckets, &$rows, $today) {
            foreach ($invoices as $invoice) {
                $reference = $invoice->due_date ?? $invoice->created_at;
                $daysOverdue = $reference ? $today->diffInDays(Carbon::parse($reference), false) * -1 : 0;
                $daysOverdue = max(0, (int) $daysOverdue);
                $balance = (float) $invoice->balance;

                $key = match (true) {
                    $daysOverdue <= 30 => 'current',
                    $daysOverdue <= 60 => 'b31_60',
                    $daysOverdue <= 90 => 'b61_90',
                    default => 'b90_plus',
                };
                $buckets[$key]['total'] += $balance;
                $buckets[$key]['count']++;

                $rows[] = [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $invoice->patient
                        ? trim($invoice->patient->first_name . ' ' . $invoice->patient->last_name)
                        : '—',
                    'patient_number' => $invoice->patient?->patient_number,
                    'sponsor_name' => $invoice->sponsor?->name,
                    'billing_type' => $invoice->billing_type?->label(),
                    'due_date' => optional($invoice->due_date)->format('d M Y'),
                    'days_overdue' => $daysOverdue,
                    'bucket' => $key,
                    'balance' => round($balance, 2),
                ];
            }
        });

        foreach ($buckets as $k => $b) {
            $buckets[$k]['total'] = round($b['total'], 2);
        }

        $grandTotal = round(array_sum(array_column($buckets, 'total')), 2);
        $grandCount = array_sum(array_column($buckets, 'count'));

        usort($rows, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return [
            'buckets' => $buckets,
            'rows' => $rows,
            'grand_total' => $grandTotal,
            'grand_count' => $grandCount,
        ];
    }

    /**
     * Billing dashboard metrics.
     */
    public function dashboard(): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $collectedToday = (float) Payment::active()
            ->whereDate('paid_at', $today)->sum('amount');
        $collectedMonth = (float) Payment::active()
            ->where('paid_at', '>=', $monthStart)->sum('amount');

        $outstanding = (float) Invoice::whereIn('status', [
            InvoiceStatus::PENDING->value,
            InvoiceStatus::PARTIALLY_PAID->value,
        ])->sum('balance');

        $billedMonth = (float) Invoice::where('created_at', '>=', $monthStart)
            ->whereNotIn('status', [InvoiceStatus::CANCELLED->value, InvoiceStatus::DRAFT->value])
            ->sum('total_amount');

        $statusCounts = Invoice::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(balance) as balance'))
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $status = $row->status instanceof InvoiceStatus
                    ? $row->status
                    : InvoiceStatus::tryFrom((string) $row->status);

                return [
                    'value' => $status?->value ?? (string) $row->status,
                    'label' => $status?->label() ?? (string) $row->status,
                    'color' => $status?->color() ?? 'secondary',
                    'count' => (int) $row->count,
                    'balance' => round((float) $row->balance, 2),
                ];
            })->values();

        $recentPayments = Payment::with(['patient:id,first_name,last_name', 'invoice:id,invoice_number'])
            ->latest('paid_at')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'payment_number' => $p->payment_number,
                'amount' => (float) $p->amount,
                'is_reversal' => (bool) $p->is_reversal,
                'method' => $p->payment_method?->label() ?? (string) $p->payment_method,
                'patient_name' => $p->patient ? trim($p->patient->first_name . ' ' . $p->patient->last_name) : '—',
                'invoice_number' => $p->invoice?->invoice_number,
                'paid_at' => optional($p->paid_at)->format('d M Y H:i'),
            ])->values();

        $topDebtors = Invoice::select('patient_id', DB::raw('SUM(balance) as balance'), DB::raw('COUNT(*) as invoices'))
            ->with('patient:id,first_name,last_name,patient_number')
            ->whereIn('status', [InvoiceStatus::PENDING->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->where('balance', '>', 0)
            ->groupBy('patient_id')
            ->orderByDesc('balance')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'patient_id' => $row->patient_id,
                'patient_name' => $row->patient ? trim($row->patient->first_name . ' ' . $row->patient->last_name) : '—',
                'patient_number' => $row->patient?->patient_number,
                'balance' => round((float) $row->balance, 2),
                'invoices' => (int) $row->invoices,
            ])->values();

        // 14-day collection trend.
        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $trend[] = [
                'date' => $day->format('d M'),
                'amount' => (float) Payment::active()->whereDate('paid_at', $day)->sum('amount'),
            ];
        }

        return [
            'collected_today' => round($collectedToday, 2),
            'collected_month' => round($collectedMonth, 2),
            'outstanding' => round($outstanding, 2),
            'billed_month' => round($billedMonth, 2),
            'status_counts' => $statusCounts,
            'recent_payments' => $recentPayments,
            'top_debtors' => $topDebtors,
            'trend' => $trend,
        ];
    }

    /**
     * Manual discount event report.
     */
    public function discounts(array $filters = []): array
    {
        $query = InvoiceDiscount::query()
            ->with([
                'invoice:id,invoice_number,patient_id',
                'invoice.patient:id,first_name,last_name,patient_number',
                'invoiceItem:id,description',
                'performedBy:id,first_name,last_name',
            ]);

        if (! empty($filters['date_from'])) {
            $query->whereDate('performed_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('performed_at', '<=', $filters['date_to']);
        }
        if (($filters['override'] ?? '') !== '') {
            $query->where('is_override', (bool) $filters['override']);
        }

        $summaryQuery = clone $query;
        $events = $query->latest('performed_at')->paginate(30)->withQueryString();

        return [
            'summary' => [
                'total_events' => (int) (clone $summaryQuery)->count(),
                'total_discount_added' => round((float) (clone $summaryQuery)
                    ->whereColumn('new_discount_amount', '>', 'old_discount_amount')
                    ->selectRaw('COALESCE(SUM(new_discount_amount - old_discount_amount), 0) AS total')
                    ->value('total'), 2),
                'total_discount_removed' => round((float) (clone $summaryQuery)
                    ->whereColumn('old_discount_amount', '>', 'new_discount_amount')
                    ->selectRaw('COALESCE(SUM(old_discount_amount - new_discount_amount), 0) AS total')
                    ->value('total'), 2),
                'override_events' => (int) (clone $summaryQuery)->where('is_override', true)->count(),
            ],
            'events' => $events->through(fn (InvoiceDiscount $event) => [
                'id' => $event->id,
                'performed_at' => optional($event->performed_at)->format('d M Y H:i'),
                'invoice_number' => $event->invoice?->invoice_number,
                'invoice_url' => $event->invoice ? route('admin.billing.invoices.show', $event->invoice_id) : null,
                'patient_name' => $event->invoice?->patient
                    ? trim($event->invoice->patient->first_name . ' ' . $event->invoice->patient->last_name)
                    : 'N/A',
                'patient_number' => $event->invoice?->patient?->patient_number,
                'item' => $event->invoiceItem?->description,
                'action' => $event->action,
                'old_discount_amount' => (float) $event->old_discount_amount,
                'new_discount_amount' => (float) $event->new_discount_amount,
                'is_override' => (bool) $event->is_override,
                'reason' => $event->reason,
                'performed_by' => $event->performedBy?->name ?? 'System',
            ]),
        ];
    }
}
