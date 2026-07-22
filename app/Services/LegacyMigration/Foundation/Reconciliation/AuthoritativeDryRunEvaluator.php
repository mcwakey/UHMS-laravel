<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;
use InvalidArgumentException;

final class AuthoritativeDryRunEvaluator
{
    public function __construct(private readonly MeasurementIntegrityService $integrity) {}

    /** Caller-declared measurements are prohibited; use evaluateRecorded(). */
    public function evaluate(VerifiedPolicyBundle $bundle, string $pinnedBundleHash, array $measurements): never
    {
        unset($bundle, $pinnedBundleHash, $measurements);
        throw new InvalidArgumentException('Caller-declared reconciliation measurements are prohibited.');
    }

    public function evaluateRecorded(VerifiedPolicyBundle $bundle, string $pinnedBundleHash, AuthoritativeRecorderEvidence $evidence): AuthoritativeDryRunResult
    {
        $bundle->assertPinned($pinnedBundleHash);
        $plan = Phase2FMeasurementPlan::fromBundle($bundle);
        $measurements = $this->measurementsFromEvidence($plan, $evidence);
        $byId = [];
        $coordinates = null;
        $failed = [];
        foreach ($measurements as $measurement) {
            if (! $measurement instanceof AuthoritativeMeasurement
                || isset($byId[$measurement->measurementId])
                || ! isset($plan->measurements[$measurement->measurementId])
                || ! hash_equals($plan->measurements[$measurement->measurementId], $measurement->contractId)
                || ! $this->integrity->verify($measurement)) {
                throw new InvalidArgumentException('Authoritative reconciliation evidence failed closed.');
            }
            foreach ([$measurement->queryOrCounterIdentity, $measurement->runToken, $measurement->sourceSnapshotId, $measurement->targetSnapshotId] as $digest) {
                if (preg_match('/\A[a-f0-9]{64}\z/', $digest) !== 1) {
                    throw new InvalidArgumentException('Authoritative reconciliation coordinates are invalid.');
                }
            }
            $current = [$measurement->runToken, $measurement->sourceSnapshotId, $measurement->targetSnapshotId];
            if ($coordinates !== null && $coordinates !== $current) {
                throw new InvalidArgumentException('Authoritative reconciliation coordinates conflict.');
            }
            $coordinates = $current;
            if (! $this->isZero($measurement->difference) || ! $this->isZero($measurement->tolerance)) {
                $failed[] = $measurement->measurementId;
            }
            $byId[$measurement->measurementId] = [
                'contract_id' => $measurement->contractId,
                'measurement_source' => $measurement->source->value,
                'expected_equation' => $measurement->expectedEquation,
                'observed_value' => $measurement->observedValue,
                'difference' => $measurement->difference,
                'tolerance' => $measurement->tolerance,
                'classification' => $measurement->classification,
                'verdict' => $this->isZero($measurement->difference) ? 'passed' : 'failed',
            ];
        }
        $missing = array_values(array_diff(array_keys($plan->measurements), array_keys($byId)));
        foreach ($missing as $id) {
            $byId[$id] = [
                'contract_id' => $plan->measurements[$id],
                'measurement_source' => 'missing',
                'expected_equation' => 'mandatory',
                'observed_value' => null,
                'difference' => null,
                'tolerance' => '0',
                'classification' => 'blocked_not_measured',
                'verdict' => 'blocked_not_measured',
            ];
        }
        ksort($byId, SORT_STRING);
        sort($missing, SORT_STRING);
        sort($failed, SORT_STRING);
        $verdict = $failed !== [] ? 'FAIL_CLOSED' : ($missing !== [] ? 'BLOCKED_PREREQUISITE' : 'DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED');

        return new AuthoritativeDryRunResult(
            $verdict,
            $plan->bundleHash,
            $coordinates[0] ?? str_repeat('0', 64),
            $coordinates[1] ?? str_repeat('0', 64),
            $coordinates[2] ?? str_repeat('0', 64),
            $byId,
            $missing,
            $failed,
        );
    }

    /** @return list<AuthoritativeMeasurement> */
    private function measurementsFromEvidence(Phase2FMeasurementPlan $plan, AuthoritativeRecorderEvidence $evidence): array
    {
        return (new RecorderDerivedMeasurementAdapter($this->integrity))->adapt($plan, $evidence);
    }

    private function isZero(string $value): bool
    {
        return preg_match('/\A[+-]?0+(?:\.0+)?\z/', trim($value)) === 1;
    }
}
