<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'drug_name',
        'drug_id',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'route',
        'instructions',
        'is_dispensed',
    ];

    protected function casts(): array
    {
        return [
            'is_dispensed' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
