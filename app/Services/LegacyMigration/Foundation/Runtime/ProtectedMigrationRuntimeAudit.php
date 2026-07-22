<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use App\Models\LegacyMigration\MigrationRun;
use App\Models\LegacyMigration\Snapshot;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\MigrationAuditRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/** Protected, append-only semantic audit for runtime isolation. */
final class ProtectedMigrationRuntimeAudit implements MigrationRuntimeAudit
{
    private int $sequence = 0;

    /** @param array<string,mixed> $configuration */
    public function __construct(
        private readonly MigrationAuditRepository $audit,
        private readonly ProtectedRecordSecurityRepository $security,
        private readonly HmacTokenService $tokens,
        private readonly CanonicalTypedMessageEncoder $encoder,
        private readonly array $configuration,
        private readonly string $environment,
        private readonly ?string $connection = null,
    ) {}

    public function start(MigrationRuntimeRequest $request): MigrationRuntimeAuditSession
    {
        if (($this->configuration['runtime_audit']['enabled'] ?? false) !== true) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_DISABLED', 'The protected migration runtime-audit sink is disabled.');
        }
        $runToken = $this->digest($request->runToken());
        $targetToken = $this->digest($request->targetSnapshotId());
        $runId = (int) $this->db()->table('legacy_migration_runs')->where('run_token', $runToken)->value('id');
        $targetId = (int) $this->db()->table('legacy_migration_snapshots')->where('snapshot_token', $targetToken)->where('snapshot_kind', 'target')->value('id');
        if ($runId < 1 || $targetId < 1) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_COORDINATE_MISSING', 'The protected runtime-audit coordinate is missing.');
        }
        $access = trim((string) ($this->configuration['runtime_audit']['access_classification'] ?? ''));
        $retention = trim((string) ($this->configuration['runtime_audit']['retention_classification'] ?? ''));
        $authority = trim((string) ($this->configuration['runtime_audit']['authority_reference'] ?? ''));
        $run = $this->security->readProjection(
            $this->context(['read'], ['migration_run'], $authority, $access, $retention, $runId, null, ['contract_bundle_id']),
            MigrationRun::class,
            $runId,
            ['run_token' => $runToken],
        );
        $this->security->readProjection(
            $this->context(['read'], ['target_snapshot'], $authority, $access, $retention, $runId, $targetId, ['run_id', 'snapshot_kind']),
            Snapshot::class,
            $targetId,
            ['run_id' => $runId, 'snapshot_kind' => 'target'],
        );
        if ((int) ($run->fields['contract_bundle_id'] ?? 0) < 1) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_LINEAGE_INVALID', 'The protected runtime-audit lineage is incomplete.');
        }
        $session = new MigrationRuntimeAuditSession(
            $runId,
            $targetId,
            $runToken,
            $targetToken,
            (string) ($this->configuration['versions']['contract_bundle'] ?? ''),
            $access,
            $retention,
        );
        $this->record($session, 'ACTIVATION_REQUESTED', ['mode' => $request->mode()->value]);

        return $session;
    }

    public function record(MigrationRuntimeAuditSession $session, string $event, array $facts = []): void
    {
        if (preg_match('/\A[A-Z][A-Z0-9_]{2,80}\z/D', $event) !== 1) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_EVENT_INVALID', 'The runtime-audit event is invalid.');
        }
        $authority = (string) ($this->configuration['runtime_audit']['authority_reference'] ?? '');
        $token = $this->tokens->tokenize(new TokenDomain('migration_audit'), $this->encoder->encode([
            TypedValue::string('runtime-isolation-audit/v1'),
            TypedValue::integer($session->runId),
            TypedValue::integer($session->targetSnapshotId),
            TypedValue::string($event),
            TypedValue::integer(++$this->sequence),
            TypedValue::string(bin2hex(random_bytes(16))),
        ]));
        $checksum = $this->tokens->tokenize(new TokenDomain('artifact_integrity'), $this->encoder->encode([
            TypedValue::string($token->encode()), TypedValue::string($event), TypedValue::string(json_encode($facts, JSON_THROW_ON_ERROR)),
        ]))->lookupDigest();
        $this->audit->appendProtected(
            $this->context(['write'], ['migration_audit'], $authority, $session->accessClassification, $session->retentionClassification, $session->runId),
            [
                'run_id' => $session->runId,
                'domain' => 'migration_audit',
                'event_type' => $event,
                'contract_version' => $session->contractVersion,
                'transformation_version' => 'runtime-isolation/1',
                'canonicalization_version' => $token->canonicalizationVersion(),
                'hmac_key_version' => $token->keyVersion(),
                'state' => 'recorded',
                'encrypted_event_payload' => ['target_snapshot_token' => $session->targetSnapshotToken, 'facts' => $facts],
                'integrity_checksum' => $checksum,
                'access_classification' => $session->accessClassification,
                'retention_classification' => $session->retentionClassification,
                'occurred_at' => now(),
            ],
            ['event_token' => ['encoded_token' => $token->encode(), 'domain' => 'migration_audit']],
        );
    }

    /** @param list<string> $operations @param list<string> $domains @param list<string> $fields */
    private function context(array $operations, array $domains, string $authority, string $access, string $retention, int $run, ?int $target = null, array $fields = []): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext('foundation_runtime_audit', $operations, $domains, $this->environment, $run, null, $target, $access, $retention, $authority, $fields);
    }

    private function digest(string $value): string
    {
        return strtolower(str_starts_with(strtolower($value), 'sha256:') ? substr($value, 7) : $value);
    }

    private function db(): ConnectionInterface
    {
        return DB::connection($this->connection ?? (string) config('database.default'));
    }
}
