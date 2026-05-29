<?php

namespace App\Console\Commands;

use App\Services\OutpatientSessionAutoCloseService;
use Illuminate\Console\Command;

class OutpatientSessionsAutoCompleteCommand extends Command
{
    protected $signature = 'outpatient-sessions:auto-complete';

    protected $description = 'Auto-complete and lock stale outpatient consultation sessions from previous days.';

    public function handle(OutpatientSessionAutoCloseService $service): int
    {
        $count = $service->closeStaleSessions();
        $this->info("Auto-completed {$count} outpatient session(s).");

        return self::SUCCESS;
    }
}
