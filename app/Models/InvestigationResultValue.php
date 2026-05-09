<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationResultValue extends Model
{
    protected $fillable = [
        'lab_result_id',
        'criteria_id',
        'name',
        'value',
        'unit',
        'reference_range',
        'flag',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(LabResult::class, 'lab_result_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(InvestigationCriterion::class, 'criteria_id');
    }
}
