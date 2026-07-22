<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\LaravelMetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\LaravelPhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalTargetIdentityContract;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final class FoundationInstallationCompositionService
{
    private readonly \Closure $externalSecretResolver;

    /** @param callable(string): ?string $externalSecretResolver */
    public function __construct(
        private readonly ConnectionInterface $connection,
        callable $externalSecretResolver,
    ) {
        $this->externalSecretResolver = \Closure::fromCallable($externalSecretResolver);
    }

    /** @param array<string, mixed> $configuration */
    public function install(array $configuration, string $environment): FoundationInstallationResult
    {
        $foundation = $configuration['foundation'] ?? null;
        if (! is_array($foundation)
            || ($foundation['schema_writes_enabled'] ?? false) !== true
            || ($foundation['installation_journal_enabled'] ?? false) !== true
            || ($foundation['partial_repair_authorized'] ?? false) !== true
            || ($foundation['installation_manifest_version'] ?? null) !== 'phase-3b/foundation-ddl/1') {
            throw new RuntimeException('Foundation installation and repair remain disabled.');
        }
        $guardConfiguration = GuardConfiguration::fromArray($configuration, $environment);
        (new EnvironmentGuard)->assertRuntime($environment, $guardConfiguration);

        $physical = $configuration['target']['physical_identity'] ?? null;
        if (! is_array($physical)) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract is incomplete.');
        }
        $contract = PhysicalTargetIdentityContract::fromArray($physical);
        $reference = $physical['identity_key_reference'] ?? null;
        $keyId = $physical['identity_key_id'] ?? null;
        $keyVersion = $physical['identity_key_version'] ?? null;
        if (! is_string($reference) || $reference === '' || ! is_string($keyId) || ! is_string($keyVersion)) {
            throw new RuntimeException('External physical-identity key reference is missing.');
        }
        $key = ($this->externalSecretResolver)($reference);
        if (! is_string($key) || strlen($key) < 32) {
            throw new RuntimeException('External physical-identity key material is unavailable.');
        }
        $hasher = new HmacIdentityReferenceHasher($environment, $keyId, $keyVersion, $key);
        unset($key);

        $metadata = new LaravelMetadataConnection($this->connection->getName(), $this->connection);
        $metadata->beginReadOnlySnapshot();
        try {
            $structural = (new SchemaFingerprintService)->inspectTargetInstallationBase($metadata, $contract->database);
        } finally {
            $metadata->rollbackReadOnlySnapshot();
        }
        $physicalObserver = new LaravelPhysicalServerIdentityObserver($this->connection->getName(), $this->connection, $hasher);
        $identity = (new PhysicalServerIdentityVerifier($hasher))->verify(
            $contract,
            $physicalObserver,
            $structural,
            $contract->foundationSchemaCoordinate,
            $contract->configurationFingerprint,
        );
        $gate = new IdentityBoundDdlGate($hasher);
        $base = new MariaDbFoundationDdlManifest;
        $extension = new MariaDbFoundationDdlExtensionManifest;
        $installationIdentity = (new InstallationPartialStateVerifier(
            $this->connection,
            [$base, $extension],
            $hasher,
        ))->verify($identity, $structural);
        $auditReference = $hasher->reference('ddl_installation_audit', implode('|', [
            $contract->ownerApprovalReference,
            $installationIdentity->contractReference,
            $base->payloadHash(),
            $extension->payloadHash(),
        ]));
        $session = InstallationSessionCapability::issue(
            $installationIdentity,
            $auditReference,
            [$base->version(), $extension->version()],
            $hasher,
        );
        $journal = new LaravelFoundationInstallationJournal($this->connection);
        $baseObserver = new MariaDbFoundationDdlObserver($this->connection, $base, new DdlDefinitionNormalizer);
        $baseExecutor = new MariaDbFoundationDdlOperationExecutor($this->connection, $base, $hasher);
        (new FoundationInstallationJournalBootstrapper(
            $base, new FoundationDdlInspector, $baseObserver, $baseExecutor, $gate,
        ))->ensure($identity, $session, $journal, $auditReference);

        $plans = [(new SafeFoundationDdlInstaller(
            new FoundationDdlInspector, new DdlRecoveryPlanner, $baseExecutor, $journal, $gate,
        ))->install($identity, $session, $base->version(), $base->expectations(), fn (): array => $baseObserver->observe(), $auditReference)];
        $extensionObserver = new MariaDbFoundationDdlObserver($this->connection, $extension, new DdlDefinitionNormalizer);
        $plans[] = (new SafeFoundationDdlInstaller(
            new FoundationDdlInspector,
            new DdlRecoveryPlanner,
            new MariaDbFoundationDdlOperationExecutor($this->connection, $extension, $hasher),
            $journal,
            $gate,
        ))->install($identity, $session, $extension->version(), $extension->expectations(), fn (): array => $extensionObserver->observe(), $auditReference);

        return new FoundationInstallationResult(
            $identity->approvedIdentityReference,
            [$base->payloadHash(), $extension->payloadHash()],
            array_sum(array_map(static fn (DdlRecoveryPlan $plan): int => count($plan->operations), $plans)),
            in_array(true, array_map(static fn (DdlRecoveryPlan $plan): bool => $plan->partialPriorInstallation, $plans), true),
        );
    }
}
