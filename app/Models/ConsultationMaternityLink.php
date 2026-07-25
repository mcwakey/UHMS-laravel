<?php

namespace App\Models;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.2 — link between a consultation encounter and a maternity record.
 *
 * Rows are written exclusively by ConsultationMaternityLinkService; controllers
 * must not build foreign-key combinations directly. `active_slot` is 1 for the
 * single active link per (consultation, context type) and NULL for history.
 */
class ConsultationMaternityLink extends Model
{
    /** Value stored in `active_slot` for the active link. */
    public const ACTIVE_SLOT = 1;

    protected $fillable = [
        'consultation_route_id',
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

    protected function casts(): array
    {
        return [
            'context_type' => ConsultationMaternityContextType::class,
            'link_role' => ConsultationMaternityLinkRole::class,
            'linked_at' => 'datetime',
            'unlinked_at' => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->whereNotNull('active_slot');
    }

    public function scopeHistorical($query)
    {
        return $query->whereNull('active_slot');
    }

    public function scopeForConsultation($query, VisitConsultationRoute|int $consultation)
    {
        return $query->where(
            'consultation_route_id',
            $consultation instanceof VisitConsultationRoute ? $consultation->id : $consultation
        );
    }

    public function scopeForContextType($query, ConsultationMaternityContextType|string $type)
    {
        return $query->where(
            'context_type',
            $type instanceof ConsultationMaternityContextType ? $type->value : $type
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->active_slot !== null;
    }

    /**
     * The maternity record this link points at, loaded through the relation
     * that matches its context type.
     */
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

    /** The longitudinal root for this link, when one is recorded. */
    public function rootPregnancyProfile(): ?PregnancyProfile
    {
        return $this->pregnancyProfile;
    }

    /** The id stored in this link's context-type foreign key. */
    public function targetId(): ?int
    {
        $key = $this->context_type?->foreignKey();

        return $key ? $this->{$key} : null;
    }
}
