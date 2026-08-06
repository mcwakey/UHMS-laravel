<?php

namespace App\Models;

use App\Models\Concerns\ImmutableClinicalSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 14R.8 — one row per GENUINE consultation completion occurrence.
 *
 * This is the authoritative answer to "which completion is this?", replacing the
 * second-precision `route:{id}@{completed_at}` reference that caused P2.
 *
 * Written only by ConsultationCompletionOccurrenceService, inside the
 * completion transaction, under a route row lock. Append-only: it reuses the
 * same immutability concern as the maternity snapshot, so update and delete
 * fail closed.
 *
 * KNOWN COVERAGE LIMIT (documented, not fixed in 14R.8): the ledger records
 * completions that go through ConsultationRouteService::completeRoute(). It
 * does NOT cover EmergencySessionService, which completes a route directly via
 * forceFill() when a case is disposed. Those routes therefore have no
 * occurrence, and `currentFor()` returns null for them. This does not affect
 * P2 — the maternity snapshot is only ever captured in completeRoute() — but
 * any future recovery path built on this ledger must not assume every COMPLETED
 * route has one. Extending the ledger to emergency dispositions is out of
 * 14R.8's scope.
 */
class ConsultationCompletionOccurrence extends Model
{
    use ImmutableClinicalSnapshot;

    /** Append-only: created_at is stamped, updated_at does not exist. */
    public const UPDATED_AT = null;

    /** Prefix for the snapshot idempotency reference built from this row. */
    public const REFERENCE_PREFIX = 'occ:';

    protected $fillable = [
        'consultation_route_id',
        'visit_id',
        'occurrence_uid',
        'occurrence_number',
        'previous_occurrence_id',
        'from_status',
        'to_status',
        'completed_at',
        'completed_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_number' => 'integer',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function consultationRoute(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function previousOccurrence(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_occurrence_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function maternitySnapshots(): HasMany
    {
        return $this->hasMany(ConsultationMaternitySnapshot::class, 'completion_occurrence_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes & helpers
    |--------------------------------------------------------------------------
    */

    public function scopeForConsultation($query, VisitConsultationRoute|int $route)
    {
        return $query->where(
            'consultation_route_id',
            $route instanceof VisitConsultationRoute ? $route->id : $route
        );
    }

    public function scopeLatestOccurrenceFirst($query)
    {
        return $query->orderByDesc('occurrence_number');
    }

    /**
     * The snapshot idempotency reference for this occurrence.
     *
     * Format is deliberately distinguishable from the legacy
     * `route:{id}@{timestamp}` reference, so old and new rows can coexist and be
     * told apart without guessing.
     */
    public function snapshotReference(): string
    {
        return self::REFERENCE_PREFIX.$this->occurrence_uid;
    }

    /** True for the legacy, timestamp-derived reference format. */
    public static function isLegacyReference(?string $reference): bool
    {
        return $reference !== null
            && ! str_starts_with($reference, self::REFERENCE_PREFIX);
    }
}
