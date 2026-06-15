<?php

namespace App\Console\Commands;

use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessHrAttendance extends Command
{
    protected $signature = 'hr:process-attendance {--date= : Attendance date (YYYY-MM-DD)}';
    protected $description = 'Process employee attendance, leave impact, shift rules and exceptions.';
    public function handle(AttendanceProcessingService $service): int
    {
        $records = $service->process(Carbon::parse($this->option('date') ?: today()));
        $this->info("Processed {$records->count()} attendance record(s).");
        return self::SUCCESS;
    }
}
