<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternitySummaryViewModel as ViewModel;
use App\Models\ConsultationMaternitySnapshot;
use App\Models\User;
use App\Models\VisitConsultationRoute;

/**
 * Phase 14R.6.1 — READ-ONLY presentation layer for the maternity summary.
 *
 * Decides one thing and decides it server-side: which payload a given
 * consultation state should show.
 *
 *   active / reopened  → the LIVE projection      ("Current Maternity Record")
 *   completed + snapshot → the STORED snapshot    ("… at Consultation Completion")
 *   completed, none      → an honest no-snapshot statement
 *
 * A completed consultation NEVER silently loads live maternity data for its
 * default values, and a missing snapshot is never fabricated — not on page
 * load, and not by enabling the capture flag afterwards.
 *
 * This service writes nothing: no snapshot, no link, no maternity record, no
 * specialty entry, no billing. Blade consumes the view model and issues no
 * query of its own.
 */
class ConsultationMaternitySummaryPresentationService
{
    /** Bounded history: a consultation is never legitimately recompleted often. */
    private const HISTORY_LIMIT = 20;

    /** @var array<string, ViewModel> request-scoped memo */
    private array $memo = [];

    public function __construct(
        private readonly ConsultationMaternitySummaryService $summaries,
        private readonly ConsultationMaternitySnapshotService $snapshots,
    ) {}

    public function enabled(): bool
    {
        return $this->summaries->enabled();
    }

    /**
     * Build the view model.
     *
     * @param  int|null  $version  a specific snapshot version to display
     */
    public function build(
        ?VisitConsultationRoute $consultation,
        ?User $user,
        ?int $version = null,
        bool $printMode = false,
        bool $isGynaecology = false,
    ): ViewModel {
        // Flag off, no consultation, or no summary permission → nothing at all.
        // No projection query, no snapshot query, no history query.
        if (! $this->enabled()
            || ! $consultation
            || ! $user
            || ! $user->can('consultation.maternity_context.summary.view')) {
            return ViewModel::unavailable();
        }

        $key = $consultation->id.':'.($version ?? 'latest').':'.($printMode ? 'p' : 'v');

        return $this->memo[$key] ??= $this->resolve($consultation, $user, $version, $printMode, $isGynaecology);
    }

    /**
     * Load one snapshot version, scoped to its consultation.
     *
     * A snapshot belonging to another consultation is never returned, which is
     * what stops the history route being used to read across encounters.
     */
    public function snapshotVersion(VisitConsultationRoute $consultation, int $version): ?ConsultationMaternitySnapshot
    {
        return ConsultationMaternitySnapshot::query()
            ->forConsultation($consultation)
            ->where('snapshot_version', $version)
            ->first();
    }

    /**
     * The CURRENT live maternity record, for the explicit separate action on a
     * completed consultation.
     *
     * Loaded only when asked for — the ordinary completed page never pays this
     * cost — and only when the user holds the bridge permission and the
     * underlying maternity view permission on top of summary access.
     *
     * @return array<string, mixed>|null
     */
    public function currentRecord(VisitConsultationRoute $consultation, ?User $user): ?array
    {
        if (! $this->mayViewCurrentRecord($user)) {
            return null;
        }

        // force: true — the current-record view is governed by its own
        // permissions, and must work while the summary flag decides only
        // whether the SECTION exists.
        $projection = $this->summaries->project($consultation, force: true);

        // An unconfirmed context is never presented as current explicit truth.
        return $projection->isExplicit() ? $projection->toCanonicalArray() : null;
    }

