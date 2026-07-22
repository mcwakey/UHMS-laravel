<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TokenDomainRegistry;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProtectedStoreAccessGuardTest extends TestCase
{
    #[Test]
    public function exact_authority_and_keyed_integrity_seal_authorize_access(): void
    {
        [$service, $encoder, $encodedToken] = $this->securityContext();
        $guard = new ProtectedStoreAccessGuard($this->authority(), $service, $encoder);
        $fields = [TypedValue::string('SYNTHETIC-ONLY-P3::STATE'), TypedValue::integer(3)];
        $seal = $guard->seal($encodedToken, 'patient_source', $fields);

        $authorized = $guard->authorizeAndVerifyIntegrity(
            $encodedToken,
            'patient_source',
            $fields,
            $seal->encode(),
        );

        $this->assertSame('patient_source', $authorized->domain());
        $this->assertSame('migration-hmac', $authorized->keyId());
        $this->assertSame('v1', $authorized->keyVersion());
    }

    #[Test]
    public function missing_authority_rejects_before_parsing_or_verification(): void
    {
        [$service, $encoder] = $this->securityContext();
        $guard = new ProtectedStoreAccessGuard(null, $service, $encoder);

        $this->expectExceptionMessage('LM-SEC-STORE-AUTHORITY-MISSING-001');
        $guard->authorize('not-a-token', 'patient_source');
    }

    #[Test]
    public function every_pinned_context_coordinate_is_required(): void
    {
        [$service, $encoder, $encodedToken] = $this->securityContext();
        $authorities = [
            new PinnedProtectedStoreAccessAuthority('staging', ['patient_source'], 'migration-hmac', 'v1', 'typed-length-prefix/1'),
            new PinnedProtectedStoreAccessAuthority('testing', ['contact_source'], 'migration-hmac', 'v1', 'typed-length-prefix/1'),
            new PinnedProtectedStoreAccessAuthority('testing', ['patient_source'], 'other-hmac', 'v1', 'typed-length-prefix/1'),
            new PinnedProtectedStoreAccessAuthority('testing', ['patient_source'], 'migration-hmac', 'v2', 'typed-length-prefix/1'),
            new PinnedProtectedStoreAccessAuthority('testing', ['patient_source'], 'migration-hmac', 'v1', 'typed-length-prefix/2'),
        ];

        foreach ($authorities as $authority) {
            $guard = new ProtectedStoreAccessGuard($authority, $service, $encoder);
            try {
                $guard->authorize($encodedToken, $authority->permittedDomains()[0]);
                $this->fail('Mismatched protected-store authority should fail closed.');
            } catch (ProtectedStoreAccessDeniedException $exception) {
                $this->assertStringContainsString('LM-SEC-STORE-CONTEXT-001', $exception->getMessage());
                $this->assertStringNotContainsString($encodedToken, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function tampered_integrity_fields_reject_without_exposing_token_or_values(): void
    {
        [$service, $encoder, $encodedToken] = $this->securityContext();
        $guard = new ProtectedStoreAccessGuard($this->authority(), $service, $encoder);
        $original = [TypedValue::string('SYNTHETIC-ONLY-P3::ORIGINAL')];
        $seal = $guard->seal($encodedToken, 'patient_source', $original);
        $tampered = 'SYNTHETIC-ONLY-P3::TAMPERED';

        try {
            $guard->authorizeAndVerifyIntegrity(
                $encodedToken,
                'patient_source',
                [TypedValue::string($tampered)],
                $seal->encode(),
            );
            $this->fail('Tampered integrity input should fail closed.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringNotContainsString($tampered, $exception->getMessage());
            $this->assertStringNotContainsString($encodedToken, $exception->getMessage());
            $this->assertStringContainsString('LM-SEC-STORE-INTEGRITY-001', $exception->getMessage());
        }
    }

    #[Test]
    public function an_integrity_seal_cannot_be_replayed_for_another_protected_token(): void
    {
        [$service, $encoder, $encodedToken] = $this->securityContext();
        $guard = new ProtectedStoreAccessGuard($this->authority(), $service, $encoder);
        $fields = [TypedValue::string('SYNTHETIC-ONLY-P3::UNCHANGED')];
        $seal = $guard->seal($encodedToken, 'patient_source', $fields);
        $otherToken = $service->tokenize(
            new TokenDomain('patient_source'),
            $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3::OTHER-ROW')]),
        );

        $this->expectExceptionMessage('LM-SEC-STORE-INTEGRITY-001');
        $guard->authorizeAndVerifyIntegrity(
            $otherToken->encode(),
            'patient_source',
            $fields,
            $seal->encode(),
        );
    }

    /** @return array{HmacTokenService, CanonicalTypedMessageEncoder, string} */
    private function securityContext(): array
    {
        $versions = new CanonicalizationVersionRegistry;
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $service = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => '56789abcdef01234FABCDE!@#$%^&*()-+=01234',
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
            new TokenDomainRegistry(['patient_source', 'contact_source', 'artifact_integrity']),
        );
        $token = $service->tokenize(
            new TokenDomain('patient_source'),
            $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3::ROW')]),
        );

        return [$service, $encoder, $token->encode()];
    }

    private function authority(): PinnedProtectedStoreAccessAuthority
    {
        return new PinnedProtectedStoreAccessAuthority(
            'testing',
            ['patient_source'],
            'migration-hmac',
            'v1',
            'typed-length-prefix/1',
        );
    }
}
