<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementLine;
use App\Models\JournalEntryLine;

/**
 * Generates explainable match suggestions for a statement line from book
 * transactions on the linked bank GL account. Suggestions are never auto-
 * approved — the user must confirm each one.
 */
class BankMatchSuggestionService
{
    /** Day tolerance for transaction/value date proximity. */
    public const DATE_TOLERANCE_DAYS = 5;

    /**
     * @return array<int, array<string, mixed>> explainable suggestions
     */
    public function suggestForLine(BankReconciliation $reconciliation, BankStatementLine $line, int $limit = 10): array
    {
        $account = $reconciliation->bankAccount()->with('glAccount')->first();
        $glAccountId = $account?->gl_account_id;
        if (! $glAccountId) {
            return [];
        }

        $remaining = $line->remainingToMatch();
        if ($remaining <= 0) {
            return [];
        }

        // Money into the bank (statement credit) corresponds to a DEBIT on the
        // bank GL in the books; money out (statement debit) to a CREDIT.
        $bookSide = $line->credit_amount > 0 ? 'debit' : 'credit';

        $alreadyMatched = BankReconciliationMatch::query()
            ->where('status', BankReconciliationMatch::STATUS_ACTIVE)
            ->where('matchable_type', JournalEntryLine::class)
            ->pluck('matchable_id')
            ->all();

        $candidates = JournalEntryLine::query()
            ->with(['journalEntry'])
            ->where('account_id', $glAccountId)
            ->where($bookSide, '>', 0)
            ->when(! empty($alreadyMatched), fn ($q) => $q->whereNotIn('id', $alreadyMatched))
            ->whereHas('journalEntry', function ($q) use ($line) {
                $q->ledgerAffecting()->whereBetween('entry_date', [
                    $line->transaction_date->copy()->subDays(self::DATE_TOLERANCE_DAYS * 3)->toDateString(),
                    $line->transaction_date->copy()->addDays(self::DATE_TOLERANCE_DAYS * 3)->toDateString(),
                ]);
            })
            ->limit(100)
            ->get();

        $suggestions = [];
        foreach ($candidates as $candidate) {
            $bookAmount = round((float) ($bookSide === 'debit' ? $candidate->debit : $candidate->credit), 2);
            $entryDate = $candidate->journalEntry?->entry_date;
            $dateDiff = $entryDate ? $entryDate->copy()->startOfDay()->diffInDays($line->transaction_date->copy()->startOfDay()) : 999;

            $amountExact = abs($bookAmount - $remaining) < 0.005;
            $refMatch = $this->referenceMatches($line, $candidate);

            [$method, $score, $reason] = $this->classify($amountExact, $refMatch, $dateDiff);
            if ($score <= 0) {
                continue;
            }

            $suggestions[] = [
                'matchable_type' => JournalEntryLine::class,
                'matchable_id' => $candidate->id,
                'label' => trim(($candidate->journalEntry?->journal_number ?? 'JE') . ' · ' . ($candidate->description ?: $candidate->journalEntry?->description)),
                'matched_amount' => min($bookAmount, $remaining),
                'book_amount' => $bookAmount,
                'confidence_score' => $score,
                'match_method' => $method,
                'reason' => $reason,
                'date_difference' => $dateDiff,
                'reference_comparison' => [
                    'statement' => $line->reference,
                    'book' => $candidate->journalEntry?->reference_number ?? $candidate->journalEntry?->journal_number,
                    'matched' => $refMatch,
                ],
            ];
        }

        usort($suggestions, fn ($a, $b) => $b['confidence_score'] <=> $a['confidence_score']);

        return array_slice($suggestions, 0, $limit);
    }

    protected function referenceMatches(BankStatementLine $line, JournalEntryLine $candidate): bool
    {
        $needle = $line->normalized_reference;
        if (! $needle) {
            return false;
        }

        $haystacks = array_filter([
            $candidate->journalEntry?->reference_number,
            $candidate->journalEntry?->journal_number,
        ]);

        foreach ($haystacks as $hay) {
            $normHay = strtoupper(preg_replace('/[^a-z0-9]/i', '', (string) $hay));
            if ($normHay !== '' && (str_contains($normHay, $needle) || str_contains($needle, $normHay))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0:string,1:float,2:string} [method, score, reason]
     */
    protected function classify(bool $amountExact, bool $refMatch, int $dateDiff): array
    {
        if ($amountExact && $refMatch) {
            return [BankReconciliationMatch::METHOD_SUGGESTED_REFERENCE, 98.0, __('accounting.suggest_reason_amount_reference')];
        }
        if ($amountExact && $dateDiff === 0) {
            return [BankReconciliationMatch::METHOD_SUGGESTED_EXACT, 95.0, __('accounting.suggest_reason_amount_date_exact')];
        }
        if ($amountExact && $dateDiff <= self::DATE_TOLERANCE_DAYS) {
            return [BankReconciliationMatch::METHOD_SUGGESTED_AMOUNT_DATE, 85.0 - $dateDiff, __('accounting.suggest_reason_amount_near_date', ['days' => $dateDiff])];
        }
        if ($amountExact) {
            return [BankReconciliationMatch::METHOD_SUGGESTED_AMOUNT_DATE, 60.0, __('accounting.suggest_reason_amount_only')];
        }
        if ($refMatch && $dateDiff <= self::DATE_TOLERANCE_DAYS) {
            return [BankReconciliationMatch::METHOD_SUGGESTED_REFERENCE, 55.0, __('accounting.suggest_reason_reference_only')];
        }

        return ['', 0.0, ''];
    }
}
