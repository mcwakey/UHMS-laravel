<?php

namespace App\Listeners\Audit;

use App\Jobs\ArchiveActivityLogJob;
use App\Jobs\ForwardActivityToSiemJob;
use App\Jobs\ForwardActivityToSlackJob;
use Spatie\Activitylog\Models\Activity;

/**
 * Fires when any activity row is saved. Forwards CRITICAL/SECURITY severity
 * entries to optional external sinks (Slack / SIEM / S3 archive) controlled
 * by config('audit_streaming.*'). Each sink is queued so an outage in any
 * external system does not block the originating request.
 */
class ForwardCriticalActivityListener
{
    public function handle(...$args): void
    {
        if (! config('audit_streaming.enabled', false)) {
            return;
        }

        // Supports both wildcard form (eventName, [activity]) and direct model
        // dispatch (Activity) from `eloquent.saved: …`.
        $activity = null;
        foreach ($args as $arg) {
            if ($arg instanceof Activity) { $activity = $arg; break; }
            if (is_array($arg) && isset($arg[0]) && $arg[0] instanceof Activity) {
                $activity = $arg[0];
                break;
            }
        }
        if (! $activity instanceof Activity) {
            return;
        }

        $props = $activity->properties instanceof \Illuminate\Support\Collection
            ? $activity->properties->toArray()
            : (array) $activity->properties;

        $severity = strtoupper((string) ($props['severity'] ?? 'INFO'));
        $min = strtoupper((string) config('audit_streaming.min_severity', 'CRITICAL'));

        $rank = ['DEBUG'=>0,'INFO'=>1,'NOTICE'=>2,'WARNING'=>3,'ERROR'=>4,'CRITICAL'=>5,'SECURITY'=>5];
        if (($rank[$severity] ?? 1) < ($rank[$min] ?? 5)) {
            return;
        }

        $queue = (string) config('audit_streaming.queue', 'default');
        $id = $activity->id;

        if (config('audit_streaming.slack.enabled')) {
            ForwardActivityToSlackJob::dispatch($id)->onQueue($queue);
        }
        if (config('audit_streaming.siem.enabled')) {
            ForwardActivityToSiemJob::dispatch($id)->onQueue($queue);
        }
        if (config('audit_streaming.archive.enabled')) {
            ArchiveActivityLogJob::dispatch($id)->onQueue($queue);
        }
    }
}
