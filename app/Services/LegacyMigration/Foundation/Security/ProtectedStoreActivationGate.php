<?php

namespace App\Services\LegacyMigration\Foundation\Security;

/** Fail-closed deployment gate in addition to operation authority and DB guards. */
final class ProtectedStoreActivationGate
{
    public static function assertPopulationWriteAllowed(): void
    {
        if (app()->runningUnitTests() && app()->environment('testing')) {
            return;
        }
        $protected = (array) config('legacy-migration.protected_store', []);
        $keys = (array) config('legacy-migration.key_provider', []);
        $retention = (array) config('legacy-migration.retention', []);
        $referenceSourcePresent = (is_array($keys['references'] ?? null) && $keys['references'] !== [])
            || (is_string($keys['reference_manifest'] ?? null) && $keys['reference_manifest'] !== ''
                && is_string($keys['reference_manifest_hash'] ?? null) && preg_match('/\A[a-f0-9]{64}\z/D', $keys['reference_manifest_hash']) === 1);

        if (($protected['population_enabled'] ?? false) !== true
            || ($protected['full_envelope_required'] ?? false) !== true
            || ($protected['keyed_integrity_required'] ?? false) !== true
            || ($protected['purpose_scoped_access_required'] ?? false) !== true
            || ($protected['direct_model_access_allowed'] ?? true) !== false
            || ! is_string($protected['access_policy_reference'] ?? null)
            || $protected['access_policy_reference'] === ''
            || ($keys['provider'] ?? null) !== 'external_reference'
            || ! $referenceSourcePresent
            || ($retention['schedule_resolved'] ?? false) !== true
            || ! is_string($retention['policy_reference'] ?? null)
            || $retention['policy_reference'] === '') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ACTIVATION-GATE-001');
        }
    }
}
