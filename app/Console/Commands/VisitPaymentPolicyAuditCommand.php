<?php

namespace App\Console\Commands;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Read-only diagnostic audit of visit-payment-policy records (Payment Timing
 * Policy Phase 6). Detects anomalies but performs NO writes, NO repair, NO
 * activity logs, and emits no patient-sensitive free text. Findings never fail
 * the exit code — only a technical command failure does.
 */
class VisitPaymentPolicyAuditCommand extends Command
{
    protected $signature = 'billing:visit-payment-policy-audit
        {--visit= : Only this visit id}
        {--visit-type= : Filter by visit type}
        {--missing : Only report visits missing a record}
        {--stale : Only report stale snapshots}
        {--finance-review : Only records requiring finance review}
        {--problems-only : Only rows with findings}
        {--limit=2000 : Maximum records to scan}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit visit-payment-policy records for anomalies (read-only; no writes).';

    public function handle(VisitPaymentPolicyMaterializationService $service): int
    {
        try {
            $limit = max(1, min((int) $this->option('limit'), 50000));
            $findings = [];

            // Visits missing a record.
            $missingQuery = Visit::query()->whereDoesntHave('paymentPolicy');
            if ($v = $this->option('visit')) {
                $missingQuery->where('id', $v);
            }
            if ($t = $this->option('visit-type')) {
                $missingQuery->where('visit_type', $t);
            }
            $missing = (clone $missingQuery)->count();
            if ($this->option('missing')) {
                return $this->render(['missing_records' => $missing, 'findings' => []]);
            }

            $query = VisitPaymentPolicy::query()->with('visit.patient');
            if ($v = $this->option('visit')) {
                $query->where('visit_id', $v);
            }
            if ($t = $this->option('visit-type')) {
                $query->where('visit_type_snapshot', $t);
            }
            if ($this->option('finance-review')) {
                $query->requiringFinanceReview();
            }

            $validSources = array_map(fn ($c) => $c->value, VisitPaymentPolicySource::cases());
            $validPolicies = array_map(fn ($c) => $c->value, VisitPaymentTimingPolicy::cases());

            $query->orderBy('id')->limit($limit)->chunkById(200, function ($policies) use ($service, &$findings, $validSources, $validPolicies): void {
                foreach ($policies as $policy) {
                    $problems = [];
                    $resolved = $policy->getRawOriginal('resolved_policy');
                    $recommended = $policy->getRawOriginal('recommended_policy');
                    $recSource = $policy->getRawOriginal('recommendation_source');
                    $resSource = $policy->getRawOriginal('resolution_source');

                    if ($resolved === VisitPaymentTimingPolicy::INHERIT->value) {
                        $problems[] = 'resolved_policy_inherit';
                    }
                    if (! in_array($resSource, $validSources, true)) {
                        $problems[] = 'unknown_resolution_source';
                    }
                    if ($recommended !== null && $recSource === null) {
                        $problems[] = 'recommendation_without_source';
                    }
                    if ($recommended === null && $recSource !== null && ! $policy->requires_finance_review) {
                        $problems[] = 'source_without_recommendation';
                    }
                    // A resolved policy sourced from patient_risk would mean the
                    // recommendation leaked into the baseline — must never happen.
                    if ($resSource === VisitPaymentPolicySource::PATIENT_RISK->value) {
                        $problems[] = 'recommendation_treated_as_resolved';
                    }
                    // Restrictive snapshot but no recommendation and no review flag.
                    $level = $policy->getRawOriginal('patient_risk_level_snapshot');
                    if (in_array($level, [PatientFinancialRiskLevel::HIGH_RISK->value, PatientFinancialRiskLevel::BLOCKED_CREDIT->value], true)
                        && $recommended === null && ! $policy->requires_finance_review) {
                        $problems[] = 'restrictive_snapshot_without_recommendation';
                    }
                    if (blank($policy->resolution_version)) {
                        $problems[] = 'missing_resolution_version';
                    }
                    if ($policy->materialized_at === null || $policy->materialized_at->isFuture()) {
                        $problems[] = 'invalid_materialized_at';
                    }
                    if ($recommended !== null && ! in_array($recommended, $validPolicies, true)) {
                        $problems[] = 'invalid_recommended_policy';
                    }
                    if ($service->snapshotIsStale($policy)) {
                        $problems[] = 'stale_risk_snapshot';
                    }

                    if ($this->option('stale') && ! in_array('stale_risk_snapshot', $problems, true)) {
                        continue;
                    }
                    if ($problems !== []) {
                        $findings[$policy->id] = ['visit_id' => $policy->visit_id, 'findings' => $problems];
                    }
                }
            }, 'id');

            return $this->render(['missing_records' => $missing, 'findings' => $findings]);
        } catch (Throwable $e) {
            $this->error('Visit payment policy audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array{missing_records:int, findings:array<int, array{visit_id:int, findings:array<int,string>}>}  $data
     */
    private function render(array $data): int
    {
        $findings = $data['findings'];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'missing_records' => $data['missing_records'],
                'records_with_findings' => count($findings),
                'total_findings' => collect($findings)->pluck('findings')->flatten()->count(),
                'findings' => collect($findings)->map(fn ($f, $id) => array_merge(['policy_id' => $id], $f))->values(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Visit payment policy audit (read-only)');
        $this->info("Missing records: {$data['missing_records']} · Records with findings: ".count($findings));
        if ($findings !== []) {
            $this->table(['Policy', 'Visit', 'Findings'], collect($findings)->map(fn ($f, $id) => [
                $id, $f['visit_id'], implode(', ', $f['findings']),
            ])->all());
        } elseif (! $this->option('problems-only')) {
            $this->line('No anomalies detected for the selected filters.');
        }
        $this->line('Findings are advisory; no data was modified.');

        return self::SUCCESS;
    }
}
