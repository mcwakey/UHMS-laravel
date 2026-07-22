<?php

namespace Tests\Unit\LegacyMigration\Foundation\Integration;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedToken as StorageToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProtectedTokenStorageIntegrationTest extends TestCase
{
    #[Test]
    public function security_token_produces_the_fixed_width_protected_lookup_digest(): void
    {
        $service = new HmacTokenService(
            new ConfiguredKeyProvider([
                'algorithm' => 'sha256',
                'key_id' => 'synthetic-key',
                'key_version' => 'v1',
                'key' => '6789abcdef012345ABCDEF!@#$%^&*()-+=012345',
            ]),
            new \App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry,
            'testing',
        );
        $message = (new CanonicalTypedMessageEncoder)->encode([
            TypedValue::string('SYNTHETIC-ONLY-P3'),
        ]);
        $token = $service->tokenize(new TokenDomain('patient_source'), $message);

        self::assertSame($token->lookupDigest(), StorageToken::assert($token->lookupDigest()));
        self::assertSame(64, strlen($token->lookupDigest()));
        self::assertNotSame($token->encode(), $token->lookupDigest());
    }
}
