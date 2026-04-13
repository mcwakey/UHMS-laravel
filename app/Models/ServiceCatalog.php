<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    use HasFactory;

    protected $table = 'service_catalog';

    protected $fillable = [
        'name',
        'code',
        'category',
        'price',
        'nhis_price',
        'is_nhis_covered',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'nhis_price' => 'decimal:2',
            'is_nhis_covered' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'service_specialty', 'service_catalog_id', 'specialty_id');
    }

    public function visitServiceLines()
    {
        return $this->hasMany(VisitServiceLine::class, 'service_catalog_id');
    }

    /**
     * Resolve the price to charge based on a PatientInsurance instance.
     * Returns ['unit_price', 'insurance_covered', 'patient_payable'].
     */
    public function getPricingForInsurance(PatientInsurance $insurance): array
    {
        $provider   = $insurance->insuranceProvider;
        $basePrice  = (float) $this->price;

        // Cash & Carry / SELF — patient pays everything
        if ($provider->is_default) {
            return ['unit_price' => $basePrice, 'insurance_covered' => 0.0, 'patient_payable' => $basePrice];
        }

        // NHIA — use nhis_price if service is covered
        if (in_array($provider->type instanceof \BackedEnum ? $provider->type->value : $provider->type, ['nhia', 'nhis'])
            && $this->is_nhis_covered && $this->nhis_price) {
            $covered = min((float) $this->nhis_price, $basePrice);
            return ['unit_price' => $basePrice, 'insurance_covered' => $covered, 'patient_payable' => max(0, $basePrice - $covered)];
        }

        // PRIVATE / CORPORATE — apply coverage_percentage
        $pct     = (float) ($provider->coverage_percentage ?? 0);
        $covered = round($basePrice * ($pct / 100), 2);
        return ['unit_price' => $basePrice, 'insurance_covered' => $covered, 'patient_payable' => round($basePrice - $covered, 2)];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNhisCovered($query)
    {
        return $query->where('is_nhis_covered', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceAttribute(): string
    {
        return '₵' . number_format($this->price, 2);
    }

    public function getFormattedNhisPriceAttribute(): string
    {
        return $this->nhis_price ? '₵' . number_format($this->nhis_price, 2) : '—';
    }
}
