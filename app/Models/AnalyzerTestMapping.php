<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyzerTestMapping extends Model
{
    protected $fillable = [
        'analyzer_id',
        'analyzer_test_code',
        'lab_test_id',
        'unit_conversion_factor',
    ];

    protected $casts = [
        'unit_conversion_factor' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function analyzer(): BelongsTo
    {
        return $this->belongsTo(Analyzer::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    /**
     * Apply unit conversion to a raw result value if a factor is set.
     */
    public function convertValue(string $rawValue): string
    {
        if ($this->unit_conversion_factor && is_numeric($rawValue)) {
            return (string) round((float) $rawValue * (float) $this->unit_conversion_factor, 4);
        }

        return $rawValue;
    }
}
