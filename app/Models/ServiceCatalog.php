<?php

namespace App\Models;

use App\Enums\DepartmentType;
use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    use HasFactory;

    protected $table = 'service_catalog';

    protected $fillable = [
        'name',
        'description',
        'code',
        'category',
        'price',
        'is_active',
        'department_id',
        'department_type',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'department_type' => DepartmentType::class,
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

    public function prices()
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function visitServices()
    {
        return $this->hasMany(VisitServiceItem::class);
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



    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceAttribute(): string
    {
        return '₵' . number_format($this->price, 2);
    }


    /**
     * Get the applicable price for a given insurance type and optional provider.
     * Priority: provider-specific > type default > base price.
     */
    public function getPriceForInsurance(?InsuranceType $type, ?int $providerId = null): float
    {
        if (! $type) {
            return (float) $this->price;
        }

        $pricesLoaded = $this->relationLoaded('prices');
        $collection = $pricesLoaded ? $this->prices : $this->prices()->get();

        // Provider-specific override
        if ($providerId) {
            $specific = $collection->first(
                fn ($p) => $p->insurance_type === $type->value && $p->insurance_provider_id === $providerId
            );
            if ($specific) {
                return (float) $specific->price;
            }
        }

        // Type default
        $typeDefault = $collection->first(
            fn ($p) => $p->insurance_type === $type->value && $p->insurance_provider_id === null
        );
        if ($typeDefault) {
            return (float) $typeDefault->price;
        }

        return (float) $this->price;
    }

    /**
     * Return all department IDs this service is associated with:
     * primary department_id + unique departments from specialties.
     */
    public function getDepartmentIds(): array
    {
        $ids = [];
        if ($this->department_id) {
            $ids[] = $this->department_id;
        }
        if ($this->relationLoaded('specialties')) {
            foreach ($this->specialties as $spec) {
                if ($spec->department_id) {
                    $ids[] = $spec->department_id;
                }
            }
        }
        return array_unique($ids);
    }}
