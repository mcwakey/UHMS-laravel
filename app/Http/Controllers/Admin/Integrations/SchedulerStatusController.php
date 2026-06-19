<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\Integrations\SchedulerStatusService;

class SchedulerStatusController extends Controller
{
    public function __construct(protected SchedulerStatusService $scheduler) {}

    public function index(ActivityLogService $logger)
    {
        $commands = $this->scheduler->all();
        $cron = '* * * * * php ' . base_path('artisan') . ' schedule:run >> /dev/null 2>&1';

        $logger->log(LogModule::INTEGRATIONS, 'INTEGRATION_SCHEDULER_STATUS_VIEWED', [], null, 'Integration scheduler status viewed');

        return view('admin.integrations.scheduler.index', compact('commands', 'cron'));
    }
}
