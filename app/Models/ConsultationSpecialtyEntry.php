<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSpecialtyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'consultation_specialty_profile_id',
        'section_key',
        'entry',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
