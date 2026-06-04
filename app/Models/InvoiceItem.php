<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * Canonical source_type values for billable line items (Section 17 of UHMS spec).
     * Stored as plain strings; helpers ensure consistent usage across services.
     */
    public const SOURCE_CONSULTATION_SERVICE = 'consultation_service';

    public const SOURCE_INVESTIGATION_SERVICE = 'investigation_service';

    public const SOURCE_PROCEDURE_SERVICE = 'procedure_service';

    public const SOURCE_PHARMACY_PRODUCT = 'pharmacy_product';

    public const SOURCE_PHARMACY_BILLING_SELECTION = 'pharmacy_billing_selection';

    public const SOURCE_WARD_CONSUMABLE = 'ward_consumable';

    public const SOURCE_EMERGENCY_CONSUMABLE = 'emergency_consumable';

    public const SOURCE_EMERGENCY_BED_CHARGE = 'emergency_bed_charge';

    public const SOURCE_EMERGENCY_DAILY_CONSUMABLE_CHARGE = 'emergency_daily_consumable_charge';

    public const SOURCE_ADMISSION_FEE = 'admission_fee';

    public const SOURCE_ADMISSION_BED_CHARGE = 'admission_bed_charge';

    public const SOURCE_ADMISSION_DAILY_CONSUMABLE_CHARGE = 'admission_daily_consumable_charge';

    public const SOURCE_INVESTIGATION_CONSUMABLE = 'investigation_consumable';

    public const SOURCE_PROCEDURE_CONSUMABLE = 'procedure_consumable';

    public static function sourceTypes(): array
    {
        return [
            self::SOURCE_CONSULTATION_SERVICE,
            self::SOURCE_INVESTIGATION_SERVICE,
            self::SOURCE_PROCEDURE_SERVICE,
            self::SOURCE_PHARMACY_PRODUCT,
            self::SOURCE_PHARMACY_BILLING_SELECTION,
            self::SOURCE_WARD_CONSUMABLE,
            self::SOURCE_EMERGENCY_CONSUMABLE,
            self::SOURCE_EMERGENCY_BED_CHARGE,
            self::SOURCE_EMERGENCY_DAILY_CONSUMABLE_CHARGE,
            self::SOURCE_ADMISSION_FEE,
            self::SOURCE_ADMISSION_BED_CHARGE,
            self::SOURCE_ADMISSION_DAILY_CONSUMABLE_CHARGE,
            self::SOURCE_INVESTIGATION_CONSUMABLE,
            self::SOURCE_PROCEDURE_CONSUMABLE,
        ];
    }

    protected $fillable = [
        'invoice_id',
        'visit_id',
        'patient_id',
        'service_catalog_id',
        'product_id',
        'department_id',
        'source_type',
        'source_id',
        'description',
        'quantity',
        // Legacy column kept for back-compat on older DB schemas where it is
        // still NOT NULL; BillingService mirrors selected_price into it.
        'unit_price',
        'cash_price',
        'insurance_price',
        'selected_price',
        'insurance_covered',
        'is_nhis_covered',
        'nhis_approved_amount',
        'discount_amount',
        'patient_payable',
        'paid_amount',
        'balance',
        'payment_status',
        // total_price retained for backwards-compat (= selected_price * quantity);
        // BillingService keeps it in sync but it is no longer the canonical total.
        'total_price',
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
            'quantity' => 'integer',
            'cash_price' => 'decimal:2',
            'insurance_price' => 'decimal:2',
            'selected_price' => 'decimal:2',
            'insurance_covered' => 'decimal:2',
            'is_nhis_covered' => 'boolean',
            'nhis_approved_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'patient_payable' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'total_price' => 'decimal:2',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
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

    public function discountEvents()
    {
        return $this->hasMany(InvoiceDiscount::class);
    }

    /**
     * Alias of allocations() to match spec naming.
     */
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function serviceRendering()
    {
        return $this->hasOne(ServiceRendering::class);
    }

    /**
     * Alias of serviceCatalog() — spec calls it service().
     */
    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_catalog_id');
    }

    /**
     * The user who created this invoice item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
        $paid = (float) $this->paid_amount;
        $payable = (float) $this->patient_payable;
        $balance = max(0.0, round($payable - $paid, 2));

        $status = 'unpaid';
        if ($payable <= 0.0) {
            $status = 'paid';
        } elseif ($balance <= 0.0) {
            $status = 'paid';
        } elseif ($paid > 0.0) {
            $status = 'partially_paid';
        }

        $this->forceFill([
            'balance' => $balance,
            'payment_status' => $status,
        ])->save();

        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'success',
            'partially_paid' => 'warning',
            'waived' => 'info',
            'cancelled',
            'voided' => 'secondary',
            default => 'danger',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedTotalAttribute(): string
    {
        return '₵'.number_format($this->total_price, 2);
    }
}
