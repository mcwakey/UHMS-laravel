<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityReferenceHasher;
use RuntimeException;

/** Keyed authority for one exactly observed pre-install or partial-install state. */
final readonly class InstallationIdentityContract
{
    public const VERSION = 'phase-3b/installation-identity/1';

    private function __construct(
        public string $basePhysicalIdentityReference,
        public string $connectionInstanceReference,
        public string $baseSchemaFingerprint,
        public array $manifestPayloadHashes,
        public string $partialStateHash,
        public string $contractReference,
        private string $seal,
    ) {}

    /** @param array<string, string> $manifestPayloadHashes */
    public static function issue(
        string $basePhysicalIdentityReference,
        string $connectionInstanceReference,
        string $baseSchemaFingerprint,
        array $manifestPayloadHashes,
        string $partialStateHash,
        IdentityReferenceHasher $hasher,
    ): self {
        ksort($manifestPayloadHashes, SORT_STRING);
        foreach ([$basePhysicalIdentityReference, $connectionInstanceReference, $baseSchemaFingerprint, $partialStateHash, ...array_values($manifestPayloadHashes)] as $digest) {
            if (preg_match('/\A[a-f0-9]{64}\z/D', $digest) !== 1) {
                throw new RuntimeException('Installation identity contract contains an invalid protected coordinate.');
            }
        }
        $payload = self::payload(
            $basePhysicalIdentityReference,
            $connectionInstanceReference,
            $baseSchemaFingerprint,
            $manifestPayloadHashes,
            $partialStateHash,
        );
        $reference = $hasher->reference('installation_identity_contract_reference', $payload);

        return new self(
            $basePhysicalIdentityReference,
            $connectionInstanceReference,
            $baseSchemaFingerprint,
            $manifestPayloadHashes,
            $partialStateHash,
            $reference,
            $hasher->reference('installation_identity_contract_seal', $payload.'|'.$reference),
        );
    }

    public function assertAuthentic(IdentityReferenceHasher $hasher): void
    {
        $payload = self::payload(
            $this->basePhysicalIdentityReference,
            $this->connectionInstanceReference,
            $this->baseSchemaFingerprint,
            $this->manifestPayloadHashes,
            $this->partialStateHash,
        );
        if (! hash_equals($this->contractReference, $hasher->reference('installation_identity_contract_reference', $payload))
            || ! hash_equals($this->seal, $hasher->reference('installation_identity_contract_seal', $payload.'|'.$this->contractReference))) {
            throw new RuntimeException('Installation identity contract is invalid.');
        }
    }

    /** @param array<string, string> $manifestPayloadHashes */
    private static function payload(
        string $basePhysicalIdentityReference,
        string $connectionInstanceReference,
        string $baseSchemaFingerprint,
        array $manifestPayloadHashes,
        string $partialStateHash,
    ): string {
        return json_encode([
            'version' => self::VERSION,
            'base_physical_identity_reference' => $basePhysicalIdentityReference,
            'connection_instance_reference' => $connectionInstanceReference,
            'base_schema_fingerprint' => $baseSchemaFingerprint,
            'manifest_payload_hashes' => $manifestPayloadHashes,
            'partial_state_hash' => $partialStateHash,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
