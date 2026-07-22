<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class HmacKeyMaterial
{
    public function __construct(
        private readonly string $keyId,
        private readonly string $version,
        #[\SensitiveParameter]
        private readonly string $secret,
    ) {
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,63}$/D', $this->keyId) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,31}$/D', $this->version) !== 1) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-001');
        }
        if (strlen($this->secret) < 32 || self::looksLikePlaceholder($this->secret) || self::hasLowDiversity($this->secret)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-001');
        }
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function authenticate(string $domainContext, string $canonicalPayload): string
    {
        $derivedKey = hash_hmac(
            'sha256',
            "legacy-migration/domain-key/v1\0".$domainContext,
            $this->secret,
            true,
        );

        return hash_hmac(
            'sha256',
            "legacy-migration/protected-token/v1\0".$canonicalPayload,
            $derivedKey,
            true,
        );
    }

    private static function looksLikePlaceholder(string $value): bool
    {
        return preg_match('/(?:change[-_ ]?me|replace[-_ ]?me|example|placeholder|test[-_ ]?key)/i', $value) === 1;
    }

    private static function hasLowDiversity(string $value): bool
    {
        $frequencies = count_chars($value, 1);
        if (count($frequencies) < 12) {
            return true;
        }

        return max($frequencies) > (int) floor(strlen($value) / 2);
    }
}
