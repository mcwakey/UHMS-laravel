<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ConfiguredKeyProvider implements KeyProvider
{
    /** @var array<string, HmacKeyMaterial> */
    private array $keys = [];

    private string $activeReference;

    /**
     * Accepts the exact `legacy-migration.hmac` configuration shape. Secret
     * values must be injected by the environment-backed Laravel config file.
     *
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(#[\SensitiveParameter] array $configuration)
    {
        if (($configuration['algorithm'] ?? null) !== 'sha256') {
            throw SecurityConfigurationException::forCode('LM-SEC-HMAC-ALGORITHM-001');
        }

        $active = $this->material(
            $configuration['key_id'] ?? null,
            $configuration['key_version'] ?? null,
            $configuration['key'] ?? null,
        );
        $this->activeReference = self::reference($active->keyId(), $active->version());
        $this->keys[$this->activeReference] = $active;

        $previous = $configuration['previous_keys'] ?? [];
        if (! is_array($previous)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-006');
        }
        foreach ($previous as $configured) {
            if (! is_array($configured)) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-006');
            }
            $key = $this->material(
                $configured['key_id'] ?? null,
                $configured['key_version'] ?? null,
                $configured['key'] ?? null,
            );
            $reference = self::reference($key->keyId(), $key->version());
            if (isset($this->keys[$reference])) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-004');
            }
            $this->keys[$reference] = $key;
        }
    }

    public function active(): HmacKeyMaterial
    {
        return $this->keys[$this->activeReference];
    }

    public function get(string $keyId, string $version): HmacKeyMaterial
    {
        $reference = self::reference($keyId, $version);
        if (! isset($this->keys[$reference])) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-NOT-AVAILABLE-001');
        }

        return $this->keys[$reference];
    }

    private function material(mixed $id, mixed $version, #[\SensitiveParameter] mixed $secret): HmacKeyMaterial
    {
        if (! is_string($id) || ! is_string($version) || ! is_string($secret) || $secret === '') {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-MISSING-001');
        }

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded === false) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-ENCODING-001');
            }
            $secret = $decoded;
        }

        return new HmacKeyMaterial($id, $version, $secret);
    }

    private static function reference(string $keyId, string $version): string
    {
        return $keyId."\0".$version;
    }
}
