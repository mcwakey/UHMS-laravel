<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;

final class MeasurementIntegrityService
{
    public function __construct(
        private readonly HmacTokenService $hmac,
        private readonly CanonicalTypedMessageEncoder $encoder,
    ) {}

    /** @param list<string> $material */
    public function seal(array $material): string
    {
        return $this->hmac->tokenize(
            new TokenDomain('artifact_integrity'),
            $this->encoder->encode(array_map(TypedValue::string(...), $material)),
        )->encode();
    }

    public function verify(AuthoritativeMeasurement $measurement): bool
    {
        try {
            return $this->hmac->verify(
                new TokenDomain('artifact_integrity'),
                $this->encoder->encode(array_map(TypedValue::string(...), $measurement->sealMaterial())),
                ProtectedToken::parse($measurement->integritySeal),
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
