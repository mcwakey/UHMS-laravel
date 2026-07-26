<?php

namespace App\Data\Consultation\Maternity;

/**
 * Phase 14R.6 — the curated, typed maternity projection used by the
 * consultation summary AND by the immutable completion snapshot.
 *
 * One shape serves both so a completed summary and its snapshot can never
 * disagree about what "the maternity context" meant.
 *
 * Deliberately EXCLUDED (approved matrix): full ANC assessment/plan, full labor
 * notes, full delivery notes, postnatal observation values, STI/sexual history,
 * HIV and other specially protected fields, long clinical narrative, billing,
 * pharmacy and stock data.
 *
 * Values are typed codes and scalars. Translated labels are never stored as
 * source data — the renderer localises codes at display time.
 */
final class ConsultationMaternitySummaryProjection
{
    /** Bump when the payload SHAPE changes. Old snapshots keep their version. */
    public const SCHEMA_VERSION = '14R.6.1';

    public const STATUS_EXPLICIT = 'explicit';
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_NONE = 'none';

    /**
     * @param  array<string, mixed>  $pregnancy
     * @param  array<string, mixed>  $anc
     * @param  array<string, mixed>  $labor
     * @param  array<string, mixed>  $delivery
     * @param  list<array<string, mixed>>  $newborns
     * @param  array<string, mixed>  $postnatal
     * @param  array<string, mixed>  $admission
     * @param  array<string, mixed>  $sourceRecordIds
     * @param  list<string>  $warnings  localisation KEYS, never rendered text
     */
    public function __construct(
        public readonly string $contextStatus,
        public readonly string $resolutionSource,
        public readonly ?int $consultationRouteId = null,
        public readonly ?int $pregnancyProfileId = null,
        public readonly array $pregnancy = [],
        public readonly array $anc = [],
        public readonly array $labor = [],
        public readonly array $delivery = [],
        public readonly array $newborns = [],
        public readonly array $postnatal = [],
        public readonly array $admission = [],
        public readonly array $sourceRecordIds = [],
        public readonly array $warnings = [],
        public readonly string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    public static function none(?int $consultationRouteId = null, string $resolutionSource = 'none'): self
    {
        return new self(
            contextStatus: self::STATUS_NONE,
            resolutionSource: $resolutionSource,
            consultationRouteId: $consultationRouteId,
        );
    }

    /**
     * Context the clinician has NOT confirmed. Carries no clinical values —
     * only the advisory status — so a suggestion can never be summarised or
     * snapshotted as if it were confirmed.
     *
     * @param  list<string>  $warnings
     */
    public static function unconfirmed(
        string $status,
        string $resolutionSource,
        ?int $consultationRouteId = null,
        array $warnings = [],
    ): self {
        return new self(
            contextStatus: $status,
            resolutionSource: $resolutionSource,
            consultationRouteId: $consultationRouteId,
            warnings: $warnings,
        );
    }

    /** Only an EXPLICIT context may be summarised or snapshotted. */
    public function isExplicit(): bool
    {
        return $this->contextStatus === self::STATUS_EXPLICIT;
    }

    /** True when there is clinical content worth rendering. */
    public function hasClinicalContent(): bool
    {
        return $this->isExplicit() && $this->pregnancyProfileId !== null;
    }

    public function isSnapshotEligible(): bool
    {
        return $this->hasClinicalContent();
    }

    public function newbornCount(): int
    {
        return count($this->newborns);
    }

    /**
     * The canonical payload: stable key order, ISO-8601 dates, numeric values
     * kept numeric, enum values kept as codes. This exact array is what gets
     * hashed, so any reordering here would invalidate historical hashes.
     *
     * @return array<string, mixed>
     */
    public function toCanonicalArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'context_status' => $this->contextStatus,
            'resolution_source' => $this->resolutionSource,
            'consultation_route_id' => $this->consultationRouteId,
            'pregnancy_profile_id' => $this->pregnancyProfileId,
            'pregnancy' => $this->sortRecursive($this->pregnancy),
            'anc' => $this->sortRecursive($this->anc),
            'labor' => $this->sortRecursive($this->labor),
            'delivery' => $this->sortRecursive($this->delivery),
            // Newborn order is already deterministic (birth_order, then id);
            // only the KEYS inside each row are sorted, never the list itself.
            'newborns' => array_map(fn (array $row) => $this->sortRecursive($row), $this->newborns),
            'postnatal' => $this->sortRecursive($this->postnatal),
            'admission' => $this->sortRecursive($this->admission),
            'source_record_ids' => $this->sortRecursive($this->sourceRecordIds),
            'warnings' => $this->warnings,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->toCanonicalArray();
    }

    /**
     * Recursively sort associative keys so the payload is byte-stable
     * regardless of the order the builder happened to populate it in. Lists
     * (sequential integer keys) keep their order.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function sortRecursive(array $value): array
    {
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        if (! $isList) {
            ksort($value);
        }

        return $value;
    }
}
