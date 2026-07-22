<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenContextMismatchException;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TokenDomainRegistry;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HmacTokenServiceTest extends TestCase
{
    #[Test]
    public function tokens_are_deterministic_only_inside_the_exact_context(): void
    {
        $encoder = new CanonicalTypedMessageEncoder;
        $message = $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3::VALUE')]);
        $patient = new TokenDomain('patient_source');
        $contact = new TokenDomain('contact_source');
        $service = $this->service('testing', 'v1', '0123456789abcdefABCDEF!@#$%^&*()-+=');

        $first = $service->tokenize($patient, $message);
        $second = $service->tokenize($patient, $message);
        $otherDomain = $service->tokenize($contact, $message);
        $otherEnvironment = $this->service('staging', 'v1', '0123456789abcdefABCDEF!@#$%^&*()-+=')->tokenize($patient, $message);

        $this->assertTrue($first->matches($second));
        $this->assertNotSame($first->encode(), $otherDomain->encode());
        $this->assertNotSame($first->encode(), $otherEnvironment->encode());

        $this->expectException(TokenContextMismatchException::class);
        $first->matches($otherDomain);
    }

    #[Test]
    public function rotation_verifies_old_lineage_and_emits_the_active_version(): void
    {
        $versions = new CanonicalizationVersionRegistry;
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $message = $encoder->encode([TypedValue::integer(42)]);
        $domain = new TokenDomain('patient_source');
        $oldService = $this->service('testing', 'v1', '123456789abcdef0BCDEFA!@#$%^&*()-+=0');
        $oldToken = $oldService->tokenize($domain, $message);

        $rotatedService = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v2',
                'key' => '23456789abcdef01CDEFAB!@#$%^&*()-+=01',
                'algorithm' => 'sha256',
                'previous_keys' => [[
                    'key_id' => 'migration-hmac',
                    'key_version' => 'v1',
                    'key' => '123456789abcdef0BCDEFA!@#$%^&*()-+=0',
                ]],
            ]),
            $versions,
            'testing',
        );

        $this->assertTrue($rotatedService->verify($domain, $message, $oldToken));
        $rotation = $rotatedService->rotate($domain, $message, $oldToken);
        $this->assertTrue($rotation->rotated);
        $this->assertSame('v1', $rotation->priorKeyVersion);
        $this->assertSame('v2', $rotation->token->keyVersion());
        $this->assertTrue($rotatedService->verify($domain, $message, $rotation->token));
        $this->assertNotSame($oldToken->encode(), $rotation->token->encode());
        $this->assertSame($rotation->token->encode(), ProtectedToken::parse($rotation->token->encode())->encode());
    }

    #[Test]
    public function an_unlisted_domain_fails_before_token_generation(): void
    {
        $versions = new CanonicalizationVersionRegistry;
        $service = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => '3456789abcdef012DEFABC!@#$%^&*()-+=012',
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
            new TokenDomainRegistry(['patient_source']),
        );

        $this->expectExceptionMessage('LM-SEC-DOMAIN-NOT-ALLOWED-001');
        $service->tokenize(
            new TokenDomain('contact_source'),
            (new CanonicalTypedMessageEncoder($versions))->encode([TypedValue::null()]),
        );
    }

    private function service(string $environment, string $version, string $key): HmacTokenService
    {
        return new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => $version,
                'key' => $key,
                'algorithm' => 'sha256',
            ]),
            new CanonicalizationVersionRegistry,
            $environment,
        );
    }
}
