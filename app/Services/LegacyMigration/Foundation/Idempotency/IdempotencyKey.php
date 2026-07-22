<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

final readonly class IdempotencyKey
{
    public function __construct(
        public string $domain,
        public string $token,
        public IdempotencyOutcomeType $outcomeType,
        public string $inputFingerprint,
        public string $contractVersion,
        public string $transformationVersion,
        public string $canonicalizationVersion,
        public string $hmacKeyVersion,
    ) {
        if ($domain === '' || preg_match('/\A[0-9a-f]{64}\z/D', $token) !== 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $inputFingerprint) !== 1) {
            throw IdempotencyException::incompatible();
        }

        foreach ([$contractVersion, $transformationVersion, $canonicalizationVersion, $hmacKeyVersion] as $version) {
            if ($version === '') {
                throw IdempotencyException::incompatible();
            }
        }
    }
}
