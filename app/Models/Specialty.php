<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Doctors who hold this specialty.
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doctor_specialty');
    }

    /**
     * Services that require or are linked to this specialty.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceCatalog::class,
            'service_specialty',
            'specialty_id',
            'service_catalog_id'
        );
    }

    /**
     * Departments that cover this specialty.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_specialty');
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
}
