<?php

namespace App\Models;

use App\Enums\DepartmentType;
use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceCatalog extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'service_catalog';

    /*
    |--------------------------------------------------------------------------
    | Overall result type (configurable per investigation/test)
    |--------------------------------------------------------------------------
    | Drives how the overall result is entered (result-entry UI) and how it is
    | stored canonically + tallied in reports. Stored values are always the
    | internal English constants below; display labels are translated.
    */
    public const OVERALL_RESULT_FREE_TEXT          = 'free_text';
    public const OVERALL_RESULT_NUMERIC            = 'numeric';
    public const OVERALL_RESULT_BOOLEAN            = 'boolean';
    public const OVERALL_RESULT_POSITIVE_NEGATIVE  = 'positive_negative';

    protected $fillable = [
        'name',
        'description',
        'code',
        'category',
        'price',
        'is_active',
        'is_billable',
        'requires_rendering_tracking',
        'department_id',
        'department_type',
        'overall_result_type',
        'overall_result_unit',
        'overall_result_min_value',
        'overall_result_max_value',
        'overall_result_positive_label',
        'overall_result_negative_label',
        'overall_result_true_label',
        'overall_result_false_label',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_billable' => 'boolean',
            'requires_rendering_tracking' => 'boolean',
            'department_type' => DepartmentType::class,
            'overall_result_min_value' => 'decimal:4',
            'overall_result_max_value' => 'decimal:4',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Overall result type helpers
    |--------------------------------------------------------------------------
    */

    /** Supported overall result types keyed by canonical value => translation key. */
    public static function overallResultTypes(): array
    {
        return [
            self::OVERALL_RESULT_FREE_TEXT         => 'investigations.free_text',
            self::OVERALL_RESULT_NUMERIC           => 'investigations.numeric_value',
            self::OVERALL_RESULT_BOOLEAN           => 'investigations.true_false',
            self::OVERALL_RESULT_POSITIVE_NEGATIVE => 'investigations.positive_negative',
        ];
    }

    /** Canonical type for this service, defaulting to free_text for legacy/unset rows. */
    public function overallResultType(): string
    {
        $type = $this->overall_result_type ?: self::OVERALL_RESULT_FREE_TEXT;

        return array_key_exists($type, self::overallResultTypes())
            ? $type
            : self::OVERALL_RESULT_FREE_TEXT;
    }

    /** Translated label for the configured overall result type. */
    public function overallResultTypeLabel(): string
    {
        return __(self::overallResultTypes()[$this->overallResultType()]);
    }

    public function usesNumericOverallResult(): bool
    {
        return $this->overallResultType() === self::OVERALL_RESULT_NUMERIC;
    }

    public function usesBooleanOverallResult(): bool
    {
        return $this->overallResultType() === self::OVERALL_RESULT_BOOLEAN;
    }

    public function usesPositiveNegativeOverallResult(): bool
    {
        return $this->overallResultType() === self::OVERALL_RESULT_POSITIVE_NEGATIVE;
    }

    public function usesFreeTextOverallResult(): bool
    {
        return $this->overallResultType() === self::OVERALL_RESULT_FREE_TEXT;
    }

    /** Display label for the canonical positive value (custom or translated default). */
    public function positiveLabel(): string
    {
        return $this->overall_result_positive_label ?: __('investigations.positive');
    }

    public function negativeLabel(): string
    {
        return $this->overall_result_negative_label ?: __('investigations.negative');
    }

    public function trueLabel(): string
    {
        return $this->overall_result_true_label ?: __('investigations.true');
    }

    public function falseLabel(): string
    {
        return $this->overall_result_false_label ?: __('investigations.false');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'category', 'price', 'is_active', 'is_billable', 'requires_rendering_tracking', 'department_id', 'overall_result_type'])
            ->logOnlyDirty()
            ->useLogName('service_catalog')
            ->dontSubmitEmptyLogs();
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

    public function investigationHeaders()
    {
        return $this->hasMany(InvestigationHeader::class, 'service_id')->orderBy('sort_order');
    }

    public function investigationCriteria()
    {
        return $this->hasMany(InvestigationCriterion::class, 'service_id')->orderBy('sort_order');
    }

    public function consumables()
    {
        return $this->hasMany(ServiceConsumable::class, 'service_id');
    }

    public function serviceRenderings()
    {
        return $this->hasMany(ServiceRendering::class, 'service_id');
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
