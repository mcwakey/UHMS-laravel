<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorConsultationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'default_consultation_specialty_profile_id',
        'default_department_id',
        'pinned_actions',
        'preferred_layout',
        'compact_mode',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'pinned_actions' => 'array',
            'compact_mode' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultSpecialtyProfile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'default_consultation_specialty_profile_id');
    }

    public function defaultDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'default_department_id');
    }
}
