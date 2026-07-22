<?php

namespace App\Services\LegacyMigration\Foundation\Security;

interface KeyProvider
{
    public function active(): HmacKeyMaterial;

    public function get(string $keyId, string $version): HmacKeyMaterial;
}
