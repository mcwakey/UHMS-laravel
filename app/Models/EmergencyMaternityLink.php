<?php

namespace App\Models;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Models\Concerns\HasMaternityContextTargets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 14R.5 — link between an Emergency case and a maternity record.
 *
 * Written exclusively by EmergencyMaternityLinkService. Emergency keeps
 * ownership of triage, bay, vitals, notes, treatment and disposition; this row
 * only says "this emergency episode concerns that pregnancy record".
 */
class EmergencyMaternityLink extends Model
{
    use HasMaternityContextTargets;

    public const ACTIVE_SLOT = 1;

    protected $fillable = [
        'emergency_case_id',
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

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function scopeForEmergencyCase($query, EmergencyCase|int $case)
    {
        return $query->where(
            'emergency_case_id',
            $case instanceof EmergencyCase ? $case->id : $case
        );
    }
}
