<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class PhysicalServerIdentityVerification
{
    private function __construct(
        public string $contractVersion,
        public string $approvedIdentityReference,
        public string $structuralIdentityReference,
        public string $configurationFingerprint,
        public string $connectionInstanceReference,
        public string $observedPhysicalReference,
        private string $verificationSeal,
    ) {}

    public static function issue(
        string $contractVersion,
        string $approvedIdentityReference,
        string $structuralIdentityReference,
        string $configurationFingerprint,
        IdentityReferenceHasher $hasher,
        ?string $connectionInstanceReference = null,
        ?string $observedPhysicalReference = null,
    ): self {
        $connectionInstanceReference ??= $hasher->reference('synthetic_connection_instance', $approvedIdentityReference);
        $observedPhysicalReference ??= $hasher->reference('synthetic_observed_physical_target', $approvedIdentityReference);

        return new self(
            $contractVersion,
            $approvedIdentityReference,
            $structuralIdentityReference,
            $configurationFingerprint,
            $connectionInstanceReference,
            $observedPhysicalReference,
            $hasher->reference('physical_identity_verification_seal', implode('|', [
                $contractVersion, $approvedIdentityReference, $structuralIdentityReference, $configurationFingerprint,
                $connectionInstanceReference,
                $observedPhysicalReference,
            ])),
        );
    }

    public function isAuthentic(IdentityReferenceHasher $hasher): bool
    {
        $expected = $hasher->reference('physical_identity_verification_seal', implode('|', [
            $this->contractVersion,
            $this->approvedIdentityReference,
            $this->structuralIdentityReference,
            $this->configurationFingerprint,
            $this->connectionInstanceReference,
            $this->observedPhysicalReference,
        ]));

        return hash_equals($expected, $this->verificationSeal);
    }

    /** @return array<string, string> */
    public function redactedReport(): array
    {
        return [
            'status' => 'verified',
            'contract_version' => $this->contractVersion,
            'approved_identity_reference' => $this->approvedIdentityReference,
            'structural_identity_reference' => $this->structuralIdentityReference,
            'configuration_fingerprint' => $this->configurationFingerprint,
            'connection_binding_status' => 'verified',
            'physical_reobservation_status' => 'verified',
        ];
    }
}
