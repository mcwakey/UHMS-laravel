<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'claim_workflow',
        'requires_claim_submission',
        'requires_verification_code',
        'verification_code_label',
        'requires_diagnosis',
        'requires_doctor',
        'default_claim_export_format',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_claim_submission' => 'boolean',
            'requires_verification_code' => 'boolean',
            'requires_diagnosis' => 'boolean',
            'requires_doctor' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function providers(): HasMany
    {
        return $this->hasMany(InsuranceProvider::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
