<?php

namespace App\Services;

use App\Models\InvoiceReceivable;
use App\Models\ReceivableCase;
use App\Models\ReceivableDispute;
use App\Models\ReceivablePromise;
use Illuminate\Support\Carbon;

class ReceivableWorkbenchService
{
    public function __construct(protected ARAgingService $aging) {}

    public function dashboard(array $filters = []): array
    {
        $aging = $this->aging->report($filters);
        $rows = collect($aging['rows']);
        $activePromises = ReceivablePromise::where('status', 'active')->sum('promised_amount');
        $brokenPromises = ReceivablePromise::where('status', 'broken')->count();
        $disputedAmount = ReceivableDispute::whereIn('status', ['open', 'under_review', 'credit_note_recommended', 'writeoff_recommended'])
            ->sum('disputed_amount');

        return [
            'aging' => $aging,
            'metrics' => [
                'total_ar' => $aging['grand_total'],
                'current' => $aging['buckets']['not_due']['total'] ?? 0,
                'b1_30' => $aging['buckets']['b0_30']['total'] ?? 0,
                'b31_60' => $aging['buckets']['b31_60']['total'] ?? 0,
                'b61_90' => $aging['buckets']['b61_90']['total'] ?? 0,
                'over_90' => round(($aging['buckets']['b91_120']['total'] ?? 0) + ($aging['buckets']['b120_plus']['total'] ?? 0), 2),
                'disputed_amount' => round((float) $disputedAmount, 2),
                'promised_amount' => round((float) $activePromises, 2),
                'broken_promises' => $brokenPromises,
                'unassigned_cases' => ReceivableCase::active()->whereNull('assigned_to')->count(),
                'high_risk_cases' => ReceivableCase::active()->whereIn('priority', ['high', 'critical'])->count(),
            ],
            'payer_balances' => $this->payerBalances($filters),
            'top_debtors' => $rows->sortByDesc('balance')->take(10)->values(),
            'broken_promises' => ReceivablePromise::with('receivableCase')->where('status', 'broken')->latest()->limit(10)->get(),
            'cases' => ReceivableCase::with(['assignee', 'items'])
                ->active()
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }

    public function payerBalances(array $filters = []): array
    {
        $asOf = ! empty($filters['as_of']) ? Carbon::parse($filters['as_of'])->startOfDay() : Carbon::today();
        $query = InvoiceReceivable::with(['patient', 'insuranceProvider', 'sponsor', 'corporateClient'])->open();

        if (! empty($filters['payer_type'])) {
            $query->where('payer_type', $filters['payer_type']);
        }

        $balances = [];
        $query->orderBy('payer_type')->orderBy('payer_id')->chunk(500, function ($receivables) use (&$balances, $asOf) {
            foreach ($receivables as $receivable) {
                $payerId = $receivable->payer_id ?: 0;
                $key = $receivable->payer_type.'|'.$payerId.'|'.$receivable->payerName();
                $reference = $receivable->due_date ?: $receivable->aging_start_date;
                $days = $reference ? max(0, $reference->diffInDays($asOf, false)) : 0;
                $bucket = $this->bucketForDays($days, (bool) ($receivable->due_date && $receivable->due_date->greaterThan($asOf)));

                $balances[$key] ??= [
                    'payer_type' => $receivable->payer_type,
                    'payer_id' => $payerId ?: null,
                    'payer_name' => $receivable->payerName(),
                    'count' => 0,
                    'balance' => 0.0,
                    'oldest_due_date' => null,
                    'aging_bucket' => 'current',
                    'invoice_receivable_ids' => [],
                ];

                $balances[$key]['count']++;
                $balances[$key]['balance'] = round($balances[$key]['balance'] + (float) $receivable->balance, 2);
                $balances[$key]['invoice_receivable_ids'][] = $receivable->id;
                if ($receivable->due_date && (! $balances[$key]['oldest_due_date'] || $receivable->due_date->lt($balances[$key]['oldest_due_date']))) {
                    $balances[$key]['oldest_due_date'] = $receivable->due_date;
                }
                if ($this->bucketRank($bucket) > $this->bucketRank($balances[$key]['aging_bucket'])) {
                    $balances[$key]['aging_bucket'] = $bucket;
                }
            }
        });

        return collect($balances)->sortByDesc('balance')->values()->all();
    }

    public function bucketForReceivable(InvoiceReceivable $receivable, ?Carbon $asOf = null): string
    {
        $asOf ??= Carbon::today();
        if ($receivable->due_date && $receivable->due_date->greaterThan($asOf)) {
            return 'current';
        }
        $reference = $receivable->due_date ?: $receivable->aging_start_date;
        $days = $reference ? max(0, $reference->diffInDays($asOf, false)) : 0;

        return $this->bucketForDays($days, false);
    }

    private function bucketForDays(int $days, bool $notDue): string
    {
        return match (true) {
            $notDue || $days <= 0 => 'current',
            $days <= 30 => '1_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => 'over_90',
        };
    }

    private function bucketRank(string $bucket): int
    {
        return ['current' => 0, '1_30' => 1, '31_60' => 2, '61_90' => 3, 'over_90' => 4][$bucket] ?? 0;
    }
}
