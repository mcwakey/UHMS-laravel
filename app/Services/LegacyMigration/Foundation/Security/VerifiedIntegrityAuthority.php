<?php

namespace App\Services\LegacyMigration\Foundation\Security;

/** Capability supplied only by an independently verified envelope authority. */
interface VerifiedIntegrityAuthority
{
    public function assertVerified(): void;
}
