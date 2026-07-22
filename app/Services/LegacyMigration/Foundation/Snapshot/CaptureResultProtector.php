<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;

final class CaptureResultProtector
{
    public function __construct(
        private readonly HmacTokenService $hmac,
        private readonly CanonicalTypedMessageEncoder $encoder,
    ) {}

    /** @param array<int, array<string, mixed>> $rows */
    public function protect(string $queryId, array $rows): string
    {
        $canonical = json_encode($this->canonicalize($rows), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        $message = $this->encoder->encode([
            TypedValue::string('authoritative-snapshot-result/1'),
            TypedValue::string($queryId),
            TypedValue::string($canonical),
        ]);

        return $this->hmac->tokenize(new TokenDomain('artifact_integrity'), $message)->encode();
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
