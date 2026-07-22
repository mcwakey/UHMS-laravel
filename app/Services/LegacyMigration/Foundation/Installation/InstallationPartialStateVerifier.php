<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class InstallationPartialStateVerifier
{
    /** @param list<MariaDbFoundationDdlManifestContract> $manifests */
    public function __construct(
        private ConnectionInterface $connection,
        private array $manifests,
        private IdentityReferenceHasher $hasher,
    ) {}

    public function verify(PhysicalServerIdentityVerification $identity, SchemaObservation $baseSchema): InstallationIdentityContract
    {
        if (! hash_equals($identity->structuralIdentityReference, $baseSchema->fingerprint)) {
            throw new RuntimeException('Installation base schema identity is not approved.');
        }
        $expectations = [];
        $observations = [];
        $manifestHashes = [];
        foreach ($this->manifests as $manifest) {
            $manifestHashes[$manifest->version()] = $manifest->payloadHash();
            array_push($expectations, ...$manifest->expectations());
            array_push($observations, ...(new MariaDbFoundationDdlObserver(
                $this->connection,
                $manifest,
                new DdlDefinitionNormalizer,
            ))->observe());
        }
        array_push($observations, ...$this->unexpectedReservedObjects($expectations));
        $results = (new FoundationDdlInspector)->inspect($expectations, $observations);
        $state = [];
        foreach ($results as $result) {
            $state[$result->expected->coordinate()] = match ($result->state) {
                DdlInspectionState::Matching => ['state' => 'matching', 'hash' => $result->observedDefinitionHash],
                DdlInspectionState::Absent, DdlInspectionState::RepairableMetadataGap => ['state' => 'absent', 'hash' => null],
                default => throw new RuntimeException('Installation partial state is drifted or conflicting.'),
            };
        }
        ksort($state, SORT_STRING);
        $partialStateHash = $this->hasher->reference(
            'installation_partial_manifest_state',
            json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        return InstallationIdentityContract::issue(
            $identity->approvedIdentityReference,
            $identity->connectionInstanceReference,
            $baseSchema->fingerprint,
            $manifestHashes,
            $partialStateHash,
            $this->hasher,
        );
    }

    /**
     * @param  list<DdlObjectExpectation>  $expectations
     * @return list<DdlObjectObservation>
     */
    private function unexpectedReservedObjects(array $expectations): array
    {
        $expected = array_fill_keys(array_map(
            static fn (DdlObjectExpectation $item): string => $item->coordinate(),
            $expectations,
        ), true);
        $unexpected = [];
        $tables = $this->connection->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $this->connection->getDatabaseName())
            ->where('TABLE_NAME', 'like', 'legacy\\_migration\\_%')
            ->pluck('TABLE_NAME');
        foreach ($tables as $table) {
            $coordinate = DdlObjectType::Table->value.':'.$table;
            if (! isset($expected[$coordinate])) {
                $unexpected[] = new DdlObjectObservation(DdlObjectType::Table, (string) $table, hash('sha256', 'unexpected-reserved-table'));
            }
        }
        $triggers = $this->connection->table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', $this->connection->getDatabaseName())
            ->where(function ($query): void {
                $query->where('TRIGGER_NAME', 'like', 'lm\\_%')
                    ->orWhere('EVENT_OBJECT_TABLE', 'like', 'legacy\\_migration\\_%');
            })->pluck('TRIGGER_NAME');
        foreach ($triggers as $trigger) {
            $coordinate = DdlObjectType::Trigger->value.':'.$trigger;
            if (! isset($expected[$coordinate])) {
                $unexpected[] = new DdlObjectObservation(DdlObjectType::Trigger, (string) $trigger, hash('sha256', 'unexpected-reserved-trigger'));
            }
        }

        return $unexpected;
    }
}
