<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class TokenRotationResult
{
    public function __construct(
        public readonly ProtectedToken $token,
        public readonly string $priorKeyId,
        public readonly string $priorKeyVersion,
        public readonly bool $rotated,
    ) {}
}
