<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestCriterion extends Model
{
    protected $fillable = [
        'lab_test_id',
        'name',
        'normal_range',
        'unit',
        'sort_order',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }
}
