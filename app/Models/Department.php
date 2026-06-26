<?php

namespace App\Models;

use App\Enums\DepartmentType;
use App\Enums\ResultType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'status',
        'result_type',
        'is_stock_managed',
    ];

    protected function casts(): array
    {
        return [
            'type'             => DepartmentType::class,
            'result_type'      => ResultType::class,
            'is_stock_managed' => 'boolean',
        ];
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceCatalog::class);
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(Specialty::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class, 'target_department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConsultation($query)
    {
        return $query->whereIn('type', DepartmentType::valuesFor(DepartmentType::consultationTypes()));
    }

    /**
     * Filter by a list of DepartmentType cases (or raw string values).
     *
     * @param  array<int, DepartmentType|string>  $types
     */
    public function scopeOfTypes($query, array $types)
    {
        $values = array_map(
            fn ($type) => $type instanceof DepartmentType ? $type->value : $type,
            $types,
        );

        return $query->whereIn('type', $values);
    }

    public function scopeInvestigation($query)
    {
        return $query->whereIn('type', DepartmentType::valuesFor(DepartmentType::investigationTypes()));
    }

    public function scopeProcedureCapable($query)
    {
        return $query->whereIn('type', DepartmentType::valuesFor(DepartmentType::procedureTypes()));
    }

    public function scopeStockManaged($query)
    {
        return $query->where('is_stock_managed', true);
    }

    public function scopeAcceptsRequests($query)
    {
        // Include departments that have an explicit result_type set,
        // OR departments whose DepartmentType implies investigation work
        // (so existing departments don't disappear before result_type is configured).
        $investigationTypes = DepartmentType::valuesFor(DepartmentType::investigationTypes());

        return $query->where('status', 'active')
            ->where(function ($q) use ($investigationTypes) {
                $q->where('result_type', '!=', ResultType::NONE->value)
                  ->orWhereIn('type', $investigationTypes);
            });
    }
}
