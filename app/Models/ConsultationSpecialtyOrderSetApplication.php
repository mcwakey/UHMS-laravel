<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationSpecialtyOrderSetApplication extends Model
{
    protected $fillable = [
        'consultation_id',
        'consultation_specialty_order_set_id',
        'consultation_specialty_profile_id',
        'applied_by',
        'status',
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

    public function orderSet(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyOrderSet::class, 'consultation_specialty_order_set_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyOrderSetApplicationItem::class);
    }
}
