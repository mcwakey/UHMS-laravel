<?php

namespace App\Support\Integrations;

/**
 * Normalised result of a provider connectivity/credential test.
 */
class ProviderTestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly array $meta = [],
    ) {}

    public static function pass(string $message = 'OK', array $meta = []): self
    {
        return new self(true, $message, $meta);
    }

    public static function fail(string $message, array $meta = []): self
    {
        return new self(false, $message, $meta);
    }

    public function status(): string
    {
        return $this->success ? 'passed' : 'failed';
    }
}
