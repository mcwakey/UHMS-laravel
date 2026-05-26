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
            'history_of_presenting_complaint', 'hopc' => 'History of Presenting Complaint',
            'examination', 'physical_examination' => 'Examination',
            'diagnosis' => 'Diagnosis',
            'investigation' => 'Investigation',
            'treatment' => 'Treatment',
            'prescription_item', 'prescription' => 'Prescription Item',
            'procedure' => 'Procedure',
            'task' => 'Task',
            'follow_up' => 'Follow-up',
            'note' => 'Note',
            default => ucfirst($this->type),
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'complaint' => 'warning',
            'history_of_presenting_complaint', 'hopc' => 'warning',
            'examination', 'physical_examination' => 'secondary',
            'diagnosis' => 'info',
            'investigation' => 'info',
            'treatment' => 'success',
            'prescription_item', 'prescription' => 'primary',
            'procedure' => 'danger',
            'task', 'follow_up' => 'dark',
            'note' => 'secondary',
            default => 'secondary',
        };
    }
}
