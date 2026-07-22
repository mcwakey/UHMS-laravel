<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use RuntimeException;

final class AllocationException extends RuntimeException
{
    public static function failClosed(string $code): self
    {
        return new self("Patient-number allocation stopped [{$code}].");
    }
}
