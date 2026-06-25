<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dynamic lookup describing the statistical attendance category of a visit
 * (first_ever, first_attendance_of_year, subsequent_attendance, ...). This value
 * is auto-computed when a visit is created and is NOT user-editable.
 */
class AttendanceClass extends Model
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
        return $this->hasMany(Visit::class, 'attendance_class', 'code');
    }

    public static function labelFor(?string $code): ?string
    {
        return $code ? static::where('code', $code)->value('name') : null;
    }
}
