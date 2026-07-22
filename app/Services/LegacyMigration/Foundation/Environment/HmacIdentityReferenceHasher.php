<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class HmacIdentityReferenceHasher implements IdentityReferenceHasher
{
    public function __construct(
        private string $environment,
        private string $keyId,
        private string $keyVersion,
        private string $keyMaterial,
    ) {
        if ($environment === '' || $keyId === '' || $keyVersion === '' || strlen($keyMaterial) < 32) {
            throw new FoundationGuardException('FOUNDATION_IDENTITY_KEY_INVALID', 'The physical identity reference key is unavailable.');
        }
    }

    public function reference(string $field, string $value): string
    {
        if ($field === '' || trim($value) === '') {
            throw new FoundationGuardException('FOUNDATION_IDENTITY_VALUE_MISSING', 'A physical identity component is unavailable.');
        }

        $message = implode("\0", [
            'legacy-migration-target-identity-v1',
            $this->environment,
            $this->keyId,
            $this->keyVersion,
            $field,
            trim($value),
        ]);

        return hash_hmac('sha256', $message, $this->keyMaterial);
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return [
            'environment' => $this->environment,
            'key_id' => $this->keyId,
            'key_version' => $this->keyVersion,
            'key_material' => '[REDACTED_EXTERNAL_KEY_MATERIAL]',
        ];
    }
}
