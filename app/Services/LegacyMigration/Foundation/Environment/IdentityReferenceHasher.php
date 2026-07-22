<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

interface IdentityReferenceHasher
{
    public function reference(string $field, string $value): string;
}
