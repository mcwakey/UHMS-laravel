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

class ForwardActivityToSlackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $activityId) {}

    public function handle(): void
    {
        $url = (string) config('audit_streaming.slack.webhook_url');
        if ($url === '') {
            return;
        }

        $activity = Activity::find($this->activityId);
        if (! $activity) {
            return;
        }

        $props = (array) ($activity->properties?->toArray() ?? []);
        $module = $props['module'] ?? $activity->log_name;
        $severity = $props['severity'] ?? 'INFO';

        try {
            Http::asJson()->post($url, [
                'text' => sprintf('[%s] %s · %s', $severity, $module, (string) $activity->description),
                'attachments' => [[
                    'color' => $severity === 'SECURITY' ? '#d63384' : '#dc3545',
                    'fields' => [
                        ['title' => 'Action', 'value' => $props['action'] ?? $activity->event ?? '—', 'short' => true],
                        ['title' => 'Causer', 'value' => optional($activity->causer)->name ?? 'System', 'short' => true],
                        ['title' => 'Subject', 'value' => $activity->subject_type ? class_basename($activity->subject_type) . '#' . $activity->subject_id : '—', 'short' => true],
                        ['title' => 'When', 'value' => optional($activity->created_at)->toIso8601String(), 'short' => true],
                    ],
                ]],
            ])->throw();
        } catch (\Throwable $e) {
            Log::channel(config('logging.activity_failures_channel', 'activity_failures'))
                ->warning('Slack forwarding failed', ['activity_id' => $this->activityId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
