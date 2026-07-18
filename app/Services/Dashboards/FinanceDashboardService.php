<?php

namespace App\Services\Dashboards;

use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Claim;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Dashboards\Concerns\BuildsPressure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Metrics for the Finance workspace dashboard. Read-only aggregate queries over
 * the existing invoice, payment, cashier-shift and claims tables — billing and
 * collections are reported separately, never merged.
 */
class FinanceDashboardService
{
    use BuildsPressure;

    private const OPEN_INVOICE_STATUSES = [
        InvoiceStatus::PENDING->value,
        InvoiceStatus::PARTIALLY_PAID->value,
    ];

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->unpaidInsight(),
            'pressure' => $this->collectionPressure(),
            'kpis' => $this->kpis(),
            'billingTrend' => $this->billingTrend(),
            'paymentMethods' => $this->paymentMethodsToday(),
            'unpaidInvoices' => Invoice::with('patient:id,patient_number,first_name,last_name,other_names')
                ->whereIn('status', self::OPEN_INVOICE_STATUSES)
                ->orderByDesc('balance')
                ->take(6)
                ->get(),
            'claimsAttention' => Claim::with('patient:id,patient_number,first_name,last_name,other_names')
                ->whereIn('status', [
                    ClaimStatus::DRAFT->value,
                    ClaimStatus::READY->value,
                    ClaimStatus::REJECTED->value,
                ])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    /**
     * Unpaid-invoice backlog banner: open invoices + oldest waiting age.
     *
     * @return array<string, mixed>|null
     */
    private function unpaidInsight(): ?array
    {
        $openQuery = Invoice::whereIn('status', self::OPEN_INVOICE_STATUSES);
        $open = (clone $openQuery)->count();
        if ($open < 1) {
            return null;
        }

        $oldest = (clone $openQuery)->oldest()->value('created_at');
        $oldestDays = $oldest ? (int) Carbon::parse($oldest)->diffInDays(now()) : 0;

        return [
            'variant' => $oldestDays >= 30 ? 'danger' : 'warning',
            'icon' => 'ti-file-invoice',
            'title' => __('finance.dashboard.unpaid_insight'),
            'badge' => __('finance.dashboard.open_invoices_count', ['count' => $open]),
            'cause' => ['icon' => 'ti-clock-exclamation', 'label' => __('finance.dashboard.oldest_open', ['days' => $oldestDays])],
            'action' => __('finance.dashboard.action_collect'),
            'link' => ['url' => route('finance.billing.invoices.index'), 'label' => __('finance.dashboard.open_invoices')],
        ];
    }

    /** @return array<string, mixed> */
    private function collectionPressure(): array
    {
        $unpaid = Invoice::where('status', InvoiceStatus::PENDING->value)->count();
        $partiallyPaid = Invoice::where('status', InvoiceStatus::PARTIALLY_PAID->value)->count();
        $openShifts = CashierShift::where('status', ShiftStatus::OPEN)->count();
        $claimsPending = Claim::whereIn('status', [ClaimStatus::DRAFT->value, ClaimStatus::READY->value])->count();

        return $this->pressure(
            __('finance.dashboard.collection_load'),
            'ti-cash',
            $unpaid + $partiallyPaid,
            [10, 25, 50],
            [
                __('finance.dashboard.unpaid_count', ['count' => $unpaid]),
                __('finance.dashboard.partially_paid_count', ['count' => $partiallyPaid]),
                __('finance.dashboard.open_shifts_count', ['count' => $openShifts]),
                __('finance.dashboard.claims_pending_count', ['count' => $claimsPending]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        return [
            'billed_today' => ['value' => (float) Invoice::whereDate('created_at', today())
                ->whereNot('status', InvoiceStatus::CANCELLED->value)
                ->sum('total_amount')],
            'collected_today' => ['value' => (float) Payment::where('status', PaymentStatus::ACTIVE->value)
                ->whereDate('paid_at', today())
                ->sum('amount')],
            'outstanding' => ['value' => (float) Invoice::whereIn('status', self::OPEN_INVOICE_STATUSES)->sum('balance')],
            'unpaid_invoices' => ['value' => Invoice::whereIn('status', self::OPEN_INVOICE_STATUSES)->count()],
        ];
    }

    /** Billed vs collected per day, last 7 days. @return array<string, mixed> */
    private function billingTrend(): array
    {
        $invoices = Invoice::whereNot('status', InvoiceStatus::CANCELLED->value)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['created_at', 'total_amount']);
        $payments = Payment::where('status', PaymentStatus::ACTIVE->value)
            ->where('paid_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['paid_at', 'amount']);

        $labels = [];
        $billed = [];
        $collected = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('D');
            $billed[] = round((float) $invoices->filter(fn ($row) => Carbon::parse($row->created_at)->isSameDay($day))->sum('total_amount'), 2);
            $collected[] = round((float) $payments->filter(fn ($row) => $row->paid_at && Carbon::parse($row->paid_at)->isSameDay($day))->sum('amount'), 2);
        }

        return ['labels' => $labels, 'billed' => $billed, 'collected' => $collected];
    }

    /** Today's collections grouped by payment method. @return array<string, float> */
    private function paymentMethodsToday(): array
    {
        $rows = Payment::where('status', PaymentStatus::ACTIVE->value)
            ->whereDate('paid_at', today())
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $method = $row->payment_method;
            $label = $method instanceof PaymentMethod
                ? $method->label()
                : ucfirst(str_replace('_', ' ', (string) $method));

            $out[$label] = (float) $row->total;
        }

        return $out;
    }
}
