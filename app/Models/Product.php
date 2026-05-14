<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'product_type',
        'unit',
        'description',
        'reorder_level',
        'default_cost',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'product_type'  => ProductType::class,
            'reorder_level' => 'decimal:4',
            'default_cost'  => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'product_department')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(ProductStockMovement::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(ProductStockBalance::class);
    }

    public function serviceConsumables()
    {
        return $this->hasMany(ServiceConsumable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->whereHas('departments', fn ($q) => $q
            ->where('departments.id', $departmentId)
            ->where('product_department.is_active', true));
    }
}
