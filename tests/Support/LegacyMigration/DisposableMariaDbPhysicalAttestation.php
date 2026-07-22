<?php

namespace Tests\Support\LegacyMigration;

final class DisposableMariaDbPhysicalAttestation
{
    /** @param array<string,string|int> $observation */
    public static function verify(string $path, string $expectedFileHash, string $schema, array $observation): string
    {
        if ($path === '' || ! is_file($path)
            || preg_match('/\A[0-9a-f]{64}\z/D', $expectedFileHash) !== 1
            || ! hash_equals($expectedFileHash, hash_file('sha256', $path))) {
            throw new \RuntimeException('Disposable physical-identity attestation is absent or not independently pinned.');
        }
        $document = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
        if (! is_array($document)
            || ($document['purpose'] ?? null) !== 'phase3b_allocator_disposable_mariadb_10_4'
            || ! hash_equals($schema, (string) ($document['schema'] ?? ''))
            || ! is_string($document['authority_reference'] ?? null)
            || trim($document['authority_reference']) === ''
            || ! is_string($document['expires_at'] ?? null)
            || new \DateTimeImmutable($document['expires_at']) <= new \DateTimeImmutable('now')) {
            throw new \RuntimeException('Disposable physical-identity attestation metadata is invalid.');
        }
        ksort($observation, SORT_STRING);
        $identity = hash('sha256', json_encode($observation, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        if (! hash_equals($identity, (string) ($document['physical_identity_hash'] ?? ''))) {
            throw new \RuntimeException('Observed MariaDB physical identity does not match the independent attestation.');
        }

        return (string) $document['authority_reference'];
    }
}
