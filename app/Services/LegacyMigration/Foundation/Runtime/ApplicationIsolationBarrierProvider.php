<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

interface ApplicationIsolationBarrierProvider
{
    /** @param array<string,string> $expectedReferences */
    public function observe(array $expectedReferences): ApplicationIsolationAuthorityEvidence;
}
