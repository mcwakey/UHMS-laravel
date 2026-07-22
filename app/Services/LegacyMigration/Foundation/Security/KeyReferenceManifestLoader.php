<?php

namespace App\Services\LegacyMigration\Foundation\Security;

/** Loads only externally pinned, non-secret key-reference metadata. */
final class KeyReferenceManifestLoader
{
    /** @return list<array<string, mixed>> */
    public function load(string $path, string $expectedSha256): array
    {
        if ($path === '' || preg_match('/\A[a-f0-9]{64}\z/D', $expectedSha256) !== 1 || ! is_file($path) || ! is_readable($path)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-001');
        }
        $contents = file_get_contents($path);
        if (! is_string($contents) || $contents === '' || strlen($contents) > 1_048_576
            || ! hash_equals($expectedSha256, hash('sha256', $contents))) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-INTEGRITY-001');
        }
        try {
            $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SHAPE-001');
        }
        if (! is_array($manifest) || array_keys($manifest) !== ['manifest_version', 'references']
            || ! is_string($manifest['manifest_version']) || $manifest['manifest_version'] === ''
            || ! is_array($manifest['references']) || $manifest['references'] === []) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SHAPE-001');
        }
        $forbidden = ['key', 'secret', 'password', 'credential', 'private_key', 'key_material'];
        foreach ($manifest['references'] as $reference) {
            if (! is_array($reference)) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SHAPE-001');
            }
            foreach ($reference as $field => $value) {
                if (! is_string($field) || (in_array(strtolower($field), $forbidden, true) && $field !== 'secret_reference')) {
                    throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SECRET-001');
                }
                if (is_array($value)) {
                    foreach ($value as $nested) {
                        if (! is_string($nested)) {
                            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SHAPE-001');
                        }
                    }
                } elseif (! is_string($value) && ! is_bool($value) && $value !== null) {
                    throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-SHAPE-001');
                }
            }
            $secretReference = $reference['secret_reference'] ?? null;
            if (! is_string($secretReference)
                || preg_match('#\A(?:env|secret|vault|kms)://[A-Za-z0-9._/-]+\z#D', $secretReference) !== 1) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-MANIFEST-REFERENCE-001');
            }
        }

        return array_values($manifest['references']);
    }
}
