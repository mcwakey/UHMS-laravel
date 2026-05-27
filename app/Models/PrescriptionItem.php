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

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function dispensingRecords()
    {
        return $this->hasMany(DispensingRecord::class);
    }

    public function medicationOrder()
    {
        return $this->hasOne(MedicationOrder::class);
    }

    public function getTotalDispensedAttribute(): int
    {
        return $this->dispensingRecords()->sum('quantity_dispensed');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, ($this->quantity ?? 0) - $this->total_dispensed);
    }
}
