<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\InvoiceReceivable;
use App\Models\ReceivableStatementRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReceivableStatementService
{
    public function __construct(protected ActivityLogService $activity) {}

    public function generate(string $payerType, ?int $payerId, string $periodStart, string $periodEnd, User $user): ReceivableStatementRun
    {
        return DB::transaction(function () use ($payerType, $payerId, $periodStart, $periodEnd, $user) {
            $receivables = InvoiceReceivable::with(['invoice', 'payments'])
                ->where('payer_type', $payerType)
                ->when($payerId, fn ($query) => $query->where('payer_id', $payerId))
                ->whereDate('aging_start_date', '<=', $periodEnd)
                ->orderBy('aging_start_date')
                ->get();

            $payerName = $receivables->first()?->payerName() ?? ucfirst($payerType).' payer';
            $opening = round((float) $receivables->where('aging_start_date', '<', $periodStart)->sum('balance'), 2);
            $charges = round((float) $receivables->whereBetween('aging_start_date', [$periodStart, $periodEnd])->sum('allocated_amount'), 2);
            $payments = round((float) $receivables->sum('paid_amount'), 2);
            $creditNotes = round((float) $receivables->sum('credit_note_amount'), 2);
            $writeoffs = round((float) $receivables->sum('write_off_amount'), 2);
            $closing = round((float) $receivables->sum('balance'), 2);

            $statement = ReceivableStatementRun::create([
                'statement_number' => $this->nextStatementNumber(),
                'payer_type' => $payerType,
                'payer_id' => $payerId,
                'payer_name_snapshot' => $payerName,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'generated',
                'opening_balance' => $opening,
                'charges' => $charges,
                'payments' => $payments,
                'credit_notes' => $creditNotes,
                'writeoffs' => $writeoffs,
                'closing_balance' => $closing,
                'generated_by' => $user->id,
                'generated_at' => now(),
                'metadata_snapshot' => json_encode(['receivable_count' => $receivables->count()]),
            ]);

            $balance = $opening;
            foreach ($receivables as $receivable) {
                $debit = round((float) $receivable->allocated_amount, 2);
                $credit = round((float) $receivable->paid_amount + (float) $receivable->credit_note_amount + (float) $receivable->write_off_amount, 2);
                $balance = round($balance + $debit - $credit, 2);
                $statement->items()->create([
                    'source_type' => InvoiceReceivable::class,
                    'source_id' => $receivable->id,
                    'transaction_date' => $receivable->aging_start_date,
                    'description' => 'Invoice '.$receivable->invoice?->invoice_number,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'balance_after' => $balance,
                ]);
            }

            $this->activity->log(LogModule::BILLING, 'RECEIVABLE_STATEMENT_GENERATED', ['statement_number' => $statement->statement_number], $statement);

            return $statement->refresh()->load('items');
        });
    }

    public function approve(ReceivableStatementRun $statement, User $user): ReceivableStatementRun
    {
        $statement->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_STATEMENT_APPROVED', ['statement_number' => $statement->statement_number], $statement);

        return $statement->refresh();
    }

    private function nextStatementNumber(): string
    {
        $prefix = 'ARS-'.now()->format('Y').'-';
        $last = ReceivableStatementRun::where('statement_number', 'like', $prefix.'%')->orderByDesc('statement_number')->value('statement_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
