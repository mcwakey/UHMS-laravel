<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

final class ProtectedToken
{
    public static function assert(string $token, string $field = 'protected token'): string
    {
        if (preg_match('/\A[0-9a-f]{64}\z/D', $token) !== 1) {
            throw new StorageIntegrityException("{$field} must be a lowercase HMAC-SHA-256 token.");
        }

        return $token;
    }

    public static function nullable(?string $token, string $field = 'protected token'): ?string
    {
        return $token === null ? null : self::assert($token, $field);
    }
}
