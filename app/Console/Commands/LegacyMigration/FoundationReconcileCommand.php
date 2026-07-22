<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeDryRunEvaluator;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeRecorderEvidenceProvider;
use App\Services\LegacyMigration\Foundation\Reporting\AggregateDryRunReportBuilder;
use App\Services\LegacyMigration\Foundation\Validation\Phase2FPolicyConfigurationFactory;
use Illuminate\Console\Command;

final class FoundationReconcileCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-reconcile';

    protected $description = 'Evaluate aggregate foundation reconciliation measurements without writing data';

    public function handle(): int
    {
        $configuration = (array) config('legacy-migration');
        $evaluatorConfiguration = is_array($configuration['evaluator'] ?? null) ? $configuration['evaluator'] : [];
        if (($evaluatorConfiguration['authoritative_recorders_bound'] ?? false) !== true
            || ! app()->bound(AuthoritativeRecorderEvidenceProvider::class)) {
            $this->components->error('Blocked: verified authoritative recorder adapters are not bound.');

            return self::FAILURE;
        }

        try {
            $policies = app(Phase2FPolicyConfigurationFactory::class);
            $evaluator = app(AuthoritativeDryRunEvaluator::class);
            $bundle = $policies->load(base_path(), (array) ($configuration['phase2f_policy'] ?? []));
            $evidence = app(AuthoritativeRecorderEvidenceProvider::class)->capture();
            $result = $evaluator->evaluateRecorded($bundle, $bundle->bundleHash, $evidence);
            $this->line(json_encode((new AggregateDryRunReportBuilder)->renderAuthoritative($result), JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            $this->components->error('Blocked: authoritative reconciliation evidence is incomplete or invalid.');

            return self::FAILURE;
        }

        return $result->verdict === 'DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED' ? self::SUCCESS : self::FAILURE;
    }
}
