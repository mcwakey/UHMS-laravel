<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CanonicalTypedMessageEncoderTest extends TestCase
{
    #[Test]
    public function typed_length_prefixing_prevents_ambiguous_messages(): void
    {
        $encoder = new CanonicalTypedMessageEncoder;
        $service = $this->service();
        $domain = new TokenDomain('patient_source');

        $splitLeft = $service->tokenize($domain, $encoder->encode([TypedValue::string('ab'), TypedValue::string('c')]));
        $splitRight = $service->tokenize($domain, $encoder->encode([TypedValue::string('a'), TypedValue::string('bc')]));
        $typedString = $service->tokenize($domain, $encoder->encode([TypedValue::string('1')]));
        $typedInteger = $service->tokenize($domain, $encoder->encode([TypedValue::integer(1)]));
        $null = $service->tokenize($domain, $encoder->encode([TypedValue::null()]));
        $empty = $service->tokenize($domain, $encoder->encode([TypedValue::string('')]));

        $this->assertNotSame($splitLeft->encode(), $splitRight->encode());
        $this->assertNotSame($typedString->encode(), $typedInteger->encode());
        $this->assertNotSame($null->encode(), $empty->encode());
    }

    #[Test]
    public function untyped_and_invalid_utf8_inputs_fail_closed(): void
    {
        $encoder = new CanonicalTypedMessageEncoder;

        try {
            $encoder->encode(['untyped']);
            $this->fail('Untyped input should have failed.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringNotContainsString('untyped', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        TypedValue::string("\xC3\x28");
    }

    private function service(): HmacTokenService
    {
        $versions = new CanonicalizationVersionRegistry;

        return new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => '456789abcdef0123EFABCD!@#$%^&*()-+=0123',
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
        );
    }
}
