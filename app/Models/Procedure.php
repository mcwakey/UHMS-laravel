<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'category',
        'description',
        'default_price',
        'nhis_price',
        'requires_consent',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'nhis_price' => 'decimal:2',
        'requires_consent' => 'boolean',
        'is_active' => 'boolean',
    ];

    /* ── Relationships ────────────────────────────────── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function patientProcedures(): HasMany
    {
        return $this->hasMany(PatientProcedure::class);
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /* ── Accessors ────────────────────────────────────── */

    public function getFormattedPriceAttribute(): string
    {
        return '₵ ' . number_format($this->default_price, 2);
    }

    public function getFormattedNhisPriceAttribute(): string
    {
        return $this->nhis_price ? '₵ ' . number_format($this->nhis_price, 2) : '—';
    }
}
