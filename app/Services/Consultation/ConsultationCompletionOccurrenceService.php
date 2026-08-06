<?php

namespace App\Services\Consultation;

use App\Models\ConsultationCompletionOccurrence;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Phase 14R.8 — allocates the authoritative identity of a completion
 * occurrence.
 *
 * The whole point: identity must NOT be derived from a clock. Two genuine
 * completions in the same second are two occurrences; one completion retried
 * twice is one occurrence.
 *
 * Called exactly once per genuine completion transition, from inside
 * `ConsultationRouteService::completeRoute()`'s transaction. It is deliberately
 * NOT called by the snapshot service — a snapshot consumes an occurrence, it
 * never mints one.
 */
class ConsultationCompletionOccurrenceService
{
    /**
     * Record the completion occurrence for a route that is transitioning to
     * COMPLETED.
     *
     * The caller must already hold the route row lock (the completion service
     * takes it), which is what makes the `occurrence_number` allocation safe
     * against concurrent completion requests. The unique index on
     * `(consultation_route_id, occurrence_number)` is the final guard.
     */
    public function record(
        VisitConsultationRoute $route,
        ?User $actor,
        ?string $fromStatus,
        array $metadata = [],
    ): ConsultationCompletionOccurrence {
        // A LOCKING read, deliberately. The caller's lock on the route row is
        // not enough: under InnoDB REPEATABLE READ a plain SELECT is served
        // from the transaction's read view, which was established at the
        // transaction's first consistent read — potentially before a competing
        // completion committed. A caller that had already read anything in the
        // same transaction (ConsultationNextPatientService::openNext does)
        // would then miss occurrence #1, allocate #1 again, and on conflict
        // re-read the same stale view — silently reintroducing P2.
        $previous = $this->lockedLatestFor($route);

        $attributes = [
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'occurrence_uid' => (string) Str::ulid(),
            'occurrence_number' => ($previous?->occurrence_number ?? 0) + 1,
            'previous_occurrence_id' => $previous?->id,
            'from_status' => $fromStatus,
            'to_status' => VisitConsultationRoute::STATUS_COMPLETED,
            // The timestamp is retained for humans and audit. It is no longer
            // the identity.
            'completed_at' => $route->completed_at ?? now(),
            'completed_by' => $actor?->id,
            'metadata' => $metadata === [] ? null : $metadata,
        ];

        try {
            return ConsultationCompletionOccurrence::create($attributes);
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            // A concurrent completion won the number. Re-read and verify the
            // lineage rather than reporting success blindly.
            //
            // The winner must be STRICTLY NEWER than what this call saw. A
            // route-scoped re-read alone would be tautological — it can only
            // ever return a row for this route — and would happily hand back
            // the PREVIOUS occurrence if the insert failed for some other
            // integrity reason, losing this completion silently.
            $winner = $this->lockedLatestFor($route);

            if (! $winner
                || (int) $winner->consultation_route_id !== (int) $route->id
                || (int) $winner->occurrence_number <= (int) ($previous?->occurrence_number ?? 0)) {
                throw $e;
            }

            return $winner;
        }
    }

    /**
     * The most recent occurrence, read with a row lock.
     *
     * Only for allocation inside a completion transaction — it forces a current
     * read rather than a read-view read, so the number it returns is the number
     * that is actually committed. Read paths must use `latestFor()`.
     */
    private function lockedLatestFor(VisitConsultationRoute $route): ?ConsultationCompletionOccurrence
    {
        return ConsultationCompletionOccurrence::query()
            ->forConsultation($route)
            ->latestOccurrenceFirst()
            ->lockForUpdate()
            ->first();
    }

    /** The most recent occurrence for a route, if any. */
    public function latestFor(VisitConsultationRoute $route): ?ConsultationCompletionOccurrence
    {
        return ConsultationCompletionOccurrence::query()
            ->forConsultation($route)
            ->latestOccurrenceFirst()
            ->first();
    }

    /**
     * The occurrence a COMPLETED route currently sits in.
     *
     * Used by recovery paths: a consultation that completed but whose snapshot
     * capture failed can resume against the same occurrence rather than
     * inventing a new one.
     *
     * Returns null for a COMPLETED route that never went through
     * completeRoute() — notably emergency dispositions, which complete the
     * route directly. Callers must handle null rather than assume it.
     */
    public function currentFor(VisitConsultationRoute $route): ?ConsultationCompletionOccurrence
    {
        if ($route->status !== VisitConsultationRoute::STATUS_COMPLETED) {
            return null;
        }

        return $this->latestFor($route);
    }

    /** @return \Illuminate\Support\Collection<int, ConsultationCompletionOccurrence> */
    public function historyFor(VisitConsultationRoute $route)
    {
        return ConsultationCompletionOccurrence::query()
            ->forConsultation($route)
            ->latestOccurrenceFirst()
            ->get();
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }
}
