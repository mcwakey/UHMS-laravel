<?php

namespace App\Models;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Models\Concerns\HasMaternityContextTargets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.5 — clinical maternity context for an Admission Request.
 *
 * Kept strictly separate from `admission_requests.source_type/source_id`, which
 * continue to record the OPERATIONAL origin (emergency / consultation /
 * maternity / direct). A request may therefore be "raised by Emergency" and
 * "about this Labor Episode" at the same time, with neither fact overwriting
 * the other.
 */
class AdmissionRequestMaternityLink extends Model
{
    use HasMaternityContextTargets;

    public const ACTIVE_SLOT = 1;

    protected $fillable = [
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

    public function admissionRequest(): BelongsTo
    {
        return $this->belongsTo(AdmissionRequest::class);
    }

    public function scopeForAdmissionRequest($query, AdmissionRequest|int $request)
    {
        return $query->where(
            'admission_request_id',
            $request instanceof AdmissionRequest ? $request->id : $request
        );
    }
}
