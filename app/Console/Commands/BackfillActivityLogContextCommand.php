<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\ActivityContextResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills patient_id / visit_id on existing activity_log rows so historical
 * activity appears on the patient timeline. Pulls the ids from each row's
 * `properties`, then (where missing) resolves them from the row's subject model.
 *
 *   php artisan logs:backfill-context
 *   php artisan logs:backfill-context --chunk=1000
 */
class BackfillActivityLogContextCommand extends Command
{
    protected $signature = 'logs:backfill-context {--chunk=500 : Rows per batch}';

    protected $description = 'Backfill patient_id/visit_id on existing activity_log rows for the patient timeline.';

    public function handle(ActivityContextResolver $resolver): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $updated = 0;
        $scanned = 0;

        ActivityLog::query()
            ->whereNull('patient_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($logs) use (&$updated, &$scanned, $resolver) {
                foreach ($logs as $log) {
                    $scanned++;
                    $props = $log->properties ?: collect();

                    $patientId = $props['patient_id'] ?? null;
                    $visitId = $props['visit_id'] ?? null;

                    if ($patientId === null || $visitId === null) {
                        $subject = $log->subject; // morphTo
                        if ($subject) {
                            $ctx = $resolver->resolve($subject, is_array($props) ? $props : $props->toArray());
                            $patientId ??= $ctx['patient_id'];
                            $visitId ??= $ctx['visit_id'];
                        }
                    }

                    if ($patientId !== null || $visitId !== null) {
                        DB::table('activity_log')->where('id', $log->id)->update([
                            'patient_id' => $patientId !== null ? (int) $patientId : null,
                            'visit_id' => $visitId !== null ? (int) $visitId : null,
                        ]);
                        $updated++;
                    }
                }
            });

        $this->info("Scanned {$scanned} row(s) without patient_id; backfilled {$updated}.");

        return self::SUCCESS;
    }
}
