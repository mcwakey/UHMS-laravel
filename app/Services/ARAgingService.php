<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use Illuminate\Support\Carbon;

class ARAgingService
{
    public function __construct(protected InvoiceReceivableService $receivableService) {}

    public function report(array $filters = []): array
    {
        $this->ensureOpenInvoiceReceivables();

        $asOf = ! empty($filters['as_of']) ? Carbon::parse($filters['as_of'])->startOfDay() : Carbon::today();

        $query = InvoiceReceivable::query()
            ->with([
                'invoice:id,invoice_number,billing_type,status,total_amount,balance,due_date',
                'patient:id,first_name,last_name,patient_number',
                'insuranceProvider:id,name',
                'sponsor:id,name',
                'corporateClient:id,name',
            ])
            ->open();

        if (! empty($filters['payer_type'])) {
            $query->where('payer_type', $filters['payer_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['sponsor_id'])) {
            $query->where('sponsor_id', $filters['sponsor_id']);
        }
        if (! empty($filters['insurance_provider_id'])) {
            $query->where('insurance_provider_id', $filters['insurance_provider_id']);
        }
        if (! empty($filters['corporate_client_id'])) {
            $query->where('corporate_client_id', $filters['corporate_client_id']);
        }

        $buckets = [
            'not_due' => ['label' => 'Not Due', 'total' => 0.0, 'count' => 0],
            'b0_30' => ['label' => '0-30 days', 'total' => 0.0, 'count' => 0],
            'b31_60' => ['label' => '31-60 days', 'total' => 0.0, 'count' => 0],
            'b61_90' => ['label' => '61-90 days', 'total' => 0.0, 'count' => 0],
            'b91_120' => ['label' => '91-120 days', 'total' => 0.0, 'count' => 0],
            'b120_plus' => ['label' => '120+ days', 'total' => 0.0, 'count' => 0],
        ];
        $payerSummary = [
            'patient' => ['label' => 'Patient', 'total' => 0.0, 'count' => 0],
            'insurance' => ['label' => 'Insurance', 'total' => 0.0, 'count' => 0],
            'sponsor' => ['label' => 'Sponsor', 'total' => 0.0, 'count' => 0],
            'corporate' => ['label' => 'Corporate', 'total' => 0.0, 'count' => 0],
        ];

        $rows = [];
        $query->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderBy('aging_start_date')
            ->chunk(500, function ($receivables) use (&$buckets, &$payerSummary, &$rows, $asOf) {
                foreach ($receivables as $receivable) {
                    $reference = $receivable->due_date ?: $receivable->aging_start_date;
                    $daysOverdue = 0;
                    $bucket = 'not_due';

                    if ($receivable->due_date && $receivable->due_date->greaterThan($asOf)) {
                        $daysOverdue = 0;
                        $bucket = 'not_due';
                    } else {
                        $daysOverdue = $reference ? max(0, $reference->diffInDays($asOf, false)) : 0;
                        $bucket = match (true) {
                            $daysOverdue <= 30 => 'b0_30',
                            $daysOverdue <= 60 => 'b31_60',
                            $daysOverdue <= 90 => 'b61_90',
                            $daysOverdue <= 120 => 'b91_120',
                            default => 'b120_plus',
                        };
                    }

                    $balance = round((float) $receivable->balance, 2);
                    $buckets[$bucket]['total'] += $balance;
                    $buckets[$bucket]['count']++;
                    if (! isset($payerSummary[$receivable->payer_type])) {
                        $payerSummary[$receivable->payer_type] = [
                            'label' => ucfirst((string) $receivable->payer_type),
                            'total' => 0.0,
                            'count' => 0,
                        ];
                    }
                    $payerSummary[$receivable->payer_type]['total'] += $balance;
                    $payerSummary[$receivable->payer_type]['count']++;

                    $rows[] = [
                        'id' => $receivable->id,
                        'invoice_id' => $receivable->invoice_id,
                        'invoice_number' => $receivable->invoice?->invoice_number,
                        'invoice_url' => route('admin.billing.invoices.show', $receivable->invoice_id),
                        'patient_name' => $receivable->patient
                            ? trim($receivable->patient->first_name . ' ' . $receivable->patient->last_name)
                            : 'N/A',
                        'patient_number' => $receivable->patient?->patient_number,
                        'payer_type' => $receivable->payer_type,
                        'payer_name' => $receivable->payerName(),
                        'allocated_amount' => (float) $receivable->allocated_amount,
                        'paid_amount' => (float) $receivable->paid_amount,
                        'adjustment_amount' => round((float) $receivable->credit_note_amount + (float) $receivable->write_off_amount, 2),
                        'balance' => $balance,
                        'due_date' => optional($receivable->due_date)->format('d M Y'),
                        'aging_start_date' => optional($receivable->aging_start_date)->format('d M Y'),
                        'days_overdue' => $daysOverdue,
                        'bucket' => $bucket,
                        'status' => $receivable->status,
                    ];
                }
            });

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['total'] = round($bucket['total'], 2);
        }
        foreach ($payerSummary as $key => $summary) {
            $payerSummary[$key]['total'] = round((float) $summary['total'], 2);
        }

        usort($rows, fn ($a, $b) => [$b['days_overdue'], $b['balance']] <=> [$a['days_overdue'], $a['balance']]);

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'buckets' => $buckets,
            'payer_summary' => $payerSummary,
            'rows' => $rows,
            'grand_total' => round(array_sum(array_column($buckets, 'total')), 2),
            'grand_count' => array_sum(array_column($buckets, 'count')),
        ];
    }

    private function ensureOpenInvoiceReceivables(): void
    {
        Invoice::query()
            ->whereIn('status', [InvoiceStatus::PENDING->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->where('balance', '>', 0)
            ->whereDoesntHave('receivables')
            ->with(['items', 'payments', 'creditNotes', 'claim', 'visit.visitInsurance'])
            ->orderBy('id')
            ->limit(250)
            ->get()
            ->each(fn (Invoice $invoice) => $this->receivableService->syncFromInvoice($invoice));
    }
}
