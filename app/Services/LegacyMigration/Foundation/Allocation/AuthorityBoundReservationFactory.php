<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Recovery\ProtectedRecoveryStore;
use App\Services\LegacyMigration\Foundation\Recovery\VerifiedRecoveryCoordinate;

/** Production composition for protected reservation attributes and envelopes. */
final readonly class AuthorityBoundReservationFactory implements ReservationAttributeFactory, ReservationProtectionFactory
{
    /** @param array<string,mixed> $configuration */
    public function __construct(
        private HmacTokenService $tokens,
        private CanonicalTypedMessageEncoder $encoder,
        private ProtectedRecoveryStore $coordinates,
        private array $configuration,
        private string $environment,
    ) {}

    public function make(AllocationRequest $request, string $number, int $ordinal): array
    {
        $coordinate = $this->coordinate($request);
        $numberToken = ProtectedToken::parse($this->token('number_reservation', ['number', $number, (string) $ordinal]));

        return [
            'run_id' => $coordinate->hint->runId,
            'idempotency_record_id' => $coordinate->hint->idempotencyRecordId,
            'protected_number_token' => $numberToken->lookupDigest(),
            'contract_version' => $this->contractVersion(),
            'transformation_version' => $this->transformationVersion(),
            'canonicalization_version' => $this->canonicalizationVersion(),
            'hmac_key_version' => $numberToken->keyVersion(),
            'integrity_checksum' => ProtectedToken::parse($this->token('artifact_integrity', [
                'reservation', $request->patientCoreKey, (string) $ordinal,
            ]))->lookupDigest(),
            'access_classification' => $this->accessClassification(),
            'retention_classification' => $this->retentionClassification(),
            'lock_version' => 0,
        ];
    }

    public function connectionName(): string
    {
        return $this->coordinates->connectionName();
    }

    public function context(AllocationRequest $request): ProtectedStoreOperationContext
    {
        $coordinate = $this->coordinate($request);

        return new ProtectedStoreOperationContext(
            'foundation_number_reservation', ['read', 'write'], ['number_reservation'], $this->environment,
            $coordinate->hint->runId, $coordinate->hint->sourceSnapshotId, $coordinate->hint->targetSnapshotId,
            $this->accessClassification(), $this->retentionClassification(), $this->authorityReference(),
            ['sequence_ordinal', 'configuration_fingerprint', 'period_key'],
        );
    }

    public function expectedProtectedSourceToken(AllocationRequest $request): string
    {
        return ProtectedToken::parse($this->sourceToken($request))->lookupDigest();
    }

    public function tokenSet(AllocationRequest $request, string $number, int $ordinal): array
    {
        return [
            'protected_source_token' => ['encoded_token' => $this->sourceToken($request), 'domain' => 'number_reservation'],
            'numbering_coordinate_token' => ['encoded_token' => $this->token('number_reservation', ['coordinate', $request->configuration->coordinateToken()]), 'domain' => 'number_reservation'],
            'protected_number_token' => ['encoded_token' => $this->token('number_reservation', ['number', $number, (string) $ordinal]), 'domain' => 'number_reservation'],
        ];
    }

    private function coordinate(AllocationRequest $request): VerifiedRecoveryCoordinate
    {
        $this->assertEnabled();
        $coordinate = $this->coordinates->transaction(fn (): VerifiedRecoveryCoordinate => $this->coordinates->reservationCoordinate(
            $request->patientCoreKey,
            $request->protectedSourceToken,
        ));
        if (! hash_equals($this->expectedBundleHash(), $coordinate->contractBundleHash)
            || $coordinate->domain !== 'patient_core'
            || $coordinate->hint->targetSnapshotId === null) {
            throw AllocationException::failClosed('PATIENT-NUM-AUTHORITATIVE-COORDINATE-MISMATCH');
        }

        return $coordinate;
    }

    private function assertEnabled(): void
    {
        $allocation = (array) ($this->configuration['allocation'] ?? []);
        $protected = (array) ($this->configuration['protected_store'] ?? []);
        if (($allocation['authority_verified'] ?? false) !== true
            || ($allocation['protected_reservations_enabled'] ?? false) !== true
            || ! is_string($allocation['connection'] ?? null)
            || trim((string) $allocation['connection']) === ''
            || ($protected['population_enabled'] ?? false) !== true
            || ($protected['full_envelope_required'] ?? false) !== true
            || ($protected['keyed_integrity_required'] ?? false) !== true
            || ($protected['purpose_scoped_access_required'] ?? false) !== true
            || ($protected['direct_model_access_allowed'] ?? true) !== false) {
            throw AllocationException::failClosed('PATIENT-NUM-PROTECTED-AUTHORITY-NOT-ENABLED');
        }
        if (! hash_equals(trim((string) $allocation['connection']), $this->connectionName())) {
            throw AllocationException::failClosed('PATIENT-NUM-CONNECTION-BOUNDARY-MISMATCH');
        }
        foreach ([$this->authorityReference(), $this->accessClassification(), $this->retentionClassification(), $this->transformationVersion()] as $value) {
            if ($value === '' || strlen($value) > 160) {
                throw AllocationException::failClosed('PATIENT-NUM-PROTECTED-AUTHORITY-INCOMPLETE');
            }
        }
        if (! hash_equals($this->authorityReference(), (string) ($protected['access_policy_reference'] ?? ''))) {
            throw AllocationException::failClosed('PATIENT-NUM-PROTECTED-AUTHORITY-MISMATCH');
        }
    }

    /** @param list<string> $parts */
    private function token(string $domain, array $parts): string
    {
        return $this->tokens->tokenize(
            new TokenDomain($domain),
            $this->encoder->encode(array_map(static fn (string $part): TypedValue => TypedValue::string($part), ['number-reservation/v1', ...$parts]), $this->canonicalizationVersion()),
        )->encode();
    }

    private function sourceToken(AllocationRequest $request): string
    {
        return $this->token('number_reservation', ['source-lineage', $request->protectedSourceToken]);
    }

    private function allocation(string $key): string { return (string) ($this->configuration['allocation'][$key] ?? ''); }
    private function authorityReference(): string { return $this->allocation('authority_reference'); }
    private function accessClassification(): string { return $this->allocation('access_classification'); }
    private function retentionClassification(): string { return $this->allocation('retention_classification'); }
    private function transformationVersion(): string { return $this->allocation('transformation_version'); }
    private function contractVersion(): string { return (string) ($this->configuration['versions']['contract_bundle'] ?? ''); }
    private function canonicalizationVersion(): string { return (string) ($this->configuration['versions']['canonicalization'] ?? ''); }
    private function expectedBundleHash(): string { return (string) ($this->configuration['phase2f_policy']['expected_bundle_hash'] ?? ''); }
}
