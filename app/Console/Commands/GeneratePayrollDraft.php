<?php

namespace App\Console\Commands;

use App\Services\PayrollDraftService;
use Illuminate\Console\Command;

class GeneratePayrollDraft extends Command
{
    protected $signature = 'hr:generate-payroll-draft {--period= : Payroll period (YYYY-MM)}';
    protected $description = 'Generate an idempotent payroll draft from approved attendance.';
    public function handle(PayrollDraftService $service): int
    {
        $period = $this->option('period');
        if (! $period || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error('A valid --period=YYYY-MM is required.');
            return self::FAILURE;
        }
        $run = $service->generate($period);
        $this->info("Generated payroll draft {$run->id} with {$run->records->count()} record(s).");
        return self::SUCCESS;
    }
}
