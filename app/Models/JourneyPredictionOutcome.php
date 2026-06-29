<?php

namespace App\Models;

use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Prediction-vs-actual outcome (Phase 9.10). Aggregate accuracy measurement — the
 * handoff identity is hashed; no patient-identifiable display data is stored.
 */
class JourneyPredictionOutcome extends Model
{
    protected $fillable = [
        'prediction_date', 'prediction_hour', 'handoff_identity_hash', 'visit_id',
        'from_department_id', 'from_department_type', 'to_department_id', 'to_department_type', 'cause',
        'predicted_risk_level', 'predicted_risk_score', 'predicted_minutes_to_breach', 'predicted_remaining_minutes', 'confidence',
        'actual_breached', 'actual_critical_breached', 'actual_resolved',
        'actual_minutes_to_breach', 'actual_minutes_to_resolve', 'actual_time_to_acknowledge', 'actual_time_to_resolve',
        'evaluated_at',
    ];

    protected $casts = [
        'prediction_hour' => 'integer',
        'cause' => JourneyDelayCause::class,
        'predicted_risk_level' => JourneyRiskLevel::class,
        'actual_breached' => 'boolean',
        'actual_critical_breached' => 'boolean',
        'actual_resolved' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    /** Did this prediction call a breach (HIGH/CRITICAL)? */
    public function predictedBreach(): bool
    {
        return in_array($this->predicted_risk_level, [JourneyRiskLevel::HIGH, JourneyRiskLevel::CRITICAL], true);
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereDate('prediction_date', '>=', $from)->whereDate('prediction_date', '<=', $to);
    }

    public function scopeForCause(Builder $query, string $cause): Builder
    {
        return $query->where('cause', $cause);
    }

    public function scopeFromDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('from_department_id', $departmentId);
    }

    public function scopeToDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('to_department_id', $departmentId);
    }

    public function scopeForRiskLevel(Builder $query, string $level): Builder
    {
        return $query->where('predicted_risk_level', $level);
    }

    public function scopeForConfidence(Builder $query, string $confidence): Builder
    {
        return $query->where('confidence', $confidence);
    }

    public function scopeEvaluated(Builder $query): Builder
    {
        return $query->whereNotNull('evaluated_at');
    }

    public function scopeUnevaluated(Builder $query): Builder
    {
        return $query->whereNull('evaluated_at');
    }
}
