<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalPatternItem extends Model
{
    protected $fillable = [
        'medical_pattern_id',
        'type',
        'data',
        'sort_order',
    ];

    protected $casts = [
        'data' => 'array',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function pattern(): BelongsTo
    {
        return $this->belongsTo(MedicalPattern::class, 'medical_pattern_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'complaint' => 'Complaint',
            'diagnosis' => 'Diagnosis',
            'treatment' => 'Treatment',
            'prescription_item' => 'Prescription Item',
            default => ucfirst($this->type),
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'complaint' => 'warning',
            'diagnosis' => 'info',
            'treatment' => 'success',
            'prescription_item' => 'primary',
            default => 'secondary',
        };
    }
}
