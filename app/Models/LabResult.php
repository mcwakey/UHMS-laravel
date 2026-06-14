<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabResult extends Model
{
    protected $fillable = [
        'lab_request_item_id',
        'lab_request_id',
        'result_type',
        'result_value',
        'result_text',
        'result_file',
        'result_file_name',
        'is_abnormal',
        'remarks',
        'performed_by',
        'verified_by',
        'performed_at',
        'verified_at',
        // Configurable overall result (canonical storage)
        'overall_result_type',
        'overall_result_text',
        'overall_result_numeric',
        'overall_result_boolean',
        'overall_result_outcome',
        'overall_result_unit',
    ];

    protected $casts = [
        'result_type' => \App\Enums\ResultType::class,
        'is_abnormal' => 'boolean',
        'performed_at' => 'datetime',
        'verified_at' => 'datetime',
        'overall_result_numeric' => 'decimal:4',
        'overall_result_boolean' => 'boolean',
    ];

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'lab_request_item_id');
    }

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function values(): HasMany
    {
        return $this->hasMany(InvestigationResultValue::class, 'lab_result_id')->orderBy('sort_order');
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_by);
    }

    public function getDisplayResultAttribute(): string
    {
        return match ($this->result_type) {
            \App\Enums\ResultType::RICHTEXT   => $this->result_text ?? '',
            \App\Enums\ResultType::IMAGE,
            \App\Enums\ResultType::DOCUMENT   => $this->result_file_name ?? $this->result_file ?? '',
            default                           => $this->result_value ?? '',
        };
    }

    /**
     * Whether a typed/canonical overall result has been recorded.
     * Legacy rows (free-text only) return false and fall back to result_value.
     */
    public function hasTypedOverallResult(): bool
    {
        return in_array($this->overall_result_type, [
            \App\Models\ServiceCatalog::OVERALL_RESULT_NUMERIC,
            \App\Models\ServiceCatalog::OVERALL_RESULT_BOOLEAN,
            \App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE,
        ], true);
    }

    /**
     * Translated, human-readable overall result for display.
     *
     * Database values stay canonical (numeric / true|false / positive|negative);
     * only the displayed text is translated. Old free-text results continue to
     * display via the legacy result_value/result_text fallback.
     *
     * @param  \App\Models\ServiceCatalog|null  $service  optional, supplies custom labels/unit
     */
    public function overallResultDisplay(?ServiceCatalog $service = null): string
    {
        switch ($this->overall_result_type) {
            case ServiceCatalog::OVERALL_RESULT_NUMERIC:
                if ($this->overall_result_numeric === null) {
                    break;
                }
                $value = rtrim(rtrim(number_format((float) $this->overall_result_numeric, 4, '.', ''), '0'), '.');
                $unit = $this->overall_result_unit ?: $service?->overall_result_unit;

                return $unit ? "{$value} {$unit}" : $value;

            case ServiceCatalog::OVERALL_RESULT_BOOLEAN:
                if ($this->overall_result_boolean === null) {
                    break;
                }

                return $this->overall_result_boolean
                    ? ($service ? $service->trueLabel() : __('investigations.true'))
                    : ($service ? $service->falseLabel() : __('investigations.false'));

            case ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE:
                if ($this->overall_result_outcome === null || $this->overall_result_outcome === '') {
                    break;
                }

                return $this->overall_result_outcome === 'positive'
                    ? ($service ? $service->positiveLabel() : __('investigations.positive'))
                    : ($service ? $service->negativeLabel() : __('investigations.negative'));
        }

        // free_text or legacy: prefer the explicit overall text, then the legacy result value.
        return (string) ($this->overall_result_text ?? $this->result_value ?? $this->result_text ?? '');
    }
}
