<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\ProtectedKeyReference;
use App\Models\LegacyMigration\ProtectedPurgeRequest;
use App\Models\LegacyMigration\ProtectedRetentionPolicy;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use Illuminate\Support\Facades\DB;

/** Purpose-scoped, append-only protected lifecycle metadata. */
final class ProtectedLifecycleRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    public function appendKeyReference(array $attributes): never
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-LIFECYCLE-PROTECTED-METHOD-REQUIRED-001');
    }

    /** @param array<string,mixed> $attributes */
    public function appendKeyReferenceProtected(
        ProtectedStoreOperationContext $context,
        array $attributes,
        string $encodedLifecycleToken,
        string $domain,
    ): ProtectedKeyReference {
        $this->rejectCallerChecksum($attributes);
        $this->assertExternalReference($attributes['secret_reference'] ?? null);

        return DB::transaction(function () use ($context, $attributes, $encodedLifecycleToken, $domain): ProtectedKeyReference {
            if (($attributes['active_for_signing'] ?? false) === true) {
                $active = DB::table('legacy_migration_protected_key_references')
                    ->where('token_environment', $attributes['token_environment'])
                    ->where('domain', $attributes['domain'])
                    ->where('active_for_signing', true)
                    ->lockForUpdate()
                    ->exists();
                if ($active) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-KEY-ACTIVE-COUNT-001');
                }
            }
            $attributes['integrity_checksum'] = $this->checksum($context, $encodedLifecycleToken, $domain, 'key_reference', $attributes);
            $record = ProtectedKeyReference::query()->create($attributes);
            $this->seal($context, $record, $encodedLifecycleToken, $domain, $context->accessClassification(), $context->retentionClassification());

            return $record;
        }, 3);
    }

    public function appendRetentionPolicy(array $attributes): never
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-LIFECYCLE-PROTECTED-METHOD-REQUIRED-001');
    }

    /** @param array<string,mixed> $attributes */
    public function appendRetentionPolicyProtected(
        ProtectedStoreOperationContext $context,
        array $attributes,
        string $encodedLifecycleToken,
        string $domain,
    ): ProtectedRetentionPolicy {
        $this->rejectCallerChecksum($attributes);
        if (($attributes['active'] ?? false) === true
            && (! is_string($attributes['owner_approval_reference'] ?? null) || $attributes['owner_approval_reference'] === '')) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RETENTION-OWNER-APPROVAL-001');
        }
        if (($attributes['purge_enabled'] ?? false) === true
            && (! is_string($attributes['purge_authority_reference'] ?? null) || $attributes['purge_authority_reference'] === '')) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RETENTION-PURGE-AUTHORITY-001');
        }

        return DB::transaction(function () use ($context, $attributes, $encodedLifecycleToken, $domain): ProtectedRetentionPolicy {
            $attributes['integrity_checksum'] = $this->checksum($context, $encodedLifecycleToken, $domain, 'retention_policy', $attributes);
            $record = ProtectedRetentionPolicy::query()->create($attributes);
            $this->seal(
                $context,
                $record,
                $encodedLifecycleToken,
                $domain,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
            );

            return $record;
        }, 3);
    }

    public function recordBlockedPurgeRequest(mixed ...$arguments): never
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-LIFECYCLE-PROTECTED-METHOD-REQUIRED-001');
    }

    /** @param array<string,mixed> $attributes */
    public function recordBlockedPurgeRequestProtected(
        ProtectedStoreOperationContext $context,
        int $envelopeId,
        int $policyId,
        array $attributes,
        string $encodedLifecycleToken,
        string $domain,
    ): ProtectedPurgeRequest {
        $this->rejectCallerChecksum($attributes);
        $this->security()->verifyStoredEnvelope($context, $envelopeId);
        $this->security()->readProjection($context, ProtectedRetentionPolicy::class, $policyId);
        $policy = DB::table('legacy_migration_retention_policies')->where('id', $policyId)->first();
        if ($policy === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-RETENTION-POLICY-MISSING-001');
        }
        if ((bool) $policy->legal_hold || (bool) $policy->operational_hold || ! (bool) $policy->active || ! (bool) $policy->purge_enabled) {
            $attributes['state'] = 'blocked_pending_owner_policy';
            $attributes['authorized_by_authority_reference'] = null;
            $attributes['authorized_at'] = null;
        } else {
            $attributes['state'] = 'eligible_not_executable_phase3b';
        }
        $attributes['executed_at'] = null;
        $attributes['envelope_id'] = $envelopeId;
        $attributes['retention_policy_id'] = $policyId;
        $attributes['integrity_checksum'] = $this->checksum($context, $encodedLifecycleToken, $domain, 'purge_request', $attributes);

        return DB::transaction(function () use ($context, $attributes, $encodedLifecycleToken, $domain, $policy): ProtectedPurgeRequest {
            $record = ProtectedPurgeRequest::query()->create($attributes);
            $this->seal(
                $context,
                $record,
                $encodedLifecycleToken,
                $domain,
                (string) $policy->access_classification,
                (string) $policy->retention_classification,
            );

            return $record;
        }, 3);
    }

    public function purge(): never
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-NOT-AUTHORIZED');
    }

    private function assertExternalReference(mixed $reference): void
    {
        if (! is_string($reference) || preg_match('#\A(?:env|secret|vault|kms)://[A-Za-z0-9._/-]+\z#D', $reference) !== 1) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-KEY-EXTERNAL-REFERENCE-001');
        }
        if (str_contains(strtolower($reference), 'base64:') || str_contains(strtolower($reference), 'password')) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-KEY-EXTERNAL-REFERENCE-002');
        }
    }

    /** @param array<string,mixed> $attributes */
    private function rejectCallerChecksum(array $attributes): void
    {
        if (array_key_exists('integrity_checksum', $attributes)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-LIFECYCLE-CALLER-CHECKSUM-001');
        }
    }

    /** @param array<string,mixed> $attributes */
    private function checksum(ProtectedStoreOperationContext $context, string $encodedToken, string $domain, string $kind, array $attributes): string
    {
        ksort($attributes, SORT_STRING);
        $canonical = json_encode($attributes, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);

        return $this->security()->keyedIntegrityChecksum($context, $encodedToken, $domain, [
            TypedValue::string($kind),
            TypedValue::string(hash('sha256', $canonical)),
        ]);
    }

    private function seal(
        ProtectedStoreOperationContext $context,
        ProtectedFoundationModel $record,
        string $encodedToken,
        string $domain,
        string $accessClassification,
        string $retentionClassification,
    ): void {
        $this->security()->seal(
            $context,
            $record,
            $encodedToken,
            $domain,
            null,
            null,
            null,
            $accessClassification,
            $retentionClassification,
            ['record_token' => ['encoded_token' => $encodedToken, 'domain' => $domain]],
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
