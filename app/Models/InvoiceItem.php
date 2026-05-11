<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'visit_id',
        'patient_id',
        'service_catalog_id',
        'department_id',
        'source_type',
        'source_id',
        'description',
        'quantity',
        'unit_price',
        'insurance_price',
        'insurance_covered',
        'patient_payable',
        'paid_amount',
        'balance',
        'payment_status',
        'total_price',
        'is_nhis_covered',
        'nhis_approved_amount',
        'cash_price',
        'selected_price',
        'discount_amount',
        'payer_type',
        'insurance_provider_id',
        'patient_insurance_id',
        'insurance_type',
        'pricing_source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'insurance_price' => 'decimal:2',
            'insurance_covered' => 'decimal:2',
            'patient_payable' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'total_price' => 'decimal:2',
            'nhis_approved_amount' => 'decimal:2',
            'cash_price' => 'decimal:2',
            'selected_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'is_nhis_covered' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function serviceCatalog()
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function patientInsurance()
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || (float) $this->balance <= 0.0;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partially_paid';
    }

    public function refreshPaymentStatus(): self
    {
        $paid     = (float) $this->paid_amount;
        $payable  = (float) $this->patient_payable;
        $balance  = max(0.0, round($payable - $paid, 2));

        $status = 'unpaid';
        if ($payable <= 0.0) {
            $status = 'paid';
        } elseif ($balance <= 0.0) {
            $status = 'paid';
        } elseif ($paid > 0.0) {
            $status = 'partially_paid';
        }

        $this->forceFill([
            'balance'        => $balance,
            'payment_status' => $status,
        ])->save();

        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid'           => 'success',
            'partially_paid' => 'warning',
            'waived'         => 'info',
            'cancelled',
            'voided'         => 'secondary',
            default          => 'danger',
        };
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
}
