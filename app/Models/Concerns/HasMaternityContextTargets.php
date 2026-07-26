<?php

namespace App\Models\Concerns;

use App\Enums\ConsultationMaternityContextType;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.5 — the target/audit half every maternity link table shares.
 *
 * `consultation_maternity_links` (14R.2) keeps its own hand-written copy so
 * that table's behaviour is provably untouched by this refactor; the three new
 * bridges (Emergency, Admission Request, Admission) all use this trait.
 *
 * The host model must declare `ACTIVE_SLOT`, its own source foreign key in
 * `$fillable`, and its own source relation + scope.
 */
trait HasMaternityContextTargets
{
    /** @return list<string> the target/audit columns every bridge shares */
    public static function maternityLinkColumns(): array
    {
        return [
            'pregnancy_profile_id',
            'maternity_case_id',
            'antenatal_visit_id',
            'labor_episode_id',
            'delivery_record_id',
            'newborn_record_id',
            'postnatal_case_id',
            'context_type',
            'link_role',
            'linked_by',
            'linked_at',
            'unlinked_by',
            'unlinked_at',
            'reason',
            'metadata',
            'active_slot',
        ];
    }

    /* ── Target relations ──────────────────────────────────────────────── */

    public function pregnancyProfile(): BelongsTo
    {
        return $this->belongsTo(PregnancyProfile::class);
    }

    public function maternityCase(): BelongsTo
    {
        return $this->belongsTo(MaternityCase::class);
    }

    public function antenatalVisit(): BelongsTo
    {
        return $this->belongsTo(AntenatalVisit::class);
    }

    public function laborEpisode(): BelongsTo
    {
        return $this->belongsTo(LaborEpisode::class);
    }

    public function deliveryRecord(): BelongsTo
    {
        return $this->belongsTo(DeliveryRecord::class);
    }

    public function newbornRecord(): BelongsTo
    {
        return $this->belongsTo(NewbornRecord::class);
    }

    public function postnatalCase(): BelongsTo
    {
        return $this->belongsTo(PostnatalCase::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function unlinkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlinked_by');
    }

    /** The relations a resolver eager-loads to avoid N+1 on active links. */
    public static function targetRelations(): array
    {
        return [
            'pregnancyProfile', 'maternityCase', 'antenatalVisit',
            'laborEpisode', 'deliveryRecord', 'newbornRecord', 'postnatalCase',
        ];
    }

    /* ── Scopes ────────────────────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->whereNotNull('active_slot');
    }

    public function scopeHistorical($query)
    {
        return $query->whereNull('active_slot');
    }

    public function scopeForContextType($query, ConsultationMaternityContextType|string $type)
    {
        return $query->where(
            'context_type',
            $type instanceof ConsultationMaternityContextType ? $type->value : $type
        );
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    public function isActive(): bool
    {
        return $this->active_slot !== null;
    }

    /** The maternity record this link points at. */
    public function targetRecord(): ?Model
    {
        return match ($this->context_type) {
            ConsultationMaternityContextType::PREGNANCY_PROFILE => $this->pregnancyProfile,
            ConsultationMaternityContextType::MATERNITY_CASE => $this->maternityCase,
            ConsultationMaternityContextType::ANC_VISIT => $this->antenatalVisit,
            ConsultationMaternityContextType::LABOR => $this->laborEpisode,
            ConsultationMaternityContextType::DELIVERY => $this->deliveryRecord,
            ConsultationMaternityContextType::NEWBORN => $this->newbornRecord,
            ConsultationMaternityContextType::POSTNATAL => $this->postnatalCase,
            default => null,
        };
    }

    /** The id stored in this link's context-type foreign key. */
    public function targetId(): ?int
    {
        $key = $this->context_type?->foreignKey();

        return $key ? $this->{$key} : null;
    }
}
