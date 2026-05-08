<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitServiceItem extends Model
{
    use HasFactory;

    protected $table = 'visit_services';

    protected $fillable = [
        'visit_id',
        'service_catalog_id',
        'department_id',
        'patient_insurance_id',
        'payment_type',
        'insurance_type',
        'pricing_source',
        'quantity',
        'unit_price',
        'insurance_price',
        'insurance_covered',
        'patient_payable',
        'total_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'insurance_price' => 'decimal:2',
            'insurance_covered' => 'decimal:2',
            'patient_payable' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class, 'patient_insurance_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function serviceCatalog(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedTotalAttribute(): string
    {
        return '₵' . number_format($this->total_price, 2);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '₵' . number_format($this->unit_price, 2);
    }
}
