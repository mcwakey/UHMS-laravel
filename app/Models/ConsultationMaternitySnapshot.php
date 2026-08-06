<?php

namespace App\Models;

use App\Models\Concerns\ImmutableClinicalSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.6 — an immutable maternity snapshot taken at consultation
 * completion.
 *
 * Written only by ConsultationMaternitySnapshotService, inside the completion
 * transaction. There is no update route, no delete route and no edit UI; the
 * model rejects both at runtime (see ImmutableClinicalSnapshot).
 *
 * `payload_hash` is SHA-256 over the canonical payload. It is TAMPER EVIDENCE —
 * it shows the row no longer matches what was captured. It is not encryption
 * and, on its own, it does not make the record cryptographically
 * non-repudiable: anyone able to rewrite the row could recompute the hash.
 */
class ConsultationMaternitySnapshot extends Model
{
    use ImmutableClinicalSnapshot;

    /** Append-only: created_at is stamped, updated_at does not exist. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'consultation_route_id',
        'pregnancy_profile_id',
        'previous_snapshot_id',
        'completion_occurrence_id',
        'snapshot_version',
        'schema_version',
        'context_status',
        'resolution_source',
        'completion_reference',
        'source_record_ids',
        'payload',
        'payload_hash',
        'captured_by',
        'captured_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_version' => 'integer',
            'source_record_ids' => 'array',
            'payload' => 'array',
            'metadata' => 'array',
            'captured_at' => 'datetime',
            'created_at' => 'datetime',
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

    public function pregnancyProfile(): BelongsTo
    {
        return $this->belongsTo(PregnancyProfile::class);
    }

    public function previousSnapshot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_snapshot_id');
    }

    /**
     * Phase 14R.8 — the completion occurrence this snapshot belongs to.
     *
     * Nullable: snapshots captured before the occurrence ledger existed keep
     * their legacy timestamp reference and are deliberately NOT backfilled.
     */
    public function completionOccurrence(): BelongsTo
    {
        return $this->belongsTo(ConsultationCompletionOccurrence::class, 'completion_occurrence_id');
    }

    /** True when this row predates the Phase 14R.8 occurrence ledger. */
    public function usesLegacyCompletionReference(): bool
    {
        return $this->completion_occurrence_id === null
            && ConsultationCompletionOccurrence::isLegacyReference($this->completion_reference);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForConsultation($query, VisitConsultationRoute|int $consultation)
    {
        return $query->where(
            'consultation_route_id',
            $consultation instanceof VisitConsultationRoute ? $consultation->id : $consultation
        );
    }

    /** Newest version first — the default view for "the" snapshot. */
    public function scopeLatestVersionFirst($query)
    {
        return $query->orderByDesc('snapshot_version');
    }

    /*
    |--------------------------------------------------------------------------
    | Integrity
    |--------------------------------------------------------------------------
    */

    /**
     * Recompute the hash over the stored payload and compare it with the value
     * recorded at capture time.
     *
     * A mismatch means the row was altered after capture; it does NOT tell you
     * by whom or when.
     */
    public function verifyPayloadHash(): bool
    {
        return hash_equals(
            (string) $this->payload_hash,
            self::hashPayload($this->payload ?? [])
        );
    }

    /**
     * The one canonical hashing routine. Both capture and verification call it,
     * so they can never drift apart.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function hashPayload(array $payload): string
    {
        return hash('sha256', self::canonicalJson($payload));
    }

    /**
     * Canonical JSON: unescaped slashes and unicode so the byte sequence does
     * not depend on PHP's escaping defaults, and no pretty-printing.
     *
     * Key ordering is the projection's responsibility
     * (ConsultationMaternitySummaryProjection::toCanonicalArray), which sorts
     * associative keys recursively while preserving list order.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function canonicalJson(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        ) ?: '{}';
    }
}
