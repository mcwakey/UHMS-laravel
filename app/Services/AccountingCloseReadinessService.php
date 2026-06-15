<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\AccountingPostingAttempt;
use App\Models\User;
use Carbon\Carbon;

class AccountingCloseReadinessService
{
    public function summary(Carbon|string $from, Carbon|string $to, ?User $actor = null): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();
        $base = AccountingPostingAttempt::query()
            ->whereBetween('last_attempted_at', [$from, $to]);

        $summary = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'unresolved_failed_postings' => (clone $base)->where('status', 'failed')->count(),
            'waived_postings' => (clone $base)->where('status', 'waived')->count(),
            'posted_attempts' => (clone $base)->where('status', 'posted')->count(),
            'unposted_eligible_source_records' => null,
            'unreconciled_control_accounts' => null,
            'open_bank_reconciliations' => null,
            'unmapped_cash_flow_activity' => null,
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
        ];
        $summary['ready'] = $summary['unresolved_failed_postings'] === 0;

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'CLOSE_READINESS_CHECKED', [
            'causer' => $actor,
            'metadata' => $summary,
        ], description: 'Accounting close readiness checked');

        return $summary;
    }
}
