<?php

namespace App\Console\Commands;

use App\Models\VisitPaymentPolicy;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Illuminate\Console\Command;

/**
 * Explicitly refreshes existing visit-payment-policy records (Payment Timing
 * Policy Phase 6). This is the ONLY controlled way an existing snapshot changes
 * — Phase 6 never auto-refreshes. Dry-run by default; each material refresh
 * appends one history entry + one activity log. Changes no visit or payment gate.
 */
class VisitPaymentPolicyRefreshCommand extends Command
{
    protected $signature = 'billing:visit-payment-policy-refresh
        {--visit= : Only this visit id}
        {--active-only : Only active (non-closed) visits}
        {--stale-only : Only records whose risk snapshot is stale}
        {--limit=1000 : Maximum records to process}
        {--dry-run : Report only (default)}
        {--commit : Perform writes}
        {--reason= : Machine reason code stored in history}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Explicitly refresh visit-payment-policy records (dry-run by default; appends history on change).';

    public function handle(VisitPaymentPolicyMaterializationService $service): int
    {
        $commit = (bool) $this->option('commit');
        $limit = max(1, min((int) $this->option('limit'), 20000));
        $reason = $this->option('reason');

        $query = VisitPaymentPolicy::query()->with('visit.patient');
        if ($visitId = $this->option('visit')) {
            $query->where('visit_id', $visitId);
        }
        if ($this->option('active-only')) {
            $query->whereHas('visit', fn ($v) => $v->active());
        }

        $scanned = 0;
        $stale = 0;
        $refreshed = 0;

        $query->orderBy('id')->limit($limit)->chunkById(200, function ($policies) use ($service, $commit, $reason, &$scanned, &$stale, &$refreshed): void {
            foreach ($policies as $policy) {
                if ($policy->visit === null) {
                    continue;
                }
                $isStale = $service->snapshotIsStale($policy);
                if ($this->option('stale-only') && ! $isStale) {
                    continue;
                }
                $scanned++;
                if ($isStale) {
                    $stale++;
                }
                if (! $commit) {
                    continue;
                }
                $before = $policy->updated_at;
                $updated = $service->refresh($policy->visit, actor: null, reasonCode: $reason);
                if ($updated->wasChanged() || $updated->last_refreshed_at?->gt($before ?? now()->subCentury())) {
                    $refreshed++;
                }
            }
        }, 'id');

        $result = [
            'mode' => $commit ? 'commit' : 'dry-run',
            'candidates' => $scanned,
            'stale' => $stale,
            'refreshed' => $refreshed,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info("Refresh ({$result['mode']}) — candidates: {$scanned}, stale: {$stale}, refreshed: {$refreshed}.");
        if (! $commit) {
            $this->line('Dry-run only; re-run with --commit to write changes.');
        }

        return self::SUCCESS;
    }
}
