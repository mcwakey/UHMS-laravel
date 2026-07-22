<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final class CanonicalManifestHasher
{
    /** @param array<string, mixed> $manifest */
    public function hash(array $manifest): string
    {
        return hash('sha256', json_encode(
            $this->canonicalize($manifest),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }

    public function assertDigest(string $digest, string $field): string
    {
        $normalized = strtolower(str_starts_with($digest, 'sha256:') ? substr($digest, 7) : $digest);
        if (preg_match('/\A[a-f0-9]{64}\z/', $normalized) !== 1) {
            throw new SnapshotException('FOUNDATION_MANIFEST_DIGEST_INVALID', "A mandatory {$field} digest is invalid.");
        }

        return $normalized;
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
