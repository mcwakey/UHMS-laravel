<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use InvalidArgumentException;

final class CanonicalTypedMessageEncoder
{
    public function __construct(
        private readonly CanonicalizationVersionRegistry $versions = new CanonicalizationVersionRegistry,
    ) {}

    /** @param list<TypedValue> $values */
    public function encode(array $values, ?string $version = null): CanonicalMessage
    {
        $version ??= $this->versions->activeVersion();
        $this->versions->assertSupported($version);

        $encoded = self::segment('version', $version).pack('N', count($values));
        foreach ($values as $value) {
            if (! $value instanceof TypedValue) {
                throw new InvalidArgumentException('Canonical inputs must be explicitly typed [LM-SEC-CANON-TYPE-001].');
            }

            $payload = $value->payload();
            $encoded .= self::segment('type', $value->type());
            $encoded .= $payload === null
                ? "\x00".pack('N', 0)
                : "\x01".pack('N', strlen($payload)).$payload;
        }

        return new CanonicalMessage($version, $encoded);
    }

    private static function segment(string $type, string $value): string
    {
        return pack('N', strlen($type)).$type.pack('N', strlen($value)).$value;
    }
}
