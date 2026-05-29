<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class ArchiveActivityLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $activityId) {}

    public function handle(): void
    {
        $disk = (string) config('audit_streaming.archive.disk', 's3');
        $base = trim((string) config('audit_streaming.archive.path', 'uhms-audit'), '/');

        $activity = Activity::find($this->activityId);
        if (! $activity) {
            return;
        }

        $when = $activity->created_at ?? now();
        $path = sprintf('%s/%s/%s.jsonl', $base, $when->format('Y/m/d'), $activity->id);

        try {
            Storage::disk($disk)->append($path, json_encode($activity->toArray(), JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::channel(config('logging.activity_failures_channel', 'activity_failures'))
                ->warning('Archive write failed', ['activity_id' => $this->activityId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
