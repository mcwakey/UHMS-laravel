<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternitySummaryProjection as Projection;
use App\Enums\LogModule;
use App\Models\ConsultationCompletionOccurrence;
use App\Models\ConsultationMaternitySnapshot;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Phase 14R.6 — captures and reads immutable maternity completion snapshots.
 *
 * Insert-only. There is no update or delete path here, and the model rejects
 * both anyway. A correction is a NEW version, produced by reopening the
 * consultation, fixing the maternity record and recompleting.
 *
 * Capture runs INSIDE the caller's completion transaction (see
 * ConsultationRouteService::completeRoute), so a completion failure rolls the
 * snapshot back and a snapshot failure rolls the completion back.
 */
class ConsultationMaternitySnapshotService
{
    public function __construct(
        private readonly ConsultationMaternitySummaryService $summaries,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('consultation.maternity_context.completion_snapshot_enabled', false);
    }

    /* ── Reads ─────────────────────────────────────────────────────────── */

    public function latestFor(VisitConsultationRoute $consultation): ?ConsultationMaternitySnapshot
    {
        return ConsultationMaternitySnapshot::query()
            ->forConsultation($consultation)
            ->latestVersionFirst()
            ->first();
    }

    /** @return Collection<int, ConsultationMaternitySnapshot> */
    public function historyFor(VisitConsultationRoute $consultation): Collection
    {
        return ConsultationMaternitySnapshot::query()
            ->forConsultation($consultation)
            ->with('capturedBy:id,first_name,last_name')
            ->latestVersionFirst()
            ->get();
    }

    /**
     * The completion OCCURRENCE token.
     *
     * Phase 14R.8: derived from the durable completion-occurrence ledger, NOT
     * from a timestamp. Two genuine completions in the same second have
     * different ULIDs and therefore different references — which is precisely
     * what closes risk P2.
     *
     * When no occurrence is supplied (a legacy caller, or a route completed
     * before this phase), the historical second-precision format is returned
     * unchanged so existing rows stay readable and comparable.
     */
    public function completionReference(
        VisitConsultationRoute $consultation,
        ?ConsultationCompletionOccurrence $occurrence = null,
    ): string {
        if ($occurrence) {
            return $occurrence->snapshotReference();
        }

        return $this->legacyCompletionReference($consultation);
    }

    /**
     * The pre-14R.8 reference format.
     *
     * Retained verbatim so snapshots captured before the occurrence ledger
     * existed keep matching their own reference. It is never used to mint a new
     * identity when an occurrence is available.
     */
    public function legacyCompletionReference(VisitConsultationRoute $consultation): string
    {
        $completedAt = $consultation->completed_at;

        return sprintf(
            'route:%d@%s',
            $consultation->id,
            $completedAt ? $completedAt->copy()->utc()->format('Y-m-d\TH:i:s\Z') : 'pending'
        );
    }

    /* ── Capture ───────────────────────────────────────────────────────── */

    /**
     * Capture the snapshot for this completion occurrence.
     *
     * Returns null — WITHOUT failing completion — when the feature is off or
     * there is no explicit valid context. Readiness is advisory in this phase,
     * so a missing or unconfirmed context must never block completion.
     *
     * Throws only on a genuine capture failure, which the caller's transaction
     * turns into a rolled-back completion rather than a completed consultation
     * with a half-written snapshot.
     */
    public function captureForCompletion(
        VisitConsultationRoute $consultation,
        ?User $actor = null,
        ?ConsultationCompletionOccurrence $occurrence = null,
    ): ?ConsultationMaternitySnapshot {
        if (! $this->enabled()) {
            return null;
        }

        // Snapshot capture has its OWN flag and builds the projection with
        // force: true — enabling the summary flag must not start writing
        // medico-legal history, and disabling it must not stop it.
        $projection = $this->summaries->project($consultation, force: true);

        if (! $projection->isSnapshotEligible()) {
            // Suggested / ambiguous / invalid / none: completion continues,
            // no snapshot is created, and nothing unconfirmed is recorded.
            return null;
        }

        $reference = $this->completionReference($consultation, $occurrence);

        // The caller holds the route lock; re-reading here is what makes a
        // repeated capture for the SAME occurrence idempotent. Two genuine
        // occurrences have different references and therefore never collide.
        $existing = ConsultationMaternitySnapshot::query()
            ->forConsultation($consultation)
            ->where('completion_reference', $reference)
            ->first();

        if ($existing) {
            return $existing;
        }

        $previous = $this->latestFor($consultation);
        $payload = $projection->toCanonicalArray();

        try {
            $snapshot = ConsultationMaternitySnapshot::create([
                'consultation_route_id' => $consultation->id,
                'pregnancy_profile_id' => $projection->pregnancyProfileId,
                'previous_snapshot_id' => $previous?->id,
                'snapshot_version' => ($previous?->snapshot_version ?? 0) + 1,
                'schema_version' => $projection->schemaVersion,
                'context_status' => $projection->contextStatus,
                'resolution_source' => $projection->resolutionSource,
                'completion_reference' => $reference,
                // Lineage only. The hash is computed over the clinical payload
                // alone, so this column does not participate in it.
                'completion_occurrence_id' => $occurrence?->id,
                'source_record_ids' => $projection->sourceRecordIds,
                'payload' => $payload,
                'payload_hash' => ConsultationMaternitySnapshot::hashPayload($payload),
                'captured_by' => $actor?->id,
                'captured_at' => now(),
                'metadata' => array_filter([
                    'newborn_count' => $projection->newbornCount(),
                    'visit_id' => $consultation->visit_id,
                    'occurrence_number' => $occurrence?->occurrence_number,
                ], fn ($value) => $value !== null),
            ]);
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            // Concurrent completion: the unique index on
            // (route, completion_reference) is the final guard. Return the
            // winner rather than creating a second version.
            $winner = ConsultationMaternitySnapshot::query()
                ->forConsultation($consultation)
                ->where('completion_reference', $reference)
                ->first();

            if (! $winner) {
                throw $e;
            }

            return $winner;
        }

        $this->log($snapshot, $consultation, $actor, $previous !== null);

        return $snapshot;
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    private function isUniqueViolation(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }

    /** Identifier-only audit. The payload contents are never logged. */
    private function log(
        ConsultationMaternitySnapshot $snapshot,
        VisitConsultationRoute $consultation,
        ?User $actor,
        bool $isRecapture,
    ): void {
        $event = $isRecapture
            ? 'CONSULTATION_MATERNITY_SNAPSHOT_RECAPTURED'
            : 'CONSULTATION_MATERNITY_SNAPSHOT_CAPTURED';

        $this->activityLog->log(
            LogModule::CONSULTATION,
            $event,
            [
                'patient_id' => $consultation->patient_id,
                'visit_id' => $consultation->visit_id,
                'causer' => $actor,
                'metadata' => array_filter([
                    'consultation_route_id' => $consultation->id,
                    'snapshot_id' => $snapshot->id,
                    'snapshot_version' => $snapshot->snapshot_version,
                    'pregnancy_profile_id' => $snapshot->pregnancy_profile_id,
                    'source_record_ids' => $snapshot->source_record_ids,
                    'payload_hash' => $snapshot->payload_hash,
                    'completion_reference' => $snapshot->completion_reference,
                    'schema_version' => $snapshot->schema_version,
                ], fn ($value) => $value !== null),
            ],
            $snapshot,
            $event,
        );
    }
}
