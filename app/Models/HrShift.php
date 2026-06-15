<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrShift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'grace_minutes', 'break_minutes', 'is_night_shift', 'is_active'];

    protected $casts = ['is_night_shift' => 'boolean', 'is_active' => 'boolean'];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'shift_id');
    }
}
