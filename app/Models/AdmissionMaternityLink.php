<?php

namespace App\Models;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Models\Concerns\HasMaternityContextTargets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.5 — link between an Admission and a maternity record.
 *
 * This is the durable statement of "this admission is caring for that
 * pregnancy". `pregnancy_profiles.admission_id` is deliberately NOT forced to
 * follow it: one nullable column cannot represent a patient's second admission
 * without erasing the first.
 */
class AdmissionMaternityLink extends Model
{
    use HasMaternityContextTargets;

    public const ACTIVE_SLOT = 1;

    protected $fillable = [
        'admission_id',
        'admission_request_id',
        'pregnancy_profile_id', 'maternity_case_id', 'antenatal_visit_id',
        'labor_episode_id', 'delivery_record_id', 'newborn_record_id',
        'postnatal_case_id', 'context_type', 'link_role', 'linked_by',
        'linked_at', 'unlinked_by', 'unlinked_at', 'reason', 'metadata',
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

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /** Audit only — records where a propagated context came from. */
    public function admissionRequest(): BelongsTo
    {
        return $this->belongsTo(AdmissionRequest::class);
    }

    public function scopeForAdmission($query, Admission|int $admission)
    {
        return $query->where(
            'admission_id',
            $admission instanceof Admission ? $admission->id : $admission
        );
    }
}
