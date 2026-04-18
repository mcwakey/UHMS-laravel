<?php

namespace App\Models;

use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    use HasFactory;

    protected $table = 'service_prices';

    protected $fillable = [
        'service_catalog_id',
        'insurance_type',
        'insurance_provider_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────
    public function serviceCatalog(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    // ── Helpers ────────────────────────────────────────────
    public function getInsuranceTypeEnumAttribute(): ?InsuranceType
    {
        return InsuranceType::tryFrom($this->insurance_type);
    }

    public function getTypeLabel(): string
    {
        return InsuranceType::tryFrom($this->insurance_type)?->label() ?? strtoupper($this->insurance_type);
    }
}
