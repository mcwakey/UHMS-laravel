<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class VerifiedRecoveryCoordinate
{
    public function __construct(
        public RecoveryCoordinateHint $hint,
        public string $domain,
        public string $inputFingerprint,
        public string $sourceSnapshotFingerprint,
        public ?string $targetSnapshotFingerprint,
        public string $contractBundleHash,
    ) {
        foreach (array_filter([$inputFingerprint, $sourceSnapshotFingerprint, $targetSnapshotFingerprint, $contractBundleHash]) as $hash) {
            if (preg_match('/\A[0-9a-f]{64}\z/D', $hash) !== 1) {
                throw RecoveryException::failClosed('RECOVERY-COORDINATE-FINGERPRINT-INVALID');
            }
        }
        if ($domain === '') {
            throw RecoveryException::failClosed('RECOVERY-COORDINATE-DOMAIN-INVALID');
        }
    }
}
