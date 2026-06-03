<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * UHMS activity log — extends Spatie's Activity to add queryable patient_id /
 * visit_id columns (MariaDB 10.1 has no JSON_EXTRACT, so the patient timeline
 * cannot filter on the `properties` longText). Columns are mirrored from
 * properties on save so both the sync and async (queued) logging paths populate
 * them automatically.
 *
 * Wired in via config/activitylog.php → activity_model.
 */
class ActivityLog extends SpatieActivity
{
    protected static function booted(): void
    {
        static::saving(function (ActivityLog $log): void {
            $props = $log->properties; // Spatie casts this to a Collection

            if ($log->patient_id === null && $props && isset($props['patient_id']) && $props['patient_id'] !== null) {
                $log->patient_id = (int) $props['patient_id'];
            }
            if ($log->visit_id === null && $props && isset($props['visit_id']) && $props['visit_id'] !== null) {
                $log->visit_id = (int) $props['visit_id'];
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Logs belonging to a patient — by the indexed patient_id column OR by the
     * legacy subject-is-Patient linkage (so pre-backfill rows still surface).
     *
     * @param  array<int>  $patientIds  main patient + merged duplicate ids
     */
    public function scopeForPatient(Builder $query, array $patientIds): Builder
    {
        return $query->where(function (Builder $q) use ($patientIds) {
            $q->whereIn('patient_id', $patientIds)
                ->orWhere(function (Builder $q2) use ($patientIds) {
                    $q2->where('subject_type', Patient::class)
                        ->whereIn('subject_id', $patientIds);
                });
        });
    }
}
