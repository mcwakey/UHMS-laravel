<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugGenericName extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'atc_code',
        'therapeutic_class',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function drugs(): HasMany
    {
        return $this->hasMany(Drug::class, 'generic_name_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'generic_name_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
