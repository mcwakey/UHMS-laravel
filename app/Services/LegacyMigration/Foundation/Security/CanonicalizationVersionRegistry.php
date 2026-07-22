<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class CanonicalizationVersionRegistry
{
    public const TYPED_LENGTH_PREFIXED_UTF8_V1 = 'typed-length-prefix/1';

    /** @var array<string, true> */
    private array $supported;

    /** @param list<string> $supportedVersions */
    public function __construct(
        array $supportedVersions = [self::TYPED_LENGTH_PREFIXED_UTF8_V1],
        private readonly string $activeVersion = self::TYPED_LENGTH_PREFIXED_UTF8_V1,
    ) {
        $this->supported = [];
        foreach ($supportedVersions as $version) {
            if (! self::validVersion($version) || isset($this->supported[$version])) {
                throw SecurityConfigurationException::forCode('LM-SEC-CANON-REGISTRY-001');
            }
            $this->supported[$version] = true;
        }

        if (! isset($this->supported[$this->activeVersion])) {
            throw SecurityConfigurationException::forCode('LM-SEC-CANON-REGISTRY-002');
        }
    }

    public function activeVersion(): string
    {
        return $this->activeVersion;
    }

    public function assertSupported(string $version): void
    {
        if (! isset($this->supported[$version])) {
            throw SecurityConfigurationException::forCode('LM-SEC-CANON-VERSION-001');
        }
    }

    private static function validVersion(string $version): bool
    {
        return preg_match('#^[a-z][a-z0-9._/-]{2,63}$#D', $version) === 1;
    }
}
