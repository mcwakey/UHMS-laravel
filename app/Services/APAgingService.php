<?php

namespace App\Services;

use App\Models\SupplierPayable;
use Illuminate\Support\Carbon;

/**
 * Accounts Payable aging — unpaid supplier payables grouped by age.
 * Mirrors ARAgingService on the payable side.
 */
class APAgingService
{
    public function report(array $filters = []): array
    {
        $asOf = ! empty($filters['as_of']) ? Carbon::parse($filters['as_of'])->startOfDay() : Carbon::today();

        $query = SupplierPayable::query()
            ->with(['supplier:id,name', 'purchaseOrder:id,po_number', 'goodsReceivedNote:id,grn_number'])
            ->open();

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $buckets = [
            'not_due' => ['label' => __('reports.statuses.not_due'), 'total' => 0.0, 'count' => 0],
            'b0_30' => ['label' => __('reports.statuses.aging_0_30'), 'total' => 0.0, 'count' => 0],
            'b31_60' => ['label' => __('reports.statuses.aging_31_60'), 'total' => 0.0, 'count' => 0],
            'b61_90' => ['label' => __('reports.statuses.aging_61_90'), 'total' => 0.0, 'count' => 0],
            'b91_120' => ['label' => __('reports.statuses.aging_91_120'), 'total' => 0.0, 'count' => 0],
            'b120_plus' => ['label' => __('reports.statuses.aging_120_plus'), 'total' => 0.0, 'count' => 0],
        ];
        $supplierSummary = [];
        $rows = [];

        $query->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderBy('aging_start_date')
            ->chunk(500, function ($payables) use (&$buckets, &$supplierSummary, &$rows, $asOf) {
                foreach ($payables as $payable) {
                    $reference = $payable->due_date ?: $payable->aging_start_date;
                    $daysOverdue = 0;
                    $bucket = 'not_due';

                    if ($payable->due_date && $payable->due_date->greaterThan($asOf)) {
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

                    $balance = round((float) $payable->balance, 2);
                    $buckets[$bucket]['total'] += $balance;
                    $buckets[$bucket]['count']++;

                    $sid = $payable->supplier_id;
                    $supplierSummary[$sid] ??= ['label' => $payable->supplier?->name ?? 'Supplier #'.$sid, 'total' => 0.0, 'count' => 0];
                    $supplierSummary[$sid]['total'] += $balance;
                    $supplierSummary[$sid]['count']++;

                    $rows[] = [
                        'id' => $payable->id,
                        'supplier_id' => $sid,
                        'supplier_name' => $payable->supplier?->name ?? 'Supplier #'.$sid,
                        'po_number' => $payable->purchaseOrder?->po_number,
                        'grn_number' => $payable->goodsReceivedNote?->grn_number,
                        'original_amount' => (float) $payable->original_amount,
                        'paid_amount' => (float) $payable->paid_amount,
                        'adjustment_amount' => round((float) $payable->return_amount + (float) $payable->credit_note_amount + (float) $payable->adjustment_amount, 2),
                        'balance' => $balance,
                        'invoice_date' => optional($payable->invoice_date)->format('d M Y'),
                        'aging_start_date' => optional($payable->aging_start_date)->format('d M Y'),
                        'due_date' => optional($payable->due_date)->format('d M Y'),
                        'days_overdue' => $daysOverdue,
                        'bucket' => $bucket,
                        'status' => $payable->status,
                    ];
                }
            });

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['total'] = round($bucket['total'], 2);
        }
        foreach ($supplierSummary as $key => $summary) {
            $supplierSummary[$key]['total'] = round((float) $summary['total'], 2);
        }
        usort($rows, fn ($a, $b) => [$b['days_overdue'], $b['balance']] <=> [$a['days_overdue'], $a['balance']]);

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'buckets' => $buckets,
            'supplier_summary' => array_values($supplierSummary),
            'rows' => $rows,
            'grand_total' => round(array_sum(array_column($buckets, 'total')), 2),
            'grand_count' => array_sum(array_column($buckets, 'count')),
        ];
    }
}
