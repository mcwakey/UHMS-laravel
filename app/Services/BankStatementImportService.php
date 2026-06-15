<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validates, previews and imports bank statement CSV files. Imported lines are
 * immutable. Duplicate files (same hash for the same bank account) and duplicate
 * lines are blocked. Nothing is persisted during preview.
 */
class BankStatementImportService
{
    public function __construct(
        protected BankStatementCsvParser $parser,
        protected BankAccountService $bankAccounts,
    ) {}

    /**
     * Parse + validate a file and return a preview. Writes NO statement lines.
     */
    public function preview(BankAccount $account, string $contents, array $mapping, User $actor): array
    {
        $this->bankAccounts->assertCanReceiveImports($account);

        $fileHash = $this->fileHash($contents);
        $duplicateFile = BankStatementImport::query()
            ->where('bank_account_id', $account->id)
            ->where('file_hash', $fileHash)
            ->whereIn('status', [BankStatementImport::STATUS_IMPORTED, BankStatementImport::STATUS_APPROVED])
            ->exists();

        $parsed = $this->parser->parse($contents, $mapping);
        $rows = $this->flagDuplicateLines($account, $parsed['rows']);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_STATEMENT_IMPORT_PREVIEWED', [
            'severity' => LogSeverity::INFO,
            'metadata' => [
                'bank_account_id' => $account->id,
                'file_hash' => $fileHash,
                'valid_rows' => count($rows),
                'error_rows' => count($parsed['errors']),
                'duplicate_file' => $duplicateFile,
            ],
        ], $account, 'Bank statement import previewed: ' . $account->name);

        return [
            'file_hash' => $fileHash,
            'duplicate_file' => $duplicateFile,
            'rows' => $rows,
            'errors' => $parsed['errors'],
            'totals' => $parsed['totals'],
        ];
    }

    /**
     * Confirm the import: create the import record and its immutable lines.
     */
    public function import(BankAccount $account, string $contents, array $mapping, array $meta, User $actor): BankStatementImport
    {
        $this->bankAccounts->assertCanReceiveImports($account);

        $fileHash = $this->fileHash($contents);

        if ($this->isDuplicateFile($account, $fileHash)) {
            throw ValidationException::withMessages([
                'file' => __('accounting.duplicate_statement_file'),
            ]);
        }

        $parsed = $this->parser->parse($contents, $mapping);
        $rows = $this->flagDuplicateLines($account, $parsed['rows']);
        $newRows = array_values(array_filter($rows, fn ($r) => empty($r['is_duplicate'])));

        if (empty($newRows)) {
            throw ValidationException::withMessages([
                'file' => __('accounting.no_importable_lines'),
            ]);
        }

        return DB::transaction(function () use ($account, $fileHash, $parsed, $newRows, $meta, $actor) {
            $import = BankStatementImport::create([
                'bank_account_id' => $account->id,
                'format' => 'csv',
                'original_filename' => $meta['original_filename'] ?? null,
                'file_hash' => $fileHash,
                'period_start' => $meta['period_start'] ?? null,
                'period_end' => $meta['period_end'] ?? null,
                'opening_balance' => $meta['opening_balance'] ?? null,
                'closing_balance' => $meta['closing_balance'] ?? null,
                'total_debit' => $parsed['totals']['debit'],
                'total_credit' => $parsed['totals']['credit'],
                'line_count' => count($newRows),
                'status' => BankStatementImport::STATUS_IMPORTED,
                'imported_by' => $actor->id,
                'imported_at' => now(),
                'error_summary' => $parsed['errors'] ?: null,
                'metadata_snapshot' => ['mapping' => $meta['mapping'] ?? null],
            ]);

            foreach ($newRows as $row) {
                BankStatementLine::create([
                    'bank_statement_import_id' => $import->id,
                    'bank_account_id' => $account->id,
                    'line_number' => $row['line_number'],
                    'transaction_date' => $row['transaction_date'],
                    'value_date' => $row['value_date'] ?? null,
                    'reference' => $row['reference'] ?? null,
                    'normalized_reference' => $row['normalized_reference'] ?? null,
                    'description' => $row['description'] ?? null,
                    'debit_amount' => $row['debit_amount'],
                    'credit_amount' => $row['credit_amount'],
                    'balance_after' => $row['balance_after'] ?? null,
                    'external_transaction_id' => $row['external_transaction_id'] ?? null,
                    'line_hash' => $row['line_hash'],
                    'match_status' => BankStatementLine::MATCH_UNMATCHED,
                    'matched_amount' => 0,
                    'unmatched_amount' => round($row['debit_amount'] + $row['credit_amount'], 2),
                ]);
            }

            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_STATEMENT_IMPORTED', [
                'severity' => LogSeverity::NOTICE,
                'causer' => $actor,
                'metadata' => [
                    'bank_account_id' => $account->id,
                    'bank_statement_import_id' => $import->id,
                    'line_count' => $import->line_count,
                ],
            ], $import, 'Bank statement imported: ' . ($import->original_filename ?? $account->name));

            return $import->refresh();
        });
    }

    public function reject(BankStatementImport $import, string $reason, User $actor): BankStatementImport
    {
        if (in_array($import->status, [BankStatementImport::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages([
                'status' => __('accounting.cannot_reject_approved_import'),
            ]);
        }

        $import->update([
            'status' => BankStatementImport::STATUS_REJECTED,
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_STATEMENT_REJECTED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'reason' => $reason,
            'metadata' => ['bank_statement_import_id' => $import->id],
        ], $import, 'Bank statement rejected: ' . ($import->original_filename ?? $import->id));

        return $import->refresh();
    }

    public function isDuplicateFile(BankAccount $account, string $fileHash): bool
    {
        return BankStatementImport::query()
            ->where('bank_account_id', $account->id)
            ->where('file_hash', $fileHash)
            ->whereIn('status', [BankStatementImport::STATUS_IMPORTED, BankStatementImport::STATUS_APPROVED])
            ->exists();
    }

    public function fileHash(string $contents): string
    {
        return hash('sha256', $contents);
    }

    public function lineHash(int $bankAccountId, array $row): string
    {
        return hash('sha256', implode('|', [
            $bankAccountId,
            $row['transaction_date'] ?? '',
            $row['normalized_reference'] ?? '',
            number_format((float) ($row['debit_amount'] ?? 0), 2, '.', ''),
            number_format((float) ($row['credit_amount'] ?? 0), 2, '.', ''),
            $row['external_transaction_id'] ?? ($row['description'] ?? ''),
        ]));
    }

    /**
     * Compute line hashes and flag rows that already exist for the account, or
     * that repeat within the same file.
     */
    protected function flagDuplicateLines(BankAccount $account, array $rows): array
    {
        $existing = BankStatementLine::query()
            ->where('bank_account_id', $account->id)
            ->pluck('line_hash')
            ->filter()
            ->flip();

        $seen = [];
        foreach ($rows as $i => $row) {
            $hash = $this->lineHash($account->id, $row);
            $rows[$i]['line_hash'] = $hash;
            $rows[$i]['is_duplicate'] = $existing->has($hash) || isset($seen[$hash]);
            $seen[$hash] = true;
        }

        return $rows;
    }
}
