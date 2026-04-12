<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcdCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'category',
        'chapter',
        'is_billable',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
    ];

    /* ── Relationships ────────────────────────────────── */

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'icd_code_id');
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeByChapter($query, string $chapter)
    {
        return $query->where('chapter', $chapter);
    }

    /* ── Accessors ────────────────────────────────────── */

    public function getDisplayAttribute(): string
    {
        return "{$this->code} — {$this->description}";
    }
}