    public function mayViewCurrentRecord(?User $user): bool
    {
        return $user !== null
            && $user->can('consultation.maternity_context.summary.view')
            && $user->can('consultation.maternity_context.view')
            && $user->can('maternity.pregnancy.view');
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    private function resolve(
        VisitConsultationRoute $consultation,
        User $user,
        ?int $version,
        bool $printMode,
        bool $isGynaecology,
    ): ViewModel {
        $isCompleted = $consultation->status === VisitConsultationRoute::STATUS_COMPLETED;
        $mayViewCurrent = $this->mayViewCurrentRecord($user);

        $routes = $this->routes($consultation);

        // ── Active or reopened: live projection. ────────────────────────
        if (! $isCompleted) {
            $projection = $this->summaries->project($consultation);
            // A reopened consultation is an ACTIVE one that already has
            // completion history — history stays reachable, but the live
            // record is what it shows.
            $history = $this->history($consultation);

            return new ViewModel(
                enabled: true,
                mode: ViewModel::MODE_LIVE,
                consultationRouteId: $consultation->id,
                consultationStatus: $consultation->status,
                isCompleted: false,
                isReopened: $history->isNotEmpty(),
                livePayload: $projection->toCanonicalArray(),
                latestSnapshot: null,
                history: $history,
                currentRecordAvailable: false,
                mayViewCurrentRecord: $mayViewCurrent,
                warnings: $this->warnings($projection->warnings),
                printMode: $printMode,
                routes: $routes,
                contextStatus: $projection->contextStatus,
                isGynaecology: $isGynaecology,
            );
        }

        // ── Completed: the snapshot, or an honest absence. ──────────────
        $history = $this->history($consultation);
        $latest = $this->snapshots->latestFor($consultation);

        $selected = $version !== null
            ? $this->snapshotVersion($consultation, $version)
            : $latest;

        if (! $selected) {
            // Completed with no snapshot — state it. Do NOT create one, and do
            // NOT show current values under a historical label.
            return new ViewModel(
                enabled: true,
                mode: ViewModel::MODE_NO_SNAPSHOT,
                consultationRouteId: $consultation->id,
                consultationStatus: $consultation->status,
                isCompleted: true,
                history: $history,
                currentRecordAvailable: $mayViewCurrent,
                mayViewCurrentRecord: $mayViewCurrent,
                printMode: $printMode,
                routes: $routes,
                isGynaecology: $isGynaecology,
            );
        }

        return new ViewModel(
            enabled: true,
            mode: ViewModel::MODE_COMPLETION_SNAPSHOT,
            consultationRouteId: $consultation->id,
            consultationStatus: $consultation->status,
            isCompleted: true,
            selectedSnapshot: $selected,
            latestSnapshot: $latest,
            snapshotPayload: $selected->payload ?? [],
            history: $history,
            snapshotVersion: (int) $selected->snapshot_version,
            schemaVersion: $selected->schema_version,
            capturedAt: $selected->captured_at?->format('d M Y H:i'),
            capturedBy: $this->actorName($selected),
            pregnancyProfileId: $selected->pregnancy_profile_id
                ? (int) $selected->pregnancy_profile_id
                : null,
            integrityState: $this->integrity($selected),
            previousSnapshotVersion: $this->neighbourVersion($history, (int) $selected->snapshot_version, -1),
            nextSnapshotVersion: $this->neighbourVersion($history, (int) $selected->snapshot_version, +1),
            currentRecordAvailable: $mayViewCurrent,
            mayViewCurrentRecord: $mayViewCurrent,
            printMode: $printMode,
            routes: $routes,
            contextStatus: $selected->context_status,
            isGynaecology: $isGynaecology,
        );
    }

    /**
     * Bounded history metadata. One query plus its eager-loaded actor; a
     * consultation with twenty versions costs the same as one with two.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function history(VisitConsultationRoute $consultation)
    {
        return ConsultationMaternitySnapshot::query()
            ->forConsultation($consultation)
            ->with('capturedBy:id,first_name,last_name')
            ->latestVersionFirst()
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (ConsultationMaternitySnapshot $snapshot) => [
                'id' => (int) $snapshot->id,
                'version' => (int) $snapshot->snapshot_version,
                'schema_version' => $snapshot->schema_version,
                'captured_at' => $snapshot->captured_at?->format('d M Y H:i'),
                'captured_by' => $this->actorName($snapshot),
                'integrity' => $this->integrity($snapshot),
                'short_hash' => substr((string) $snapshot->payload_hash, 0, 12),
            ]);
    }

    /**
     * Integrity is EVIDENCE, not repair. A mismatch is reported and nothing is
     * recalculated, regenerated or saved.
     */
    private function integrity(ConsultationMaternitySnapshot $snapshot): string
    {
        if (! is_array($snapshot->payload) || ! filled($snapshot->payload_hash)) {
            return ViewModel::INTEGRITY_UNAVAILABLE;
        }

        try {
            return $snapshot->verifyPayloadHash()
                ? ViewModel::INTEGRITY_VERIFIED
                : ViewModel::INTEGRITY_MISMATCH;
        } catch (\Throwable) {
            return ViewModel::INTEGRITY_UNAVAILABLE;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $history
     */
    private function neighbourVersion($history, int $current, int $direction): ?int
    {
        $versions = $history->pluck('version')->sort()->values();
        $index = $versions->search($current);

        if ($index === false) {
            return null;
        }

        return $versions->get($index + $direction);
    }

    private function actorName(ConsultationMaternitySnapshot $snapshot): ?string
    {
        $actor = $snapshot->capturedBy;

        return $actor ? trim($actor->first_name.' '.$actor->last_name) : null;
    }

    /**
     * Projection warnings arrive as localisation KEYS; resolve them here so the
     * Blade layer renders text and nothing else.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function warnings(array $keys): array
    {
        return array_values(array_map(fn (string $key) => __($key), $keys));
    }

    /** @return array<string, ?string> */
    private function routes(VisitConsultationRoute $consultation): array
    {
        return [
            'history' => $this->route('admin.consultations.maternity-summary.history', $consultation->visit_id),
            'current_record' => $this->route('admin.consultations.maternity-summary.current', $consultation->visit_id),
        ];
    }

    private function route(string $name, mixed $parameter): ?string
    {
        if ($parameter === null) {
            return null;
        }

        try {
            return route($name, ['visit' => $parameter]);
        } catch (\Throwable) {
            return null;
        }
    }
}
