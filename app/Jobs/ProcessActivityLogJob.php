<?php

namespace App\Jobs;

use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Async fallback for ActivityLogService::log when AUDIT_LOG_ASYNC=true.
 * Re-enters ActivityLogService synchronously inside the queue worker.
 */
class ProcessActivityLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $module,
        public string $action,
        public array $data,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?int $causerId = null,
        public ?string $description = null,
    ) {}

    public function handle(ActivityLogService $logger): void
    {
        $subject = null;
        if ($this->subjectType && $this->subjectId) {
            $subject = ($this->subjectType)::find($this->subjectId);
        }
        $causer = $this->causerId ? \App\Models\User::find($this->causerId) : null;

        $data = $this->data;
        if ($causer) {
            $data['causer'] = $causer;
        }
        $data['_async_dispatched'] = true; // prevent re-queue loop

        $logger->log($this->module, $this->action, $data, $subject, $this->description);
    }
}
