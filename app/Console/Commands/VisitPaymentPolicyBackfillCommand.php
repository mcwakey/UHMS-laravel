<?php

namespace App\Console\Commands;

use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Illuminate\Console\Command;

/**
 * Backfills observational visit-payment-policy records for existing visits
 * (Payment Timing Policy Phase 6).
 *
 * Safe by default (dry-run). Creates NO invoices/payments/overrides, changes no
 * visit status, never classifies patients, and is idempotent — it skips visits
 * that already have a record (missing-only) and never overwrites an existing
 * snapshot. History/activity are written only for actually committed creations.
 */
class VisitPaymentPolicyBackfillCommand extends Command
{
    protected $signature = 'billing:visit-payment-policy-backfill
        {--visit= : Only this visit id}
        {--visit-type= : Filter by visit type (outpatient|inpatient|emergency)}
        {--active-only : Only active (non-closed) visits}
        {--missing-only : Only visits without a policy record (default behaviour)}
        {--limit=1000 : Maximum visits to process}
        {--dry-run : Report only (default)}
        {--commit : Perform writes}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Backfill observational visit-payment-policy records for existing visits (dry-run by default).';

    public function handle(VisitPaymentPolicyMaterializationService $service): int
    {
        $commit = (bool) $this->option('commit');
        $limit = max(1, min((int) $this->option('limit'), 20000));

        $query = Visit::query()->whereDoesntHave('paymentPolicy');
        if ($visitId = $this->option('visit')) {
            $query->where('id', $visitId);
        }
        if ($type = $this->option('visit-type')) {
            $query->where('visit_type', $type);
        }
        if ($this->option('active-only')) {
            $query->active();
        }

        $created = 0;
        $scanned = 0;
        $query->orderBy('id')->limit($limit)->chunkById(200, function ($visits) use ($service, $commit, &$created, &$scanned): void {
            foreach ($visits as $visit) {
                $scanned++;
                if (! $commit) {
                    continue;
                }
                // materialize() is idempotent and never overwrites an existing record.
                $service->materialize($visit->loadMissing('patient'));
                $created++;
            }
        });

        $result = [
            'mode' => $commit ? 'commit' : 'dry-run',
            'candidates' => $scanned,
            'created' => $created,
            'existing_total' => VisitPaymentPolicy::query()->count(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info("Backfill ({$result['mode']}) — candidates: {$scanned}, created: {$created}.");
        if (! $commit) {
            $this->line('Dry-run only; re-run with --commit to write records.');
        }

        return self::SUCCESS;
    }
}
