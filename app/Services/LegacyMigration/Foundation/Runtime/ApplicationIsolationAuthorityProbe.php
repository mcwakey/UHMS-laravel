<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

/** Reads authoritative external worker/scheduler/sink state; callers cannot substitute booleans. */
interface ApplicationIsolationAuthorityProbe
{
    public function observe(): ApplicationIsolationAuthorityEvidence;
}
