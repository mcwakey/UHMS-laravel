<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ForwardActivityToSiemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 60;

    public function __construct(public int $activityId) {}

    public function handle(): void
    {
        $endpoint = (string) config('audit_streaming.siem.endpoint');
        if ($endpoint === '') {
            return;
        }

        $activity = Activity::find($this->activityId);
        if (! $activity) {
            return;
        }

        try {
            Http::withToken((string) config('audit_streaming.siem.token', ''))
                ->asJson()
                ->post($endpoint, $activity->toArray())
                ->throw();
        } catch (\Throwable $e) {
            Log::channel(config('logging.activity_failures_channel', 'activity_failures'))
                ->warning('SIEM forwarding failed', ['activity_id' => $this->activityId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
