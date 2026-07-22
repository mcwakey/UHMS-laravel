<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final readonly class ApprovedProtectedRotation
{
    /** @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function __construct(
        public string $primaryEncodedToken,
        public array $tokenSet,
        public ProtectedRecordEnvelopeFactory $factory,
        public ProtectedRecordEnvelopeVerifier $verifier,
        public string $authorityReference,
    ) {}
}
