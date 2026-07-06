<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSpecialtyBillingApplication extends Model
{
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_SKIPPED_DUPLICATE = 'skipped_duplicate';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNSUPPORTED = 'unsupported';

    protected $fillable = [
        'consultation_id',
        'consultation_specialty_profile_id',
        'consultation_specialty_service_mapping_id',
        'service_id',
        'invoice_id',
        'invoice_item_id',
        'applied_by',
        'status',
        'trigger',
        'preview_payload',
        'applied_payload',
        'warnings',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'preview_payload' => 'array',
            'applied_payload' => 'array',
            'warnings' => 'array',
            'metadata' => 'array',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyServiceMapping::class, 'consultation_specialty_service_mapping_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
