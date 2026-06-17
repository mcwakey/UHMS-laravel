<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\AccountingPostingAttempt;
use App\Models\AccountingReconciliationItem;
use App\Models\AccountingReconciliationResolution;
use App\Models\AccountingReconciliationRun;
use App\Models\InvoiceReceivable;
use App\Models\ReceivableCase;
use App\Models\ReceivableCreditnoteRecommendation;
use App\Models\ReceivableDispute;
use App\Models\ReceivablePromise;
use App\Models\ReceivableWriteoffRecommendation;
use App\Models\User;
use Carbon\Carbon;

class AccountingCloseReadinessService
{
    public function __construct(protected AccountingPostingHandlerRegistry $handlers) {}

    public function summary(Carbon|string $from, Carbon|string $to, ?User $actor = null): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();
        $base = AccountingPostingAttempt::query()
            ->whereBetween('last_attempted_at', [$from, $to]);
        $failedAttempts = (clone $base)->where('status', 'failed')->get();
        $material = $failedAttempts->map(function (AccountingPostingAttempt $attempt) {
            $snapshot = $attempt->source_snapshot ?: [];
            foreach (['amount', 'total_amount', 'grand_total', 'net_amount'] as $key) {
                if (isset($snapshot[$key]) && is_numeric($snapshot[$key])) {
                    return (float) $snapshot[$key];
                }
            }
            return null;
        })->filter(fn ($amount) => $amount !== null);
        $reconciliationRuns = AccountingReconciliationRun::query()
            ->whereDate('period_start', '<=', $to)
            ->whereDate('period_end', '>=', $from)
            ->whereNotIn('status', ['cancelled', 'superseded'])
            ->get();
        $latestReconciliations = collect(AccountingReconciliationRun::TYPES)->mapWithKeys(function (string $type) use ($reconciliationRuns) {
            return [$type => $reconciliationRuns->where('reconciliation_type', $type)->sortByDesc('completed_at')->first()];
        });
        $runIds = $reconciliationRuns->pluck('id');

