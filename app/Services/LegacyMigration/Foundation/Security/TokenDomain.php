<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use InvalidArgumentException;

final class TokenDomain
{
    public function __construct(private readonly string $value)
    {
        if (preg_match('/^[a-z][a-z0-9_-]{2,95}$/D', $this->value) !== 1) {
            throw new InvalidArgumentException('Protected token domain is invalid [LM-SEC-DOMAIN-001].');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
