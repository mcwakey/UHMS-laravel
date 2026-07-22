<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Runtime\SideEffectCounter;
use App\Services\LegacyMigration\Foundation\Snapshot\CanonicalManifestHasher;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotException;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifestIntegrityService;
use Illuminate\Database\ConnectionInterface;

/** One-shot recorder session; baselines are captured internally, never supplied by a caller. */
final class AuthoritativeRecorderSession
{
    private bool $finished = false;

    private ?string $finishedAuthority = null;

    /** @var array<string,mixed>|null */
    private ?array $evidenceMaterial = null;

    private function __construct(
        private readonly SideEffectCounter $counter,
        private readonly array $sideEffectBaseline,
        private readonly array $repositoryBaseline,
        private readonly ConnectionInterface $repository,
        private readonly SnapshotManifestIntegrityService $integrity,
        private readonly ?AuthoritativeAggregateObservationProvider $aggregateProvider,
    ) {}

    public static function begin(
        SideEffectCounter $counter,
        ConnectionInterface $repository,
        SnapshotManifestIntegrityService $integrity,
        ?AuthoritativeAggregateObservationProvider $aggregateProvider = null,
    ): self {
        return new self($counter, $counter->snapshot(), self::repositoryCounts($repository), $repository, $integrity, $aggregateProvider);
    }

    public function finish(SnapshotManifest $source, SnapshotManifest $targetBefore, SnapshotManifest $targetAfter): AuthoritativeRecorderEvidence
    {
        if ($this->finished) {
            throw new \LogicException('Authoritative recorder sessions are one-shot.');
        }
        foreach ([$source, $targetBefore, $targetAfter] as $manifest) {
            if (! $this->integrity->verify($manifest) || $manifest->authority !== 'authoritative_direct_capture') {
                throw new SnapshotException('FOUNDATION_RECORDER_SNAPSHOT_INVALID', 'Authoritative recorder snapshot evidence is invalid.');
            }
        }
        if ($source->kind !== 'coordinated_source' || $targetBefore->kind !== 'target_collision' || $targetAfter->kind !== 'target_collision'
            || ! hash_equals($source->runToken, $targetBefore->runToken)
            || ! hash_equals($source->runToken, $targetAfter->runToken)
            || hash_equals($targetBefore->snapshotId, $targetAfter->snapshotId)
            || strcmp($targetBefore->capturedAtUtc, $targetAfter->capturedAtUtc) > 0) {
            throw new SnapshotException('FOUNDATION_RECORDER_COORDINATE_INVALID', 'Authoritative recorder coordinates conflict.');
        }
        $repositoryAfter = self::repositoryCounts($this->repository);
        $repositoryDelta = [];
        foreach ($repositoryAfter as $table => $count) {
            $repositoryDelta[$table] = $count - ($this->repositoryBaseline[$table] ?? 0);
        }
        $sideEffects = $this->counter->delta($this->sideEffectBaseline);
        $aggregateObservation = $this->aggregateProvider?->capture(
            $source,
            $targetBefore,
            $targetAfter,
            $this->repositoryBaseline,
            $repositoryAfter,
        );
        $observations = [
            'source_read_only' => self::snapshotObservation($source),
            'target_before' => self::snapshotObservation($targetBefore),
            'target_after' => self::snapshotObservation($targetAfter),
            'protected_repository_before' => $this->repositoryBaseline,
            'protected_repository_after' => $repositoryAfter,
            'protected_repository_write_delta' => $repositoryDelta,
            'side_effect_attempts' => $sideEffects,
            'runtime_denied_attempts' => array_sum($sideEffects),
        ];
        if ($aggregateObservation !== null) {
            $observations['aggregate_outcomes'] = $aggregateObservation->toArray();
        }
        $authority = (new CanonicalManifestHasher)->hash([
            'source_seal' => $source->authoritySeal,
            'target_before_seal' => $targetBefore->authoritySeal,
            'target_after_seal' => $targetAfter->authoritySeal,
            'observations' => $observations,
        ]);
        $this->finished = true;
        $this->finishedAuthority = $authority;
        $this->evidenceMaterial = [
            'run_token' => $source->runToken,
            'source_snapshot_id' => $source->snapshotId,
            'target_before_snapshot_id' => $targetBefore->snapshotId,
            'target_after_snapshot_id' => $targetAfter->snapshotId,
            'observations' => $observations,
            'authority_reference' => $authority,
        ];

        return AuthoritativeRecorderEvidence::issueFromSession($this);
    }

    /** @return array{run_token:string,source_snapshot_id:string,target_before_snapshot_id:string,target_after_snapshot_id:string,observations:array<string,mixed>,authority_reference:string} */
    public function releaseEvidenceMaterial(): array
    {
        if (! $this->finished || $this->finishedAuthority === null || $this->evidenceMaterial === null
            || ! hash_equals($this->finishedAuthority, (string) $this->evidenceMaterial['authority_reference'])) {
            throw new \LogicException('Recorder evidence was not issued by this completed session.');
        }

        return $this->evidenceMaterial;
    }

    /** @return array<string,int> */
    private static function repositoryCounts(ConnectionInterface $connection): array
    {
        $tables = [
            'legacy_migration_runs', 'legacy_migration_snapshots', 'legacy_migration_target_collision_snapshots',
            'legacy_migration_crosswalks', 'legacy_migration_remediations', 'legacy_migration_provenance_records',
            'legacy_migration_quarantine_roots', 'legacy_migration_quarantine_exceptions',
            'legacy_migration_reconciliation_results', 'legacy_migration_audit_events',
            'legacy_migration_idempotency_records', 'legacy_migration_atomic_intents',
            'legacy_migration_checkpoints', 'legacy_migration_number_reservations',
            'legacy_migration_compensation_records',
        ];
        $counts = [];
        foreach ($tables as $table) {
            try {
                $counts[$table] = (int) $connection->table($table)->count();
            } catch (\Throwable) {
                throw new \RuntimeException('Authoritative protected-repository recorder is not bound to the complete foundation schema.');
            }
        }

        return $counts;
    }

    /** @return array<string,mixed> */
    private static function snapshotObservation(SnapshotManifest $manifest): array
    {
        return [
            'snapshot_id' => $manifest->snapshotId,
            'captured_at_utc' => $manifest->capturedAtUtc,
            'schema_fingerprint' => $manifest->schemaFingerprint,
            'configuration_fingerprint' => $manifest->configurationFingerprint,
            'contract_bundle_hash' => $manifest->contractBundleHash,
            'query_hashes' => $manifest->queryHashes,
            'protected_set_tokens' => $manifest->protectedSetTokens,
            'set_counts' => $manifest->setCounts,
            'capture_authority' => $manifest->authority,
        ];
    }
}
