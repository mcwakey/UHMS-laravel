<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Reconciliation\ReconciliationVerdictService;
use Illuminate\Console\Command;

final class FoundationReconcileCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-reconcile
        {--input= : Path to an aggregate-only JSON measurement file}
        {--required=* : Mandatory measurement identifier}';

    protected $description = 'Evaluate aggregate foundation reconciliation measurements without writing data';

    public function handle(ReconciliationVerdictService $service): int
    {
        unset($service);
        $this->components->error('Blocked: the authoritative contract-bundle reconciliation evaluator is not implemented in Phase 3.');

        return self::FAILURE;
    }
}