        $summary = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'unresolved_failed_postings' => (clone $base)->where('status', 'failed')->count(),
            'waived_postings' => (clone $base)->where('status', 'waived')->count(),
            'resolved_postings' => (clone $base)->where('status', 'resolved')->count(),
            'posted_attempts' => (clone $base)->where('status', 'posted')->count(),
            'posted_after_retry' => (clone $base)->where('status', 'posted')->where('attempt_count', '>', 1)->count(),
            'unsupported_failed_postings' => $failedAttempts->filter(fn (AccountingPostingAttempt $attempt) => ! $this->handlers->supports($attempt))->count(),
            'oldest_unresolved_failure' => $failedAttempts->min('last_attempted_at')?->toDateTimeString(),
            'material_unresolved_failures' => $material->count(),
            'material_unresolved_amount' => round((float) $material->sum(), 2),
            'unposted_eligible_source_records' => null,
            'unreconciled_control_accounts' => null,
            'open_bank_reconciliations' => null,
            'unmapped_cash_flow_activity' => null,
            'latest_reconciliation_by_domain' => $latestReconciliations->map(fn ($run) => $run ? [
                'id' => $run->id,
                'status' => $run->status,
                'availability' => $run->availability(),
                'difference_amount' => (float) $run->difference_amount,
                'completed_at' => $run->completed_at?->toDateTimeString(),
            ] : null)->all(),
            'unapproved_reconciliation_runs' => $reconciliationRuns->where('status', 'completed')->count(),
            'domains_with_unresolved_differences' => $latestReconciliations
                ->filter(fn ($run) => $run?->hasUnresolvedDifferences())
                ->keys()
                ->values()
                ->all(),
            'domains_not_run_for_period' => $latestReconciliations->filter(fn ($run) => ! $run)->keys()->values()->all(),
            'reconciliation_failed_posting_links' => AccountingReconciliationResolution::query()
                ->whereIn('accounting_reconciliation_run_id', $runIds)
                ->whereNotNull('linked_posting_attempt_id')
                ->count(),
            'manual_control_account_journals' => AccountingReconciliationItem::query()
                ->whereIn('accounting_reconciliation_run_id', $runIds)
                ->where('classification', 'manual_journal')
                ->count(),
            'failed_by_source_module' => (clone $base)
                ->where('status', 'failed')
                ->selectRaw('source_module, COUNT(*) total')
                ->groupBy('source_module')
                ->orderBy('source_module')
                ->pluck('total', 'source_module')
                ->map(fn ($value) => (int) $value)
                ->all(),
            'waived_by_source_module' => (clone $base)
                ->where('status', 'waived')
                ->selectRaw('source_module, COUNT(*) total')
                ->groupBy('source_module')
                ->orderBy('source_module')
                ->pluck('total', 'source_module')
                ->map(fn ($value) => (int) $value)
                ->all(),
            'resolved_by_source_module' => (clone $base)
                ->where('status', 'resolved')
                ->selectRaw('source_module, COUNT(*) total')
                ->groupBy('source_module')
                ->orderBy('source_module')
                ->pluck('total', 'source_module')
                ->map(fn ($value) => (int) $value)
                ->all(),
            'receivable_exceptions' => [
                'large_overdue_receivables' => InvoiceReceivable::open()
                    ->where('balance', '>=', 10000)
                    ->whereDate('due_date', '<', $to)
                    ->count(),
                'unassigned_overdue_receivables' => InvoiceReceivable::open()
                    ->whereDate('due_date', '<', $to)
                    ->whereDoesntHave('receivableCaseItems.receivableCase', fn ($query) => $query->active()->whereNotNull('assigned_to'))
                    ->count(),
                'unresolved_receivable_disputes' => ReceivableDispute::whereIn('status', ['open', 'under_review', 'credit_note_recommended', 'writeoff_recommended'])->count(),
                'approved_writeoffs_not_converted' => ReceivableWriteoffRecommendation::where('status', 'approved')->whereNull('linked_writeoff_id')->count(),
                'approved_creditnotes_not_converted' => ReceivableCreditnoteRecommendation::where('status', 'approved')->whereNull('linked_credit_note_id')->count(),
                'broken_payment_promises' => ReceivablePromise::where('status', 'broken')->count(),
                'claims_receivables_over_threshold' => InvoiceReceivable::open()->whereNotNull('claim_id')->where('balance', '>=', 10000)->count(),
                'sponsor_corporate_over_threshold' => InvoiceReceivable::open()->whereIn('payer_type', ['sponsor', 'corporate'])->where('balance', '>=', 10000)->count(),
                'active_receivable_cases' => ReceivableCase::active()->count(),
            ],
        ];
        $summary['ready'] = $summary['unresolved_failed_postings'] === 0;

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'CLOSE_READINESS_CHECKED', [
            'causer' => $actor,
            'metadata' => $summary,
        ], description: 'Accounting close readiness checked');
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'FAILED_POSTING_CLOSE_READINESS_VIEWED', [
            'causer' => $actor,
            'metadata' => $summary,
        ], description: 'Failed posting close readiness viewed');
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'SUBLEDGER_RECONCILIATION_CLOSE_READINESS_VIEWED', [
            'causer' => $actor,
            'metadata' => [
                'from' => $summary['from'],
                'to' => $summary['to'],
                'unapproved_runs' => $summary['unapproved_reconciliation_runs'],
                'domains_with_unresolved_differences' => $summary['domains_with_unresolved_differences'],
                'domains_not_run_for_period' => $summary['domains_not_run_for_period'],
                'manual_control_account_journals' => $summary['manual_control_account_journals'],
            ],
        ], description: 'Subledger reconciliation close readiness viewed');

        return $summary;
    }
}
