<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

/**
 * Typed proof that the exact application controls and deployment barriers were
 * authoritatively observed. The production capability can only be built by
 * running the verifier and re-observes the barriers at every runtime entry.
 */
final class ApplicationIsolationBootCapability
{
    /** @param list<SubsystemIsolationControl> $controls @param array<string,mixed> $configuration */
    private function __construct(
        private readonly ?ApplicationIsolationBootVerifier $verifier,
        private readonly array $controls,
        private readonly array $configuration,
        private readonly bool $testOnly,
    ) {}

    /** @param iterable<SubsystemIsolationControl> $controls @param array<string,mixed> $configuration */
    public static function fromVerifiedObservation(
        ApplicationIsolationBootVerifier $verifier,
        iterable $controls,
        array $configuration,
    ): self {
        $materialized = is_array($controls) ? array_values($controls) : iterator_to_array($controls, false);
        $verifier->verify($materialized, $configuration);

        return new self($verifier, $materialized, $configuration, false);
    }

    /** Test-only capability for isolated unit tests that have no application container. */
    public static function syntheticForTests(): self
    {
        if (! defined('PHPUNIT_COMPOSER_INSTALL') && ! defined('__PHPUNIT_PHAR__')) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_TEST_CAPABILITY_FORBIDDEN', 'A synthetic isolation capability is forbidden outside tests.');
        }

        return new self(null, [], [], true);
    }

    public function assertFresh(): void
    {
        if ($this->testOnly) {
            return;
        }
        if ($this->verifier === null) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CAPABILITY_INVALID', 'The application isolation capability has no verifier.');
        }
        $this->verifier->verify($this->controls, $this->configuration);
    }
}
