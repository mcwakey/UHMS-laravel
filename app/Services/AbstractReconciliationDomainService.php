<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPostingAttempt;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class AbstractReconciliationDomainService
{
    public function __construct(protected AccountingSettingsService $settings) {}

    abstract public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array;

    protected function account(string $setting): ?Account
    {
        return $this->settings->account($setting);
    }

    protected function glBalance(?Account $account, Carbon $asOf): float
    {
        if (! $account) {
            return 0.0;
        }

        $row = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', fn ($query) => $query->ledgerAffecting()->whereDate('entry_date', '<=', $asOf))
            ->selectRaw('COALESCE(SUM(debit), 0) debit_total, COALESCE(SUM(credit), 0) credit_total')
            ->first();

        $debit = (float) ($row->debit_total ?? 0);
        $credit = (float) ($row->credit_total ?? 0);

        return round($account->normal_balance->value === 'credit' ? $credit - $debit : $debit - $credit, 2);
    }

    protected function failedAttempt(string $sourceType, int $sourceId): ?AccountingPostingAttempt
    {
        $basename = class_basename($sourceType);

        return AccountingPostingAttempt::query()
            ->where('source_id', $sourceId)
            ->where('status', 'failed')
            ->where(function ($query) use ($sourceType, $basename) {
                $query->where('source_type', $sourceType)
                    ->orWhere('source_type', $basename)
                    ->orWhere('source_type', strtolower($basename));
            })
            ->latest('last_attempted_at')
            ->first();
    }

    protected function sourceClassification(
        string $sourceType,
        int $sourceId,
        ?string $accountingStatus,
        ?int $journalEntryId,
    ): string {
        if ($this->failedAttempt($sourceType, $sourceId) || $accountingStatus === 'failed') {
            return 'failed_posting';
        }
        if (! $journalEntryId || in_array($accountingStatus, [null, '', 'pending', 'eligible', 'not_posted'], true)) {
            return 'unposted_source';
        }

        return 'balanced';
    }

    protected function manualJournalItems(Collection $accounts, Carbon $asOf): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        return JournalEntryLine::query()
            ->with(['journalEntry', 'account'])
            ->whereIn('account_id', $accounts->pluck('id'))
            ->whereHas('journalEntry', fn ($query) => $query->ledgerAffecting()
                ->whereDate('entry_date', '<=', $asOf)
                ->where('source_module', 'MANUAL'))
            ->get()
            ->map(function (JournalEntryLine $line) {
                $amount = $line->account->normal_balance->value === 'credit'
                    ? (float) $line->credit - (float) $line->debit
                    : (float) $line->debit - (float) $line->credit;

                return $this->item(
                    sourceType: 'manual_journal',
                    sourceId: $line->journal_entry_id,
                    reference: $line->journalEntry?->journal_number,
                    description: $line->journalEntry?->description,
                    account: $line->account,
                    subledger: 0,
                    gl: round($amount, 2),
                    classification: 'manual_journal',
                    metadata: [
                        'journal_entry_id' => $line->journal_entry_id,
                        'entry_date' => $line->journalEntry?->entry_date?->toDateString(),
                    ],
                );
            })
            ->all();
    }

    protected function item(
        string $sourceType,
        ?int $sourceId,
        ?string $reference,
        ?string $description,
        ?Account $account,
        float $subledger,
        float $gl,
        string $classification,
        array $metadata = [],
    ): array {
        return [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_reference' => $reference,
            'source_description' => $description,
            'gl_account_id' => $account?->id,
            'subledger_amount' => round($subledger, 2),
            'gl_amount' => round($gl, 2),
            'difference_amount' => round($subledger - $gl, 2),
            'classification' => $classification,
            'resolution_status' => $classification === 'balanced' ? 'resolved' : 'open',
            'metadata_snapshot' => $metadata,
        ];
    }

    protected function result(
        string $availability,
        float $subledgerTotal,
        float $glTotal,
        array $items,
        array $sourceSnapshot,
        array $glSnapshot,
        ?string $availabilityReason = null,
    ): array {
        return [
            'availability' => $availability,
            'availability_reason' => $availabilityReason,
            'subledger_total' => round($subledgerTotal, 2),
            'gl_total' => round($glTotal, 2),
            'difference_amount' => round($subledgerTotal - $glTotal, 2),
            'items' => $items,
            'source_snapshot' => $sourceSnapshot,
            'gl_snapshot' => $glSnapshot,
        ];
    }
}
