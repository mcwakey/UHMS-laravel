<?php

namespace App\Console\Commands\LegacyMigration;

use Illuminate\Console\Command;

/** Caller-authored recovery booleans are permanently rejected. */
final class FoundationRecoveryAuditCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-recovery-audit
        {--input= : Deprecated caller-authored recovery evidence is not accepted}';

    protected $description = 'Reject unverified recovery evidence; authoritative observations are repository-derived';

    public function handle(): int
    {
        $this->components->error(
            'Blocked: caller-authored recovery evidence is not authoritative. Use the protected repository-bound recovery coordinator. No writes were performed.',
        );

        return self::FAILURE;
    }
}
