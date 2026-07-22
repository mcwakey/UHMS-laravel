<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;

final class ProtectedTokenSet
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{encoded_token:string, domain:string}>  $tokenSet
     * @return array<string, mixed>
     */
    public static function apply(array $attributes, array $tokenSet): array
    {
        if ($tokenSet === []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-001');
        }
        foreach ($tokenSet as $field => $entry) {
            if (! str_ends_with($field, '_token') || ! is_array($entry)
                || ! is_string($entry['encoded_token'] ?? null) || ! is_string($entry['domain'] ?? null)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-002');
            }
            $attributes[$field] = ProtectedToken::parse($entry['encoded_token'])->lookupDigest();
        }

        return $attributes;
    }

    /** @param array<string, array{encoded_token:string, domain:string}> $tokenSet */
    public static function primary(array $tokenSet, string $field): string
    {
        $encoded = $tokenSet[$field]['encoded_token'] ?? null;
        if (! is_string($encoded)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PRIMARY-TOKEN-FIELD-001');
        }

        return $encoded;
    }

    /** @param array<string, array{encoded_token:string, domain:string}> $tokenSet */
    public static function domain(array $tokenSet, string $field): string
    {
        $domain = $tokenSet[$field]['domain'] ?? null;
        if (! is_string($domain) || $domain === '') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PRIMARY-TOKEN-FIELD-001');
        }

        return $domain;
    }
}
