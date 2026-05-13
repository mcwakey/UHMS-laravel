<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TheatreRoom extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProcedureSchedule::class);
    }
}
