<?php

namespace App\Services\Integrations;

use App\Models\IntegrationProvider;
use App\Models\IntegrationProviderCredential;
use Illuminate\Support\Facades\Log;

/**
 * Owns the encryption, masking and retrieval of provider credentials.
 *
 * Plaintext values exist only transiently here and inside provider adapters —
 * never on the provider row, never in Blade, never in logs/exceptions.
 */
class IntegrationCredentialService
{
    /** Sentinel the UI submits for an unchanged sensitive field. */
    public const UNCHANGED = '__UNCHANGED__';

    /**
     * Upsert credential values. Blank or "unchanged" values are skipped so an
     * operator can update one secret without re-entering the others.
     *
     * @param array<string,string|null> $values credential_key => plaintext
     * @return array<int,string> the credential keys actually written
     */
    public function upsert(IntegrationProvider $provider, array $values, ?int $userId = null): array
    {
        $written = [];

        foreach ($values as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            if ($value === null || $value === '' || $value === self::UNCHANGED) {
                continue; // keep existing value
            }

            IntegrationProviderCredential::updateOrCreate(
                ['integration_provider_id' => $provider->id, 'credential_key' => $key],
                [
                    'encrypted_value' => (string) $value, // 'encrypted' cast encrypts on save
                    'is_sensitive' => true,
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ],
            );
            $written[] = $key;
        }

        return $written;
    }

    /**
     * Decrypted key => plaintext map. For adapter use only.
     *
     * @return array<string,string|null>
     */
    public function decryptedMap(IntegrationProvider $provider): array
    {
        $map = [];
        foreach ($provider->credentials()->get() as $cred) {
            try {
                $map[$cred->credential_key] = $cred->encrypted_value; // decrypts via cast
            } catch (\Throwable $e) {
                // A value encrypted under a rotated APP_KEY can't be read — treat as
                // missing rather than leaking a decryption exception.
                Log::warning('Integration credential decrypt failed', [
                    'provider_id' => $provider->id,
                    'credential_key' => $cred->credential_key,
                ]);
                $map[$cred->credential_key] = null;
            }
        }
        return $map;
    }

    /**
     * Masked map for UI display: which keys are set, never the values.
     *
     * @return array<string,string>
     */
    public function maskedMap(IntegrationProvider $provider): array
    {
        $map = [];
        foreach ($provider->credentials()->get() as $cred) {
            $map[$cred->credential_key] = '••••••••';
        }
        return $map;
    }

    public function configuredKeys(IntegrationProvider $provider): array
    {
        return $provider->credentials()->pluck('credential_key')->all();
    }
}
