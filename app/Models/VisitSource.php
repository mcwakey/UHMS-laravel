<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dynamic lookup describing HOW a visit started (direct, appointment, emergency,
 * referral, ...). Visits store the `code`; this table drives labels/colour/order.
 */
class VisitSource extends Model
{
    protected $fillable = ['code', 'name', 'description', 'color', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'visit_source', 'code');
    }

    public static function labelFor(?string $code): ?string
    {
        return $code ? static::where('code', $code)->value('name') : null;
    }
}
