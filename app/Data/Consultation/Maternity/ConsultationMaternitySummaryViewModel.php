<?php

namespace App\Data\Consultation\Maternity;

use App\Models\ConsultationMaternitySnapshot;
use Illuminate\Support\Collection;

/**
 * Phase 14R.6.1 — everything the maternity summary/snapshot UI needs, resolved
 * server-side.
 *
 * The one rule this model exists to enforce: **an active consultation shows
 * current maternity truth; a completed consultation shows what was true at
 * completion.** `mode` is the single switch, and Blade never decides it.
 *
 * Read-only throughout — nothing here can write, and there is no path from this
 * model to a snapshot mutation.
 */
final class ConsultationMaternitySummaryViewModel
{
    /** Active/reopened consultation: render the live projection. */
    public const MODE_LIVE = 'live';

    /** Completed consultation with a snapshot: render that snapshot. */
    public const MODE_COMPLETION_SNAPSHOT = 'completion_snapshot';

    /** Completed consultation, no snapshot: say so; never fabricate one. */
    public const MODE_NO_SNAPSHOT = 'no_snapshot';

    /** Flag off, no permission, or nothing to show. */
    public const MODE_UNAVAILABLE = 'unavailable';

    public const INTEGRITY_VERIFIED = 'verified';
    public const INTEGRITY_MISMATCH = 'mismatch';
    public const INTEGRITY_UNAVAILABLE = 'unavailable';

    /**
     * @param  array<string, mixed>  $livePayload  canonical projection array
     * @param  array<string, mixed>  $snapshotPayload  canonical snapshot payload
     * @param  Collection<int, array<string, mixed>>|null  $history  bounded metadata rows
     * @param  list<string>  $warnings  already-localised messages
     * @param  array<string, ?string>  $routes  internal navigation URLs
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly string $mode,
        public readonly ?int $consultationRouteId = null,
        public readonly ?string $consultationStatus = null,
        public readonly bool $isCompleted = false,
        public readonly bool $isReopened = false,
        public readonly array $livePayload = [],
        public readonly ?ConsultationMaternitySnapshot $selectedSnapshot = null,
        public readonly ?ConsultationMaternitySnapshot $latestSnapshot = null,
        public readonly array $snapshotPayload = [],
        public readonly ?Collection $history = null,
        public readonly ?int $snapshotVersion = null,
        public readonly ?string $schemaVersion = null,
        public readonly ?string $capturedAt = null,
        public readonly ?string $capturedBy = null,
        public readonly ?int $pregnancyProfileId = null,
        public readonly string $integrityState = self::INTEGRITY_UNAVAILABLE,
        public readonly ?int $previousSnapshotVersion = null,
        public readonly ?int $nextSnapshotVersion = null,
        public readonly bool $currentRecordAvailable = false,
        public readonly bool $mayViewCurrentRecord = false,
        public readonly array $warnings = [],
        public readonly bool $printMode = false,
        public readonly array $routes = [],
        public readonly ?string $contextStatus = null,
        public readonly bool $isGynaecology = false,
    ) {}

    /** Flag off / no permission: render absolutely nothing. */
    public static function unavailable(): self
    {
        return new self(enabled: false, mode: self::MODE_UNAVAILABLE);
    }

    public function shouldRender(): bool
    {
        return $this->enabled && $this->mode !== self::MODE_UNAVAILABLE;
    }

    public function isLive(): bool
    {
        return $this->mode === self::MODE_LIVE;
    }

    public function isSnapshot(): bool
    {
        return $this->mode === self::MODE_COMPLETION_SNAPSHOT;
    }

    public function isMissingSnapshot(): bool
    {
        return $this->mode === self::MODE_NO_SNAPSHOT;
    }

    /**
     * The payload the default view renders.
     *
     * In snapshot mode this is the STORED payload — the completed summary never
     * silently re-reads live maternity data for its default values.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->isSnapshot() ? $this->snapshotPayload : $this->livePayload;
    }

    public function hasClinicalContent(): bool
    {
        return ($this->payload()['pregnancy_profile_id'] ?? null) !== null;
    }

    public function integrityVerified(): bool
    {
        return $this->integrityState === self::INTEGRITY_VERIFIED;
    }

    public function integrityMismatch(): bool
    {
        return $this->integrityState === self::INTEGRITY_MISMATCH;
    }

    /**
     * A short, non-secret reference for display. The full hash is not a secret
     * either, but an abbreviation keeps clinical screens readable.
     */
    public function shortHash(): ?string
    {
        $hash = $this->selectedSnapshot?->payload_hash;

        return $hash ? substr((string) $hash, 0, 12) : null;
    }

    public function hasHistory(): bool
    {
        return ($this->history?->count() ?? 0) > 0;
    }

    public function route(string $key): ?string
    {
        return $this->routes[$key] ?? null;
    }

    /** @return array<string, mixed> identifier-only shape for tests */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'mode' => $this->mode,
            'consultation_route_id' => $this->consultationRouteId,
            'is_completed' => $this->isCompleted,
            'is_reopened' => $this->isReopened,
            'snapshot_version' => $this->snapshotVersion,
            'schema_version' => $this->schemaVersion,
            'integrity_state' => $this->integrityState,
            'pregnancy_profile_id' => $this->pregnancyProfileId,
            'history_versions' => $this->history?->pluck('version')->all() ?? [],
            'current_record_available' => $this->currentRecordAvailable,
            'may_view_current_record' => $this->mayViewCurrentRecord,
            'context_status' => $this->contextStatus,
        ];
    }
}
