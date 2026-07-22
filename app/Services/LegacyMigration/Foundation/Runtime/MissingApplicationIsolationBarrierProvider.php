<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class MissingApplicationIsolationBarrierProvider implements ApplicationIsolationBarrierProvider
{
    public function observe(array $expectedReferences): ApplicationIsolationAuthorityEvidence
    {
        throw new RuntimeIsolationException('FOUNDATION_ISOLATION_BARRIER_PROVIDER_MISSING', 'The deployment barrier provider is not bound.');
    }
}
