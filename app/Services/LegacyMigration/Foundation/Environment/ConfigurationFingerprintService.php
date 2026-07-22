<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class ConfigurationFingerprintService
{
    /** @param array<string, mixed> $configuration */
    public function fingerprint(array $configuration): string
    {
        $this->assertNoSecretKeys($configuration);

        return hash('sha256', json_encode(
            $this->canonicalize($configuration),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }

    /** @param array<string, mixed> $value */
    private function assertNoSecretKeys(array $value): void
    {
        foreach ($value as $key => $item) {
            if (preg_match('/password|passwd|secret|credential|private.?key|dsn|host|username|user_name/i', (string) $key) === 1) {
                throw new FoundationGuardException('FOUNDATION_CONFIG_SECRET_REJECTED', 'Secret-bearing configuration cannot be fingerprinted by this service.');
            }
            if (is_array($item)) {
                $this->assertNoSecretKeys($item);
            }
        }
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
