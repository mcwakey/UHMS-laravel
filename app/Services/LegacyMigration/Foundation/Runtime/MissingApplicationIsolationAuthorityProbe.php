<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class MissingApplicationIsolationAuthorityProbe implements ApplicationIsolationAuthorityProbe
{
    public function observe(): ApplicationIsolationAuthorityEvidence
    {
        throw new RuntimeIsolationException('FOUNDATION_ISOLATION_AUTHORITY_PROBE_MISSING', 'The authoritative application isolation probe is missing.');
    }
}
