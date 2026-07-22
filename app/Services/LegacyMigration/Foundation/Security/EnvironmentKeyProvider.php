<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use Closure;

final class EnvironmentKeyProvider implements KeyProvider
{
    /** @var array<string, array{id: string, version: string, environment_variable: string}> */
    private array $keys = [];

    /** @var Closure(string): (string|false|null) */
    private Closure $resolver;

    private string $activeReference;

    /**
     * @param array{
     *     active?: array{key_id?: mixed, version?: mixed},
     *     keys?: list<array{key_id?: mixed, version?: mixed, environment_variable?: mixed}>
     * } $configuration
     * @param  null|callable(string): (string|false|null)  $secretResolver
     */
    public function __construct(array $configuration, ?callable $secretResolver = null)
    {
        $this->resolver = $secretResolver === null
            ? static fn (string $name): string|false => getenv($name)
            : Closure::fromCallable($secretResolver);

        $active = $configuration['active'] ?? null;
        $configuredKeys = $configuration['keys'] ?? null;
        if (! is_array($active) || ! is_array($configuredKeys) || $configuredKeys === []) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-001');
        }

        $activeId = $active['key_id'] ?? null;
        $activeVersion = $active['version'] ?? null;
        if (! is_string($activeId) || ! is_string($activeVersion)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-002');
        }
        $this->activeReference = self::reference($activeId, $activeVersion);

        foreach ($configuredKeys as $key) {
            if (! is_array($key)
                || ! is_string($key['key_id'] ?? null)
                || ! is_string($key['version'] ?? null)
                || ! is_string($key['environment_variable'] ?? null)
                || $key['environment_variable'] === '') {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-003');
            }

            $reference = self::reference($key['key_id'], $key['version']);
            if (isset($this->keys[$reference])) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-004');
            }
            $this->keys[$reference] = [
                'id' => $key['key_id'],
                'version' => $key['version'],
                'environment_variable' => $key['environment_variable'],
            ];
        }

        if (! isset($this->keys[$this->activeReference])) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-005');
        }
    }

    public function active(): HmacKeyMaterial
    {
        $key = $this->keys[$this->activeReference];

        return $this->material($key);
    }

    public function get(string $keyId, string $version): HmacKeyMaterial
    {
        $reference = self::reference($keyId, $version);
        if (! isset($this->keys[$reference])) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-NOT-AVAILABLE-001');
        }

        return $this->material($this->keys[$reference]);
    }

    /** @param array{id: string, version: string, environment_variable: string} $key */
    private function material(array $key): HmacKeyMaterial
    {
        $configured = ($this->resolver)($key['environment_variable']);
        if (! is_string($configured) || $configured === '') {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-MISSING-001');
        }

        $secret = $configured;
        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded === false) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-ENCODING-001');
            }
            $secret = $decoded;
        }

        return new HmacKeyMaterial($key['id'], $key['version'], $secret);
    }

    private static function reference(string $keyId, string $version): string
    {
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,63}$/D', $keyId) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,31}$/D', $version) !== 1) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-001');
        }

        return $keyId."\0".$version;
    }
}
