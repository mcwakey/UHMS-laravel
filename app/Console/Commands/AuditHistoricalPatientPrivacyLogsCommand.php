<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class AuditHistoricalPatientPrivacyLogsCommand extends Command
{
    protected $signature = 'patient-privacy:audit-historical-logs {--dry-run : Report only; never mutate logs}';

    protected $description = 'Dry-run audit for historical activity logs that may contain patient-sensitive fields.';

    public function handle(): int
    {
        $fields = ActivityLogService::SENSITIVE_FIELDS;

        $query = ActivityLog::query()->where(function ($query) use ($fields) {
            foreach ($fields as $field) {
                $query->orWhere('properties', 'like', '%'.$field.'%');
            }
        });

        $count = (clone $query)->count();

        $this->info('Historical patient privacy log audit');
        $this->line('Mode: dry-run');
        $this->line('Potentially affected logs: '.$count);
        $this->line('Fields scanned: '.implode(', ', $fields));
        $this->newLine();
        $this->warn('No records were modified. Destructive sanitisation requires a separate approved command.');

        return self::SUCCESS;
    }
}
