<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Recovery\CrashBoundary;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryAuditService;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryEvidence;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryObservation;
use Illuminate\Console\Command;
use JsonException;

final class FoundationRecoveryAuditCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-recovery-audit
        {--input= : Path to a protected aggregate recovery-observation JSON file}';

    protected $description = 'Classify all migration recovery boundaries without writing any data';

    public function handle(RecoveryAuditService $audit): int
    {
        try {
            $observations = $this->readObservations($this->option('input'));
            $boundaries = array_map(static fn (RecoveryObservation $item): string => $item->boundary->value, $observations);
            if (count($boundaries) !== count(CrashBoundary::cases())
                || count(array_unique($boundaries)) !== count(CrashBoundary::cases())) {
                throw new JsonException('Every crash boundary must appear exactly once.');
            }

            $report = $audit->audit($observations);
            $report['complete_boundary_coverage'] = true;
            $report['commit_authorized'] = false;
            $this->line(json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return $report['operator_review_count'] === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable) {
            $this->components->error('Blocked: recovery input is missing, incomplete, conflicting, or unsafe. No writes were performed.');

            return self::FAILURE;
        }
    }

    /** @return list<RecoveryObservation> */
    private function readObservations(mixed $path): array
    {
        if (! is_string($path) || $path === '') {
            throw new JsonException('Input path is required.');
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new JsonException('Input cannot be read.');
        }
        $decoded = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || array_keys($decoded) !== ['observations'] || ! is_array($decoded['observations'])) {
            throw new JsonException('Only aggregate observations are accepted.');
        }

        $fields = [
            'coordinates_match',
            'lineage_compatible',
            'unexpected_durable_facts',
            'durable_unit_committed',
            'durable_facts_complete',
            'checkpoint_present',
            'mandatory_reconciliation_present',
            'reconciliation_passed',
            'transaction_rolled_back',
            'allocation_consumption_explained',
        ];
        $allowed = array_merge(['boundary'], $fields);
        $observations = [];
        foreach ($decoded['observations'] as $row) {
            if (! is_array($row) || array_diff(array_keys($row), $allowed) !== [] || array_diff($allowed, array_keys($row)) !== []) {
                throw new JsonException('Observation shape is invalid.');
            }
            foreach ($fields as $field) {
                if (! is_bool($row[$field])) {
                    throw new JsonException('Recovery evidence must be aggregate booleans.');
                }
            }

            $observations[] = new RecoveryObservation(
                CrashBoundary::from((string) $row['boundary']),
                new RecoveryEvidence(
                    $row['coordinates_match'],
                    $row['lineage_compatible'],
                    $row['unexpected_durable_facts'],
                    $row['durable_unit_committed'],
                    $row['durable_facts_complete'],
                    $row['checkpoint_present'],
                    $row['mandatory_reconciliation_present'],
                    $row['reconciliation_passed'],
                    $row['transaction_rolled_back'],
                    $row['allocation_consumption_explained'],
                ),
            );
        }

        return $observations;
    }
}
