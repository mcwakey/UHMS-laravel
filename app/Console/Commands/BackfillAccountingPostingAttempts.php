<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Models\AccountingPostingAttempt;
use App\Models\AccountingPostingAttemptEvent;
use App\Models\CreditNote;
use App\Models\GoodsReceivedNote;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceReceivable;
use App\Models\Payment;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use App\Services\AccountingIdempotencyService;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillAccountingPostingAttempts extends Command
{
    protected $signature = 'accounting:posting-attempts-backfill
        {--dry-run : Inspect eligible records without writing}
        {--from= : Include records created on or after this date}
        {--to= : Include records created on or before this date}
        {--chunk=500 : Records per chunk}
        {--source-type= : Limit to one supported source alias}
        {--source-id= : Limit to one source record ID}
        {--resume-from= : Start after this source record ID}';

    protected $description = 'Backfill accounting posting-attempt metadata without creating journals or changing financial amounts.';

    public function handle(AccountingIdempotencyService $idempotency, ActivityLogService $activityLog): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, min(5000, (int) $this->option('chunk')));
        $totals = ['selected' => 0, 'created' => 0, 'skipped' => 0, 'failed' => 0];
        $sources = $this->sources();
        $only = $this->option('source-type');

        if ($only && ! isset($sources[$only])) {
            $this->error('Unsupported source type. Supported: '.implode(', ', array_keys($sources)));

            return self::INVALID;
        }

        foreach ($only ? [$only => $sources[$only]] : $sources as $alias => $definition) {
            $model = new $definition['model'];
            if (! $this->tableHasColumns($model->getTable(), ['journal_entry_id', 'accounting_error'])) {
                $this->warn("Skipping {$alias}: required accounting metadata columns are unavailable.");
                continue;
            }

            $query = $definition['model']::query()
                ->where(fn (Builder $q) => $q->whereNotNull('journal_entry_id')->orWhereNotNull('accounting_error'))
                ->when($this->option('from'), fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                ->when($this->option('to'), fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))
                ->when($this->option('source-id'), fn (Builder $q, string $id) => $q->whereKey($id))
                ->when($this->option('resume-from'), fn (Builder $q, string $id) => $q->whereKey('>', $id))
                ->orderBy($model->getKeyName());

            $query->chunkById($chunk, function ($records) use (
                &$totals,
                $alias,
                $definition,
                $idempotency,
                $dryRun,
            ) {
                foreach ($records as $record) {
                    $totals['selected']++;
                    $key = $idempotency->key($record, $record->getKey(), $definition['posting_type'], 1);

                    if (AccountingPostingAttempt::query()->where('idempotency_key', $key)->exists()) {
                        $totals['skipped']++;
                        continue;
                    }

                    if ($dryRun) {
                        $totals['created']++;
                        continue;
                    }

                    try {
                        DB::transaction(function () use ($record, $alias, $definition, $key, &$totals) {
                            $posted = ! empty($record->journal_entry_id);
                            $status = $posted ? 'posted' : 'failed';
                            $attemptedAt = $record->accounting_posted_at ?? $record->updated_at ?? $record->created_at ?? now();
                            $attempt = AccountingPostingAttempt::create([
                                'source_module' => $definition['module'],
                                'source_type' => $alias,
                                'source_id' => $record->getKey(),
                                'posting_type' => $definition['posting_type'],
                                'posting_version' => 1,
                                'idempotency_key' => $key,
                                'status' => $status,
                                'journal_entry_id' => $record->journal_entry_id,
                                'attempt_count' => 1,
                                'first_attempted_at' => $attemptedAt,
                                'last_attempted_at' => $attemptedAt,
                                'error_code' => $posted ? null : 'HISTORICAL_SOURCE_ERROR',
                                'error_message' => $posted ? null : $record->accounting_error,
                                'source_snapshot' => $record->attributesToArray(),
                                'posting_snapshot' => ['backfilled' => true, 'metadata_only' => true],
                            ]);
                            AccountingPostingAttemptEvent::create([
                                'accounting_posting_attempt_id' => $attempt->id,
                                'event_type' => 'ACCOUNTING_POSTING_ATTEMPT_BACKFILLED',
                                'from_status' => null,
                                'to_status' => $status,
                                'error_code' => $attempt->error_code,
                                'error_message' => $attempt->error_message,
                                'context' => ['source_alias' => $alias],
                                'occurred_at' => now(),
                            ]);
                            $totals['created']++;
                        });
                    } catch (Throwable $error) {
                        $totals['failed']++;
                        $this->warn("{$alias} #{$record->getKey()}: {$error->getMessage()}");
                    }
                }
            });
        }

        $this->table(['Mode', 'Selected', 'Created/eligible', 'Skipped', 'Failed'], [[
            $dryRun ? 'DRY RUN' : 'WRITE',
            $totals['selected'],
            $totals['created'],
            $totals['skipped'],
            $totals['failed'],
        ]]);

        if (! $dryRun) {
            $activityLog->log(LogModule::ACCOUNTING, 'ACCOUNTING_POSTING_ATTEMPTS_BACKFILLED', [
                'metadata' => array_merge($totals, [
                    'from' => $this->option('from'),
                    'to' => $this->option('to'),
                    'source_type' => $only,
                    'resume_from' => $this->option('resume-from'),
                ]),
            ], description: 'Accounting posting attempts metadata backfilled');
        }

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * These are the source models that already expose both journal/error metadata.
     * Later phases can add handlers without changing this command's safety contract.
     */
    protected function sources(): array
    {
        return [
            'invoice' => ['model' => Invoice::class, 'module' => 'BILLING', 'posting_type' => 'invoice'],
            'payment' => ['model' => Payment::class, 'module' => 'PAYMENTS', 'posting_type' => 'payment'],
            'discount' => ['model' => InvoiceDiscount::class, 'module' => 'BILLING', 'posting_type' => 'discount'],
            'credit_note' => ['model' => CreditNote::class, 'module' => 'BILLING', 'posting_type' => 'credit_note'],
            'invoice_receivable' => ['model' => InvoiceReceivable::class, 'module' => 'RECEIVABLES', 'posting_type' => 'allocation'],
            'goods_received_note' => ['model' => GoodsReceivedNote::class, 'module' => 'SUPPLIER_PAYABLES', 'posting_type' => 'receipt'],
            'supplier_payable' => ['model' => SupplierPayable::class, 'module' => 'SUPPLIER_PAYABLES', 'posting_type' => 'payable'],
            'supplier_payment' => ['model' => SupplierPayment::class, 'module' => 'SUPPLIER_PAYMENTS', 'posting_type' => 'payment'],
            'purchase_return' => ['model' => PurchaseReturn::class, 'module' => 'SUPPLIER_RETURNS', 'posting_type' => 'return'],
            'stock_movement' => ['model' => StockMovement::class, 'module' => 'INVENTORY', 'posting_type' => 'movement'],
        ];
    }

    protected function tableHasColumns(string $table, array $columns): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $available = collect(DB::select("PRAGMA table_info('".str_replace("'", "''", $table)."')"))
                ->pluck('name');

            return collect($columns)->every(fn (string $column) => $available->contains($column));
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME IN ({$placeholders})",
            array_merge([$table], $columns),
        );

        return (int) ($row->cnt ?? 0) === count($columns);
    }
}
