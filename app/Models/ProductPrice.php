<?php

namespace App\Models;

use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Insurance-type and provider-specific price overrides for a Product.
 *
 * Mirrors ServicePrice but keyed on product_id.
 *
 * Resolution priority (handled in ProductPriceResolver):
 *   1. Provider-specific  (insurance_provider_id IS NOT NULL)
 *   2. Insurance-type default  (insurance_provider_id IS NULL)
 *   3. product.base_price  (cash & carry fallback)
 */
class ProductPrice extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'product_prices';

    protected $fillable = [
        'product_id',
        'insurance_type',           // InsuranceType enum value
        'insurance_provider_id',    // null = type default; non-null = provider override
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'insurance_type', 'insurance_provider_id', 'price', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('product_prices')
            ->dontSubmitEmptyLogs();
    }

    /* ── Relationships ────────────────────────────────── */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /* ── Helpers ──────────────────────────────────────── */

    public function getInsuranceTypeEnumAttribute(): ?InsuranceType
    {
        return InsuranceType::tryFrom($this->insurance_type);
    }

    public function getTypeLabel(): string
    {
        $enum = $this->getInsuranceTypeEnumAttribute();
        $base = $enum?->label() ?? strtoupper($this->insurance_type);

        if ($this->insuranceProvider) {
            return "{$base} — {$this->insuranceProvider->name}";
        }

        return $base;
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
