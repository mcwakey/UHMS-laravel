<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

interface PhysicalServerIdentityObserver
{
    public function observe(): ObservedPhysicalServerIdentity;
}
